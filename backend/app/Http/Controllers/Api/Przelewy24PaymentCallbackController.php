<?php

namespace App\Http\Controllers\Api;

use App\Domain\Commerce\Payments\PaymentCallbackService;
use App\Domain\Operations\IntegrationLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Przelewy24PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentCallbackService $paymentCallbackService,
        private readonly IntegrationLogService $integrationLogService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if ($this->looksLikePrzelewy24Notification($request)) {
            $payload = $request->validate([
                'merchantId' => ['required', 'integer'],
                'posId' => ['required', 'integer'],
                'sessionId' => ['required', 'string'],
                'amount' => ['required', 'integer'],
                'originAmount' => ['required', 'integer'],
                'currency' => ['required', 'string', 'size:3'],
                'orderId' => ['required', 'integer'],
                'methodId' => ['required', 'integer'],
                'statement' => ['required', 'string'],
                'sign' => ['required', 'string'],
            ]);

            if (! $this->paymentCallbackService->notificationIsAuthentic($payload)) {
                $this->integrationLogService->record(
                    integration: 'przelewy24',
                    event: 'payment_callback_unauthorized',
                    status: 'warning',
                    direction: 'incoming',
                    externalReference: (string) ($payload['sessionId'] ?? 'unknown'),
                    requestPayload: $payload,
                    responsePayload: [
                        'outcome' => 'rejected',
                    ],
                    errorMessage: 'Odrzucono callback platnosci z powodu niepoprawnego podpisu lub niezgodnych danych sprzedawcy.',
                );

                abort(403);
            }

            $result = $this->paymentCallbackService->handlePrzelewy24Notification($payload);
            $transaction = $result['transaction'];
            $order = $result['order'];

            return $this->responsePayload($transaction->status, $result['replayed'], $transaction->confirmed_at?->toIso8601String(), $transaction->failed_at?->toIso8601String(), $order->number, $order->payment_status);
        }

        $payload = $request->validate([
            'order_number' => ['required', 'string'],
            'session_id' => ['required', 'string'],
            'status' => ['required', 'string', 'in:paid,failed'],
            'provider_status' => ['nullable', 'string'],
            'error_message' => ['nullable', 'string'],
        ]);

        $token = (string) config('services.przelewy24.callback_token');
        $receivedToken = (string) $request->header('X-Callback-Token', '');

        if (blank($token) || ! hash_equals($token, $receivedToken)) {
            $this->integrationLogService->record(
                integration: 'przelewy24',
                event: 'payment_callback_unauthorized',
                status: 'warning',
                direction: 'incoming',
                externalReference: (string) ($payload['session_id'] ?? 'unknown'),
                requestPayload: $payload,
                responsePayload: [
                    'outcome' => 'rejected',
                ],
                errorMessage: 'Odrzucono callback platnosci z powodu braku poprawnego tokenu integracyjnego.',
            );

            abort(403);
        }

        $result = $this->paymentCallbackService->handle($payload);
        $transaction = $result['transaction'];
        $order = $result['order'];

        return $this->responsePayload(
            $transaction->status,
            $result['replayed'],
            optional($transaction->confirmed_at)->toIso8601String(),
            optional($transaction->failed_at)->toIso8601String(),
            $order->number,
            $order->payment_status,
        );
    }

    private function looksLikePrzelewy24Notification(Request $request): bool
    {
        return $request->has(['merchantId', 'posId', 'sessionId', 'orderId', 'sign']);
    }

    private function responsePayload(string $transactionStatus, bool $replayed, ?string $confirmedAt, ?string $failedAt, string $orderNumber, string $orderPaymentStatus): JsonResponse
    {
        return response()->json([
            'data' => [
                'order_number' => $orderNumber,
                'payment_status' => $orderPaymentStatus,
                'transaction_status' => $transactionStatus,
                'replayed' => $replayed,
                'confirmed_at' => $confirmedAt,
                'failed_at' => $failedAt,
            ],
        ]);
    }
}