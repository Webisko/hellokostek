<?php

namespace App\Models;

use App\Domain\Commerce\Enums\CustomerSegment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'user_id',
        'coupon_id',
        'status',
        'payment_status',
        'fulfillment_status',
        'currency',
        'customer_segment',
        'customer_email',
        'customer_first_name',
        'customer_last_name',
        'customer_phone',
        'billing_company_name',
        'billing_nip',
        'wants_invoice',
        'is_privileged_entrepreneur',
        'subtotal_amount',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'total_amount',
        'shipping_method_code',
        'shipping_method_name',
        'billing_address',
        'shipping_address',
        'placed_at',
        'notes',
        'metadata',
        'tracking_number',
        'carrier',
        'shipped_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_segment' => CustomerSegment::class,
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'wants_invoice' => 'boolean',
            'is_privileged_entrepreneur' => 'boolean',
            'placed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function fulfillmentActions(): HasMany
    {
        return $this->hasMany(OrderFulfillmentAction::class)->orderBy('id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function integrationLogs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class)->latest('occurred_at');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function latestPaymentTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class)->latestOfMany();
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function adminActivityLogs(): MorphMany
    {
        return $this->morphMany(AdminActivityLog::class, 'subject')->latest('created_at');
    }

    public function isCod(): bool
    {
        $settings = app(\App\Support\StoreSettings::class);
        $method = $settings->shippingMethod($this->shipping_method_code);
        return $method ? (bool) ($method['supports_cod'] ?? false) : false;
    }

    protected static function booted(): void
    {
        static::updating(function (Order $order): void {
            if ($order->isDirty('status') && $order->status === 'shipped' && $order->getOriginal('status') !== 'shipped') {
                $order->shipped_at = now();
            }
        });

        static::updated(function (Order $order): void {
            if ($order->wasChanged('status') && $order->status === 'cancelled') {
                // Restock items
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product && $product->isBundle()) {
                        foreach ($product->bundleItems as $bundleItem) {
                            if ($bundleItem->product_variant_id) {
                                $variant = $bundleItem->variant;
                                if ($variant && $variant->manages_stock) {
                                    $neededQty = $bundleItem->quantity * $item->quantity;
                                    $variant->increment('stock_quantity', $neededQty);
                                }
                            } else {
                                $comp = $bundleItem->product;
                                if ($comp && $comp->manages_stock) {
                                    $neededQty = $bundleItem->quantity * $item->quantity;
                                    $comp->increment('stock_quantity', $neededQty);
                                }
                            }
                        }
                    } elseif ($item->product_variant_id) {
                        $variant = $item->variant;
                        if ($variant && $variant->manages_stock) {
                            $variant->increment('stock_quantity', $item->quantity);
                        }
                    } else {
                        if ($product && $product->manages_stock) {
                            $product->increment('stock_quantity', $item->quantity);
                        }
                    }
                }
                
                // Refund payment
                try {
                    app(\App\Domain\Commerce\Payments\PaymentSessionService::class)->refundOrder($order);
                } catch (\Exception $e) {
                }
            }

            if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
                // Generate Invoice PDF
                try {
                    $pdfContent = \App\Support\MinimalPdfGenerator::generateInvoice($order);
                    $pdfPath = storage_path('app/invoices/' . $order->number . '.pdf');
                    if (!file_exists(dirname($pdfPath))) {
                        mkdir(dirname($pdfPath), 0755, true);
                    }
                    file_put_contents($pdfPath, $pdfContent);
                } catch (\Exception $e) {
                }

                // Send Confirmation Email
                try {
                    app(\App\Domain\Communication\TransactionalEmailService::class)->sendOrderPaidEmail($order);
                } catch (\Exception $e) {
                }

                // Dispatch OrderPaid Event for billing integrations
                event(new \App\Events\OrderPaid($order));
            }

            if ($order->wasChanged('status') && $order->status === 'shipped') {
                if ($order->isCod()) {
                    // Generate Invoice PDF for COD orders if not already generated
                    try {
                        $pdfPath = storage_path('app/invoices/' . $order->number . '.pdf');
                        if (!file_exists($pdfPath)) {
                            $pdfContent = \App\Support\MinimalPdfGenerator::generateInvoice($order);
                            if (!file_exists(dirname($pdfPath))) {
                                mkdir(dirname($pdfPath), 0755, true);
                            }
                            file_put_contents($pdfPath, $pdfContent);
                        }
                    } catch (\Exception $e) {
                    }

                    // Dispatch to accounting integrations for COD order invoice creation
                    try {
                        \App\Jobs\SendOrderToAccountingJob::dispatch($order);
                    } catch (\Exception $e) {
                    }
                }

                // Send Shipping Email
                try {
                    app(\App\Domain\Communication\TransactionalEmailService::class)->sendShippingConfirmationEmail($order);
                } catch (\Exception $e) {
                }
            }
        });
    }
}