<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'regular_price_amount',
        'sale_price_amount',
        'vat_rate',
        'stock_quantity',
        'manages_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'manages_stock' => 'boolean',
            'is_active' => 'boolean',
            'regular_price_amount' => 'integer',
            'sale_price_amount' => 'integer',
            'vat_rate' => 'integer',
            'stock_quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'product_variant_option_value');
    }

    public function currentPriceAmount(?User $user = null, ?\App\Domain\Commerce\Enums\CustomerSegment $segment = null): int
    {
        $user = $user ?? auth('sanctum')->user() ?? auth()->user();
        if ($segment === null && $user) {
            $segment = $user->segment();
        }
        $segment = $segment ?? \App\Domain\Commerce\Enums\CustomerSegment::Regular;

        // Resolve custom price for this variant
        if ($user) {
            $price = \App\Models\ProductCustomPrice::query()
                ->where('product_id', $this->product_id)
                ->where('product_variant_id', $this->id)
                ->where('user_id', $user->id)
                ->value('price_amount');
            if ($price !== null) {
                return $price;
            }
        }

        if ($segment !== \App\Domain\Commerce\Enums\CustomerSegment::Regular) {
            $price = \App\Models\ProductCustomPrice::query()
                ->where('product_id', $this->product_id)
                ->where('product_variant_id', $this->id)
                ->where('customer_segment', $segment->value)
                ->value('price_amount');
            if ($price !== null) {
                return $price;
            }
        }

        return $this->sale_price_amount ?? $this->regular_price_amount;
    }

    public function priceHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductPriceHistory::class, 'product_variant_id');
    }

    public function lowestPriceInLast30Days(): ?int
    {
        $price = $this->priceHistories()
            ->where('recorded_at', '>=', now()->subDays(30))
            ->min(\Illuminate\Support\Facades\DB::raw('COALESCE(sale_price_amount, regular_price_amount)'));

        if ($price !== null) {
            return (int) $price;
        }

        return $this->currentPriceAmount();
    }

    protected static function booted(): void
    {
        static::saved(function (ProductVariant $variant): void {
            if ($variant->wasRecentlyCreated || $variant->wasChanged('regular_price_amount') || $variant->wasChanged('sale_price_amount')) {
                $variant->priceHistories()->create([
                    'product_id' => $variant->product_id,
                    'regular_price_amount' => $variant->regular_price_amount,
                    'sale_price_amount' => $variant->sale_price_amount,
                    'recorded_at' => now(),
                ]);
            }
        });
    }
}
