<?php

namespace App\Console\Commands;

use App\Domain\Communication\TransactionalEmailService;
use App\Models\Order;
use Illuminate\Console\Command;

class RecoverAbandonedCarts extends Command
{
    protected $signature = 'app:recover-abandoned-carts';

    protected $description = 'Wysyła wiadomości e-mail o porzuconych koszykach do klientów, którzy nie sfinalizowali zamówienia';

    public function handle(TransactionalEmailService $emailService): int
    {
        $settings = app(\App\Support\StoreSettings::class);
        if (!$settings->abandonedCartRecoveryEnabled()) {
            $this->info('Odzyskiwanie porzuconych koszyków jest wyłączone w ustawieniach sklepu.');
            return self::SUCCESS;
        }

        $hours = $settings->abandonedCartRecoveryDelayHours();

        // Find draft orders updated between $hours and 7 days ago, with non-empty emails
        // where abandoned email has not been sent yet
        $drafts = Order::query()
            ->where('status', 'draft')
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->where('updated_at', '<=', now()->subHours($hours))
            ->where('updated_at', '>=', now()->subDays(7))
            ->where(function ($query) {
                $query->whereNull('metadata')
                    ->orWhereNull('metadata->abandoned_email_sent')
                    ->orWhere('metadata->abandoned_email_sent', false);
            })
            ->whereHas('items')
            ->get();

        $count = $drafts->count();
        $this->info("Znaleziono {$count} porzuconych koszyków do przetworzenia.");

        foreach ($drafts as $order) {
            try {
                $couponCode = null;
                $discountPercent = 0;
                $discountDurationDays = 0;
                $recoveryUrl = $settings->abandonedCartRecoveryUrl();

                if ($settings->abandonedCartRecoveryDiscountEnabled()) {
                    $discountPercent = $settings->abandonedCartRecoveryDiscountPercentage();
                    $discountDurationDays = $settings->abandonedCartRecoveryDiscountDurationDays();
                    $couponCode = 'WROC-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6));

                    // Create single-use coupon in database
                    \App\Models\Coupon::query()->create([
                        'code' => $couponCode,
                        'name' => 'Odzyskiwanie koszyka dla ' . $order->number,
                        'discount_type' => 'percentage',
                        'value' => $discountPercent,
                        'starts_at' => now(),
                        'ends_at' => now()->addDays($discountDurationDays),
                        'is_active' => true,
                        'usage_limit' => 1,
                        'usage_limit_per_customer' => 1,
                        'metadata' => [
                            'source' => 'abandoned_cart_recovery',
                            'order_id' => $order->id,
                        ],
                    ]);
                }

                // Prepare recovery link
                $resumeLink = str_replace('{number}', $order->number, $recoveryUrl);
                if ($couponCode) {
                    $resumeLink .= (str_contains($resumeLink, '?') ? '&' : '?') . 'coupon_code=' . $couponCode;
                }

                // Save metadata before sending email so the mailable template has access to it
                $metadata = $order->metadata ?? [];
                $metadata['abandoned_email_sent'] = true;
                $metadata['abandoned_email_sent_at'] = now()->toIso8601String();
                $metadata['recovery_link'] = $resumeLink;
                if ($couponCode) {
                    $metadata['recovery_coupon_code'] = $couponCode;
                    $metadata['recovery_discount_percent'] = $discountPercent;
                    $metadata['recovery_discount_ends_at'] = now()->addDays($discountDurationDays)->toIso8601String();
                }

                $order->forceFill(['metadata' => $metadata])->save();

                $emailService->sendAbandonedCartEmail($order);

                $this->info("Wysłano e-mail do: {$order->customer_email} (Zamówienie: {$order->number})");
            } catch (\Throwable $e) {
                $this->error("Błąd wysyłki do {$order->customer_email}: " . $e->getMessage());
            }
        }

        $this->info('Zakończono odzyskiwanie porzuconych koszyków.');

        return self::SUCCESS;
    }
}
