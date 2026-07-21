<?php

namespace App\Models;

use App\Domain\Commerce\Enums\ProductType;
use App\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'short_description', 'description'];

    protected $fillable = [
        'slug',
        'sku',
        'type',
        'name',
        'short_description',
        'description',
        'featured_image_path',
        'hover_image_path',
        'gallery_image_paths',
        'currency',
        'regular_price_amount',
        'sale_price_amount',
        'vat_rate',
        'stock_quantity',
        'manages_stock',
        'is_active',
        'is_visible',
        'is_purchasable',
        'is_new',
        'is_bestseller',
        'is_recommended',
        'is_promoted',
        'is_seasonal',
        'is_clearance',
        'show_on_homepage',
        'show_in_bestsellers',
        'show_in_new_arrivals',
        'show_in_recommended',
        'seo_title',
        'seo_description',
        'published_at',
        'metadata',
        'manual_tags',
        'is_noindex',
        'is_ai_generated',
        'ai_disclosure_text',
        'weight',
        'hs_code',
        'is_shipped_from_outside_eu',
        'gpsr_manufacturer_name',
        'gpsr_manufacturer_address',
        'gpsr_manufacturer_email',
        'gpsr_responsible_name',
        'gpsr_responsible_address',
        'gpsr_responsible_email',
        'gpsr_safety_warnings',
        'gpsr_document_path',
        'digital_compatibility',
        'digital_interoperability',
        'digital_drm',
        'digital_updates_info',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'vat_rate' => 'integer',
            'manages_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_new' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_recommended' => 'boolean',
            'is_promoted' => 'boolean',
            'is_seasonal' => 'boolean',
            'is_clearance' => 'boolean',
            'show_on_homepage' => 'boolean',
            'show_in_bestsellers' => 'boolean',
            'show_in_new_arrivals' => 'boolean',
            'show_in_recommended' => 'boolean',
            'published_at' => 'datetime',
            'metadata' => 'array',
            'manual_tags' => 'array',
            'gallery_image_paths' => 'array',
            'is_noindex' => 'boolean',
            'is_ai_generated' => 'boolean',
            'is_shipped_from_outside_eu' => 'boolean',
            'weight' => 'float',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class)->withTimestamps()->orderBy('sort_order');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\ProductAttribute::class, 'product_attribute_product')
            ->withPivot(['value', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(\App\Models\ProductAttributeAssignment::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }



    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('is_visible', true)
            ->where('is_purchasable', true)
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function currentPriceAmount(?User $user = null, ?\App\Domain\Commerce\Enums\CustomerSegment $segment = null): int
    {
        $user = $user ?? auth('sanctum')->user() ?? auth()->user();
        if ($segment === null && $user) {
            $segment = $user->segment();
        }
        $segment = $segment ?? \App\Domain\Commerce\Enums\CustomerSegment::Regular;

        // Resolve custom price for this product
        if ($user) {
            $price = \App\Models\ProductCustomPrice::query()
                ->where('product_id', $this->id)
                ->whereNull('product_variant_id')
                ->where('user_id', $user->id)
                ->value('price_amount');
            if ($price !== null) {
                return $price;
            }
        }

        if ($segment !== \App\Domain\Commerce\Enums\CustomerSegment::Regular) {
            $price = \App\Models\ProductCustomPrice::query()
                ->where('product_id', $this->id)
                ->whereNull('product_variant_id')
                ->where('customer_segment', $segment->value)
                ->value('price_amount');
            if ($price !== null) {
                return $price;
            }
        }

        if ($this->relationLoaded('variants')) {
            $activeVariants = $this->variants->filter(fn($v) => $v->is_active);
            if ($activeVariants->isNotEmpty()) {
                return (int) $activeVariants->map(fn($v) => $v->currentPriceAmount($user, $segment))->min();
            }
        } else {
            $minVariantPrice = $this->variants()->where('is_active', true)->get()->map(fn($v) => $v->currentPriceAmount($user, $segment))->min();
            if ($minVariantPrice !== null) {
                return (int) $minVariantPrice;
            }
        }
        return $this->sale_price_amount ?? $this->regular_price_amount;
    }

    public function featuredImageUrl(): ?string
    {
        return PublicMediaUrl::resolve($this->featured_image_path);
    }

    public function hoverImageUrl(): ?string
    {
        return PublicMediaUrl::resolve($this->hover_image_path);
    }

    public function attributeValuesSummary(): array
    {
        return $this->attributeValues
            ->map(function (\App\Models\ProductAttributeAssignment $assignment): ?string {
                $name = $assignment->attribute?->name;
                $value = filled($assignment->value) ? (string) $assignment->value : null;

                if (! filled($name) && ! filled($value)) {
                    return null;
                }

                if (! filled($name)) {
                    return $value;
                }

                return filled($value)
                    ? sprintf('%s: %s', $name, $value)
                    : (string) $name;
            })
            ->filter(fn (?string $value): bool => filled($value))
            ->values()
            ->all();
    }

    public function marketingBadgeLabels(): array
    {
        return collect([
            'Nowosc' => $this->is_new,
            'Bestseller' => $this->is_bestseller,
            'Polecany' => $this->is_recommended,
            'Promocja' => $this->is_promoted,
            'Sezonowy' => $this->is_seasonal,
            'Wyprzedaz' => $this->is_clearance,
        ])
            ->filter(fn (bool $enabled): bool => $enabled)
            ->keys()
            ->values()
            ->all();
    }

    public function homepagePlacementLabels(): array
    {
        return collect([
            'Strona glowna' => $this->show_on_homepage,
            'Sekcja bestsellerow' => $this->show_in_bestsellers,
            'Sekcja nowosci' => $this->show_in_new_arrivals,
            'Sekcja polecanych' => $this->show_in_recommended,
        ])
            ->filter(fn (bool $enabled): bool => $enabled)
            ->keys()
            ->values()
            ->all();
    }

    public function getAttributeValuesSummaryAttribute(): array
    {
        return $this->attributeValuesSummary();
    }

    public function getMarketingBadgeLabelsAttribute(): array
    {
        return $this->marketingBadgeLabels();
    }

    public function getHomepagePlacementLabelsAttribute(): array
    {
        return $this->homepagePlacementLabels();
    }

    protected static function booted(): void
    {
        static::updating(function (Product $product): void {
            if ($product->isDirty('slug')) {
                $oldSlug = $product->getOriginal('slug');
                $newSlug = $product->slug;

                if (filled($oldSlug) && filled($newSlug) && $oldSlug !== $newSlug) {
                    $oldPath = '/products/' . $oldSlug;
                    $newPath = '/products/' . $newSlug;

                    \App\Models\RedirectRule::updateOrCreate(
                        ['source_path' => $oldPath],
                        [
                            'target_path' => $newPath,
                            'status_code' => 301,
                            'is_active' => true,
                        ]
                    );

                    \App\Models\RedirectRule::query()
                        ->where('target_path', $oldPath)
                        ->update(['target_path' => $newPath]);
                }
            }
        });

        static::saved(function (Product $product): void {
            if ($product->wasRecentlyCreated || $product->wasChanged('regular_price_amount') || $product->wasChanged('sale_price_amount')) {
                $product->priceHistories()->create([
                    'regular_price_amount' => $product->regular_price_amount,
                    'sale_price_amount' => $product->sale_price_amount,
                    'recorded_at' => now(),
                ]);
            }
        });
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function lowestPriceInLast30Days(): ?int
    {
        if ($this->variants()->where('is_active', true)->exists()) {
            $activeVariants = $this->variants->filter(fn($v) => $v->is_active);
            if ($activeVariants->isNotEmpty()) {
                return (int) $activeVariants->map(fn($v) => $v->lowestPriceInLast30Days())->min();
            }
        }

        $price = $this->priceHistories()
            ->whereNull('product_variant_id')
            ->where('recorded_at', '>=', now()->subDays(30))
            ->min(\Illuminate\Support\Facades\DB::raw('COALESCE(sale_price_amount, regular_price_amount)'));

        if ($price !== null) {
            return (int) $price;
        }

        return $this->currentPriceAmount();
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_product_id')
            ->withPivot(['relation_type', 'sort_order'])
            ->withTimestamps();
    }

    public function productRelations(): HasMany
    {
        return $this->hasMany(ProductRelation::class, 'product_id');
    }

    public function customPrices(): HasMany
    {
        return $this->hasMany(ProductCustomPrice::class);
    }

    public function upsells(): BelongsToMany
    {
        return $this->relatedProducts()->wherePivot('relation_type', 'upsell')->orderByPivot('sort_order');
    }

    public function crossSells(): BelongsToMany
    {
        return $this->relatedProducts()->wherePivot('relation_type', 'cross_sell')->orderByPivot('sort_order');
    }

    public function similarProducts(): BelongsToMany
    {
        return $this->relatedProducts()->wherePivot('relation_type', 'similar')->orderByPivot('sort_order');
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_product_id')->orderBy('sort_order');
    }

    public function bundleProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_bundle_items', 'bundle_product_id', 'product_id')
            ->withPivot(['quantity', 'sort_order'])
            ->withTimestamps();
    }

    public function isBundle(): bool
    {
        return $this->type === ProductType::Bundle;
    }

    public function getDynamicStockQuantity(): ?int
    {
        if (!$this->isBundle()) {
            return $this->manages_stock ? $this->stock_quantity : null;
        }

        $items = $this->bundleItems;
        if ($items->isEmpty()) {
            return 0;
        }

        $minStock = null;
        $anyComponentManagesStock = false;

        foreach ($items as $item) {
            $component = $item->product_variant_id ? $item->variant : $item->product;
            if (!$component) {
                continue;
            }

            if ($component->manages_stock) {
                $anyComponentManagesStock = true;
                $available = (int) ($component->stock_quantity ?? 0);
                $possibleBundles = (int) floor($available / $item->quantity);
                if ($minStock === null || $possibleBundles < $minStock) {
                    $minStock = $possibleBundles;
                }
            }
        }

        return $anyComponentManagesStock ? ($minStock ?? 0) : null;
    }

    public function managesStock(): bool
    {
        if ($this->isBundle()) {
            return $this->bundleItems->contains(function ($item) {
                if ($item->product_variant_id) {
                    return (bool) $item->variant?->manages_stock;
                }
                return (bool) $item->product?->manages_stock;
            });
        }
        return (bool) $this->manages_stock;
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function averageRating(): float
    {
        return round((float) ($this->reviews()->where('is_approved', true)->avg('rating') ?? 0.0), 1);
    }

    public function approvedReviewsCount(): int
    {
        return (int) $this->reviews()->where('is_approved', true)->count();
    }

    public function galleryImageUrls(): array
    {
        $paths = $this->gallery_image_paths ?? [];
        if (! is_array($paths)) {
            return [];
        }

        return collect($paths)
            ->map(fn (string $path) => PublicMediaUrl::resolve($path))
            ->filter()
            ->values()
            ->all();
    }

    public static function syncVariantsFromData(Product $product, array $data): void
    {
        $sku = $product->sku ?: $product->slug;

        // 1. Wydruk (-PR)
        $printPrice = null;
        if (isset($data['print_regular_price']) && $data['print_regular_price'] !== null && $data['print_regular_price'] !== '') {
            $printPrice = (int) round(((float) $data['print_regular_price']) * 100);
        } elseif ($product->regular_price_amount) {
            $printPrice = $product->regular_price_amount;
        }

        $printStock = isset($data['print_stock_quantity']) && $data['print_stock_quantity'] !== null && $data['print_stock_quantity'] !== ''
            ? (int) $data['print_stock_quantity']
            : 999;

        if ($printPrice !== null && $printPrice > 0) {
            $product->updateQuietly([
                'regular_price_amount' => $printPrice,
            ]);

            $printSku = Str::endsWith($sku, '-PR') ? $sku : "{$sku}-PR";
            $product->variants()->updateOrCreate(
                ['sku' => $printSku],
                [
                    'regular_price_amount' => $printPrice,
                    'vat_rate' => $product->vat_rate ?? 23,
                    'stock_quantity' => $printStock,
                    'manages_stock' => true,
                    'is_active' => true,
                ]
            );
        }

        // 2. Praca Oryginalna (-OR)
        $hasOriginal = !empty($data['has_original']);
        $originalSku = Str::endsWith($sku, '-OR') ? $sku : "{$sku}-OR";

        if ($hasOriginal) {
            $originalPrice = isset($data['original_regular_price']) && $data['original_regular_price'] !== null && $data['original_regular_price'] !== ''
                ? (int) round(((float) $data['original_regular_price']) * 100)
                : 0;

            $originalStock = isset($data['original_stock_quantity']) && $data['original_stock_quantity'] !== null && $data['original_stock_quantity'] !== ''
                ? (int) $data['original_stock_quantity']
                : 1;

            if ($originalPrice > 0) {
                $product->variants()->updateOrCreate(
                    ['sku' => $originalSku],
                    [
                        'regular_price_amount' => $originalPrice,
                        'vat_rate' => $product->vat_rate ?? 23,
                        'stock_quantity' => $originalStock,
                        'manages_stock' => true,
                        'is_active' => true,
                    ]
                );
            }
        } else {
            $product->variants()->where('sku', $originalSku)->update([
                'is_active' => false,
                'stock_quantity' => 0,
            ]);
        }
    }
}
