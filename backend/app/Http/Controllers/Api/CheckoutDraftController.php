<?php

namespace App\Http\Controllers\Api;

use App\Domain\Commerce\Checkout\CheckoutOrderService;
use App\Domain\Commerce\Enums\CustomerSegment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutDraftController extends Controller
{
    public function __construct(
        private readonly CheckoutOrderService $checkoutOrderService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.slug' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'customer_segment' => ['nullable', 'string', Rule::in(array_map(
                static fn (CustomerSegment $segment) => $segment->value,
                CustomerSegment::cases(),
            ))],
            'shipping_method_code' => ['nullable', 'string'],
            'coupon_code' => ['nullable', 'string'],
            'customer' => ['required', 'array'],
            'customer.email' => ['required', 'email:rfc'],
            'customer.first_name' => ['required', 'string', 'max:255'],
            'customer.last_name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:64'],
            'customer.wants_invoice' => ['nullable', 'boolean'],
            'customer.company_name' => ['nullable', 'string', 'max:255'],
            'customer.nip' => ['nullable', 'string', 'max:64'],
            'billing_address' => ['nullable', 'array'],
            'shipping_address' => ['nullable', 'array'],
            'delivery_point' => ['nullable', 'array'],
            'delivery_point.id' => ['nullable', 'string', 'max:64'],
            'delivery_point.name' => ['nullable', 'string', 'max:255'],
            'delivery_point.address' => ['nullable', 'string', 'max:255'],
            'delivery_point.postal_code' => ['nullable', 'string', 'max:32'],
            'delivery_point.city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = $this->checkoutOrderService->createDraft($validated);
        $order = $result['order'];

        return response()->json([
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'fulfillment_status' => $order->fulfillment_status,
                    'customer_email' => $order->customer_email,
                    'shipping_method_code' => $order->shipping_method_code,
                    'shipping_method_name' => $order->shipping_method_name,
                    'subtotal_amount' => $order->subtotal_amount,
                    'discount_amount' => $order->discount_amount,
                    'shipping_amount' => $order->shipping_amount,
                    'total_amount' => $order->total_amount,
                    'items_count' => $order->items->count(),
                    'wants_invoice' => $order->wants_invoice,
                    'billing_company_name' => $order->billing_company_name,
                    'billing_nip' => $order->billing_nip,
                    'delivery_point' => data_get($order->metadata, 'delivery_point'),
                ],
                'quote' => $result['quote']->toArray(),
            ],
        ], 201);
    }
}