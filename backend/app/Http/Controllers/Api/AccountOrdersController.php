<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountOrdersController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with(['items', 'fulfillmentActions'])
            ->where('status', 'placed')
            ->latest('placed_at')
            ->get();

        return response()->json([
            'data' => [
                'orders' => $orders->map(static fn ($order) => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'fulfillment_status' => $order->fulfillment_status,
                    'currency' => $order->currency,
                    'total_amount' => $order->total_amount,
                    'placed_at' => optional($order->placed_at)->toIso8601String(),
                    'items' => $order->items->map(static fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'total_amount' => $item->total_amount,
                    ])->values()->all(),
                    'fulfillment_actions' => $order->fulfillmentActions->map(static fn ($action) => [
                        'id' => $action->id,
                        'action_type' => $action->action_type,
                        'status' => $action->status,
                        'title' => $action->title,
                        'due_at' => optional($action->due_at)->toIso8601String(),
                    ])->values()->all(),
                ])->values()->all(),
            ],
        ]);
    }
}