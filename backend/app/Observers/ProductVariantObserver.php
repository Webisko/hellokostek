<?php

namespace App\Observers;

use App\Models\ProductVariant;
use App\Models\BackInStockSubscription;
use App\Mail\BackInStockMail;
use App\Models\TransactionalEmailLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProductVariantObserver
{
    public function updated(ProductVariant $variant): void
    {
        if ($variant->manages_stock && $variant->stock_quantity > 0 && (int) $variant->getOriginal('stock_quantity') <= 0) {
            
            $product = $variant->product;
            if (!$product) {
                return;
            }

            $subscriptions = BackInStockSubscription::query()
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant->id)
                ->where('status', 'pending')
                ->get();

            foreach ($subscriptions as $subscription) {
                try {
                    $mailable = new BackInStockMail($product, $variant);
                    
                    Mail::to($subscription->email)->send($mailable);

                    // Log transactional email
                    TransactionalEmailLog::query()->create([
                        'order_id' => null,
                        'email_type' => 'back_in_stock_notification',
                        'recipient' => $subscription->email,
                        'subject' => (string) $mailable->envelope()->subject,
                        'status' => 'sent',
                        'sent_at' => now(),
                        'payload' => [
                            'product_id' => $product->id,
                            'product_sku' => $product->sku,
                            'variant_id' => $variant->id,
                            'variant_sku' => $variant->sku,
                        ],
                    ]);

                    // Update subscription status
                    $subscription->update([
                        'status' => 'notified',
                        'notified_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Failed to send back-in-stock notification for variant to {$subscription->email}: " . $e->getMessage());
                }
            }
        }
    }
}
