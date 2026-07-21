<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use App\Models\AdminActivityLog;

class OrderRelationsSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::all();
        $user = User::first() ?: User::factory()->create();
        foreach ($orders as $order) {
            if ($order->items()->count() === 0) {
                $product = \App\Models\Product::first();
                if ($product) {
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_type' => $product->type,
                        'sku' => $product->sku ?: 'PROD-SKU-1',
                        'name' => $product->name,
                        'quantity' => 1,
                        'unit_price_amount' => $order->total_amount ?: 10000,
                        'regular_unit_price_amount' => $order->total_amount ?: 10000,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'total_amount' => $order->total_amount ?: 10000,
                    ]);
                }
            }
            if ($order->paymentTransactions()->count() === 0) {
                $order->paymentTransactions()->create([
                    'provider' => 'p24',
                    'status' => 'paid',
                    'amount' => $order->total_amount,
                    'external_session_id' => 'p24_sess_' . uniqid(),
                    'initiated_at' => $order->placed_at ?? now(),
                ]);
            }
            if ($order->fulfillmentActions()->count() === 0) {
                app(\App\Domain\Commerce\Fulfillment\OrderFulfillmentPlanner::class)->ensurePlanned($order);
                if ($order->status === 'completed') {
                    $order->fulfillmentActions()->update([
                        'status' => 'fulfilled',
                        'completed_at' => $order->shipped_at ?? $order->placed_at ?? now(),
                    ]);
                }
            }
            if ($order->integrationLogs()->count() === 0) {
                $order->integrationLogs()->create([
                    'integration' => 'fakturownia',
                    'event' => 'invoice_generated',
                    'direction' => 'outgoing',
                    'status' => 'success',
                    'external_reference' => 'FV/2026/07/' . rand(10, 99),
                    'occurred_at' => $order->placed_at ?? now(),
                ]);
            }
            if (AdminActivityLog::where('subject_type', 'App\Models\Order')->where('subject_id', $order->id)->count() === 0) {
                AdminActivityLog::create([
                    'actor_id' => $user->id,
                    'subject_type' => 'App\Models\Order',
                    'subject_id' => $order->id,
                    'event' => 'created',
                    'summary' => 'Utworzono zamówienie w sklepie internetowym.',
                ]);
                AdminActivityLog::create([
                    'actor_id' => $user->id,
                    'subject_type' => 'App\Models\Order',
                    'subject_id' => $order->id,
                    'event' => 'updated',
                    'summary' => 'Zmieniono status płatności na Opłacone.',
                ]);
            }
        }
    }
}
