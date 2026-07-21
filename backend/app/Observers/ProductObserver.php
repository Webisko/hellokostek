<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\BackInStockSubscription;
use App\Mail\BackInStockMail;
use App\Models\TransactionalEmailLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    public function updated(Product $product): void
    {
        // Only trigger if product manages stock, now has quantity, and previously had 0 or less
        if ($product->manages_stock && $product->stock_quantity > 0 && (int) $product->getOriginal('stock_quantity') <= 0) {
            
            // Check if product has variants (if it does, variant updates will handle it)
            if ($product->variants()->exists()) {
                return;
            }

            $subscriptions = BackInStockSubscription::query()
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->where('status', 'pending')
                ->get();

            foreach ($subscriptions as $subscription) {
                try {
                    $mailable = new BackInStockMail($product);
                    
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
                        ],
                    ]);

                    // Update subscription status
                    $subscription->update([
                        'status' => 'notified',
                        'notified_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Failed to send back-in-stock notification to {$subscription->email}: " . $e->getMessage());
                }
            }
        }
    }
}
