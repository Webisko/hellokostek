<?php

namespace App\Http\Controllers\Api;

use App\Domain\Commerce\Payments\PaymentSessionService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutPaymentSessionController extends Controller
{
    public function __construct(
        private readonly PaymentSessionService $paymentSessionService,
    ) {
    }

    public function __invoke(Request $request, string $number): JsonResponse
    {
        $order = Order::query()
            ->where('number', $number)
            ->whereIn('status', ['placed'])
            ->firstOrFail();

        // Verify ownership: authenticated user must own the order.
        // For guests: the caller must supply the order's email via the
        //   X-Order-Email header or `email` query parameter.
        $user = $request->user();

        if ($user !== null) {
            if ($user->is_admin !== true && $order->user_id !== $user->id) {
                abort(403, 'Brak dostępu do tego zamówienia.');
            }
        } else {
            $providedEmail = mb_strtolower(trim(
                (string) ($request->header('X-Order-Email') ?? $request->query('email', ''))
            ));

            if (blank($providedEmail) || $providedEmail !== mb_strtolower($order->customer_email)) {
                abort(403, 'Wymagana weryfikacja tożsamości. Podaj e-mail przypisany do zamówienia.');
            }
        }

        $blikCode = null;
        if ($request->has('blik_code')) {
            $request->validate([
                'blik_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            ]);
            $blikCode = $request->input('blik_code');
        }

        $transaction = $this->paymentSessionService->initiate($order, $blikCode);

        return response()->json([
            'data' => [
                'payment_session' => [
                    'id'                  => $transaction->id,
                    'provider'            => $transaction->provider,
                    'status'              => $transaction->status,
                    'amount'              => $transaction->amount,
                    'currency'            => $transaction->currency,
                    'external_session_id' => $transaction->external_session_id,
                    'redirect_url'        => $transaction->redirect_url,
                    'error_code'          => $transaction->error_code,
                    'error_message'       => $transaction->error_message,
                    'initiated_at'        => optional($transaction->initiated_at)->toIso8601String(),
                    'next_action'         => data_get($transaction->response_payload, 'next_action'),
                ],
            ],
        ], 201);
    }
}