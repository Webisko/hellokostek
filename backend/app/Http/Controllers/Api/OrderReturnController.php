<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderReturnController extends Controller
{
    public function index(): JsonResponse
    {
        $returns = Auth::user()->returns()
            ->with(['order', 'items.orderItem.product'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $returns,
        ]);
    }

    public function show(OrderReturn $orderReturn): JsonResponse
    {
        if ($orderReturn->user_id !== Auth::id()) {
            return response()->json(['message' => 'Brak dostępu.'], 403);
        }

        $orderReturn->load(['order', 'items.orderItem.product']);

        return response()->json([
            'data' => $orderReturn,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        $rules = [
            'reason' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];

        if ($user) {
            $rules['order_id'] = 'required_without:order_number|exists:orders,id';
            $rules['order_number'] = 'nullable|string';
        } else {
            $rules['order_number'] = 'required|string';
            $rules['customer_email'] = 'required|email';
        }

        $validated = $request->validate($rules);

        // Find the order
        if ($user && isset($validated['order_id'])) {
            $order = Order::find($validated['order_id']);
        } else {
            $orderNumber = $validated['order_number'] ?? null;
            $customerEmail = $validated['customer_email'] ?? ($user ? $user->email : null);

            if (!$orderNumber || !$customerEmail) {
                return response()->json(['message' => 'Numer zamówienia i e-mail klienta są wymagane.'], 422);
            }

            $order = Order::query()
                ->where('number', $orderNumber)
                ->where('customer_email', $customerEmail)
                ->first();
        }

        if (!$order) {
            return response()->json(['message' => 'Zamówienie nie zostało znalezione.'], 404);
        }

        if ($user && $order->user_id && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Brak dostępu do tego zamówienia.'], 403);
        }

        // Validate order status is eligible for return (e.g., must be placed, shipped, or completed)
        if (!in_array($order->status, ['placed', 'shipped', 'completed'])) {
            return response()->json(['message' => 'To zamówienie nie kwalifikuje się do zwrotu.'], 422);
        }

        // Must be paid or Cash on Delivery (COD) to initiate a return
        if ($order->payment_status !== 'paid' && !$order->isCod()) {
            return response()->json(['message' => 'To zamówienie nie zostało jeszcze opłacone.'], 422);
        }

        // Verify items and quantities belong to the order
        foreach ($validated['items'] as $itemData) {
            $orderItem = $order->items()->find($itemData['order_item_id']);
            if (!$orderItem) {
                return response()->json(['message' => 'Wybrana pozycja nie należy do tego zamówienia.'], 422);
            }

            $alreadyReturnedQuantity = \App\Models\OrderReturnItem::query()
                ->where('order_item_id', $orderItem->id)
                ->whereHas('orderReturn', function ($query) {
                    $query->whereIn('status', ['pending', 'approved', 'processed']);
                })
                ->sum('quantity');

            $availableForReturn = $orderItem->quantity - $alreadyReturnedQuantity;

            if ($itemData['quantity'] > $availableForReturn) {
                return response()->json([
                    'message' => "Ilość do zwrotu ({$itemData['quantity']}) przekracza dostępną ilość do zwrotu ({$availableForReturn}) dla produktu: {$orderItem->name}. Wcześniej zgłoszono już {$alreadyReturnedQuantity} szt."
                ], 422);
            }
        }

        $orderReturn = DB::transaction(function () use ($validated, $order, $user) {
            $return = OrderReturn::create([
                'order_id' => $order->id,
                'user_id' => $user?->id, // will be null for guest returns
                'status' => 'pending',
                'reason' => $validated['reason'] ?? '',
            ]);

            foreach ($validated['items'] as $itemData) {
                $return->items()->create([
                    'order_item_id' => $itemData['order_item_id'],
                    'quantity' => $itemData['quantity'],
                ]);
            }

            return $return;
        });

        // Send confirmation email
        try {
            app(\App\Domain\Communication\TransactionalEmailService::class)->sendOrderReturnConfirmationEmail($orderReturn);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Błąd wysyłki e-maila potwierdzającego zwrot: ' . $e->getMessage());
        }

        return response()->json([
            'data' => $orderReturn->load('items'),
            'message' => 'Wniosek o zwrot został zarejestrowany.',
        ], 201);
    }
}
