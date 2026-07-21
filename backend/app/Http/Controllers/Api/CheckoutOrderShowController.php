<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutOrderShowController extends Controller
{
    public function __invoke(Request $request, string $number): JsonResponse
    {
        $order = Order::query()
            ->with(['items', 'fulfillmentActions', 'latestPaymentTransaction'])
            ->where('number', $number)
            ->firstOrFail();

        // Verify ownership: authenticated user must own the order.
        // For guests: the caller must supply the order's email via the
        //   X-Order-Email header or `email` query parameter.
        $user = $request->user();

        if ($user !== null) {
            // Logged-in user: must be the owner or an admin
            if ($user->is_admin !== true && $order->user_id !== $user->id) {
                abort(403, 'Brak dostępu do tego zamówienia.');
            }
        } else {
            // Guest: email must match
            $providedEmail = mb_strtolower(trim(
                (string) ($request->header('X-Order-Email') ?? $request->query('email', ''))
            ));

            if (blank($providedEmail) || $providedEmail !== mb_strtolower($order->customer_email)) {
                abort(403, 'Wymagana weryfikacja tożsamości. Podaj e-mail przypisany do zamówienia.');
            }
        }

        return response()->json([
            'data' => [
                'order' => [
                    'id'                   => $order->id,
                    'number'               => $order->number,
                    'status'               => $order->status,
                    'payment_status'       => $order->payment_status,
                    'fulfillment_status'   => $order->fulfillment_status,
                    'customer_email'       => $order->customer_email,
                    'customer_first_name'  => $order->customer_first_name,
                    'customer_last_name'   => $order->customer_last_name,
                    'shipping_method_code' => $order->shipping_method_code,
                    'shipping_method_name' => $order->shipping_method_name,
                    'subtotal_amount'      => $order->subtotal_amount,
                    'discount_amount'      => $order->discount_amount,
                    'shipping_amount'      => $order->shipping_amount,
                    'total_amount'         => $order->total_amount,
                    'delivery_point'       => data_get($order->metadata, 'delivery_point'),
                    'placed_at'            => optional($order->placed_at)->toIso8601String(),
                ],
                'items' => $order->items->map(fn ($item) => [
                    'id'                => $item->id,
                    'name'              => $item->name,
                    'quantity'          => $item->quantity,
                    'product_type'      => $item->product_type->value,
                    'unit_price_amount' => $item->unit_price_amount,
                    'discount_amount'   => $item->discount_amount,
                    'total_amount'      => $item->total_amount,
                    'slug'              => $item->metadata['slug'] ?? null,
                ])->all(),
                'payment'         => $order->metadata['payment'] ?? null,
                'payment_session' => $order->latestPaymentTransaction ? [
                    'id'                  => $order->latestPaymentTransaction->id,
                    'provider'            => $order->latestPaymentTransaction->provider,
                    'status'              => $order->latestPaymentTransaction->status,
                    'amount'              => $order->latestPaymentTransaction->amount,
                    'currency'            => $order->latestPaymentTransaction->currency,
                    'external_session_id' => $order->latestPaymentTransaction->external_session_id,
                    'redirect_url'        => $order->latestPaymentTransaction->redirect_url,
                    'error_code'          => $order->latestPaymentTransaction->error_code,
                    'error_message'       => $order->latestPaymentTransaction->error_message,
                    'initiated_at'        => optional($order->latestPaymentTransaction->initiated_at)->toIso8601String(),
                    'confirmed_at'        => optional($order->latestPaymentTransaction->confirmed_at)->toIso8601String(),
                    'failed_at'           => optional($order->latestPaymentTransaction->failed_at)->toIso8601String(),
                    'next_action'         => data_get($order->latestPaymentTransaction->response_payload, 'next_action'),
                ] : null,
                'fulfillment_actions' => $order->fulfillmentActions->map(fn ($action) => [
                    'id'           => $action->id,
                    'action_type'  => $action->action_type,
                    'status'       => $action->status,
                    'title'        => $action->title,
                    'instructions' => $action->instructions,
                    'due_at'       => optional($action->due_at)->toIso8601String(),
                    'item_name'    => $action->metadata['item_name'] ?? null,
                    'product_type' => $action->metadata['product_type'] ?? null,
                ])->values()->all(),
            ],
        ]);
    }
}