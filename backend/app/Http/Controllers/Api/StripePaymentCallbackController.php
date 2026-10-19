<?php

namespace App\Http\Controllers\Api;

use App\Domain\Operations\IntegrationLogService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Domain\Commerce\Payments\StripeApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripePaymentCallbackController extends Controller
{
    public function __construct(
        private readonly StripeApiClient $stripeApiClient,
        private readonly IntegrationLogService $integrationLogService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('STRIPE_SIGNATURE');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            abort(400, 'Invalid payload');
        } catch (SignatureVerificationException $e) {
            $this->integrationLogService->record(
                integration: 'stripe',
                event: 'stripe_webhook_signature_invalid',
                status: 'warning',
                direction: 'incoming',
                externalReference: 'unknown',
                requestPayload: ['raw_encoded' => true],
                errorMessage: 'Odrzucono webhook Stripe z niepoprawnym podpisem.',
            );
            abort(400, 'Invalid signature');
        }

        $this->integrationLogService->record(
            integration: 'stripe',
            event: 'stripe_webhook_received',
            status: 'success',
            direction: 'incoming',
            externalReference: $event->id,
            requestPayload: ['event_type' => $event->type],
        );

        if (in_array($event->type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.async_payment_failed',
            'checkout.session.expired',
        ], true)) {
            $session = $event->data->object;
            $orderNumber = data_get($session->metadata, 'order_number');
            $stripeSessionId = $session->id;

            $transaction = PaymentTransaction::query()
                ->with('order')
                ->where('provider', 'stripe')
                ->where(function ($query) use ($stripeSessionId) {
                    $query->where('response_payload->registration->id', $stripeSessionId)
                        ->orWhere('external_session_id', $stripeSessionId)
                        ->orWhere('metadata->stripe_session_id', $stripeSessionId);
                })
                ->first();

            if ($transaction === null && filled($orderNumber)) {
                $transaction = PaymentTransaction::query()
                    ->with('order')
                    ->where('provider', 'stripe')
                    ->whereHas('order', function ($query) use ($orderNumber) {
                        $query->where('number', $orderNumber);
                    })
                    ->first();
            }

            if ($transaction === null || $transaction->order === null) {
                abort(404, 'Nie znaleziono transakcji dla tego webhooka Stripe.');
            }

            $order = $transaction->order;

            $isPaid = ($event->type === 'checkout.session.async_payment_succeeded')
                || ($event->type === 'checkout.session.completed' && $session->payment_status === 'paid');

            if ($isPaid) {
                DB::transaction(function () use ($transaction, $order, $session, $event) {
                    $responsePayload = $transaction->response_payload ?? [];
                    $responsePayload['callback'] = [
                        'event_type' => $event->type,
                        'session_id' => $session->id,
                        'payment_status' => $session->payment_status,
                        'received_at' => now()->toIso8601String(),
                    ];

                    $transaction->forceFill([
                        'status' => 'confirmed',
                        'response_payload' => $responsePayload,
                        'confirmed_at' => now(),
                    ])->save();

                    $order->forceFill([
                        'payment_status' => 'paid',
                    ])->save();
                });

                $this->integrationLogService->record(
                    integration: 'stripe',
                    event: 'payment_callback_confirmed',
                    status: 'success',
                    order: $order,
                    direction: 'incoming',
                    externalReference: $session->id,
                    requestPayload: ['event_type' => $event->type, 'session_id' => $session->id, 'payment_status' => $session->payment_status],
                    responsePayload: ['status' => 'confirmed'],
                );
            } elseif (in_array($event->type, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
                DB::transaction(function () use ($transaction, $order, $session, $event) {
                    $responsePayload = $transaction->response_payload ?? [];
                    $responsePayload['callback'] = [
                        'event_type' => $event->type,
                        'session_id' => $session->id,
                        'payment_status' => $session->payment_status,
                        'received_at' => now()->toIso8601String(),
                    ];

                    $transaction->forceFill([
                        'status' => 'failed',
                        'response_payload' => $responsePayload,
                        'failed_at' => now(),
                        'error_message' => 'Stripe session (' . $event->type . ') payment_status was ' . $session->payment_status,
                    ])->save();

                    if ($order->payment_status !== 'paid') {
                        $order->forceFill([
                            'payment_status' => 'failed',
                            'status' => 'cancelled',
                        ])->save();
                    }
                });

                $this->integrationLogService->record(
                    integration: 'stripe',
                    event: 'payment_callback_failed',
                    status: 'warning',
                    order: $order,
                    direction: 'incoming',
                    externalReference: $session->id,
                    requestPayload: ['event_type' => $event->type, 'session_id' => $session->id, 'payment_status' => $session->payment_status],
                    errorMessage: 'Stripe payment status was: ' . $session->payment_status,
                );
            }
        }

        return response()->json(['received' => true]);
    }
}
