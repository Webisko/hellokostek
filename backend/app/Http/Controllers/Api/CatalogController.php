<?php
 
namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPriceHistory;
use Illuminate\Http\JsonResponse;
 
class CatalogController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Product::query()
            ->select('products.*')
            ->selectRaw('COALESCE(
                (SELECT MIN(COALESCE(sale_price_amount, regular_price_amount)) FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.is_active = true),
                COALESCE(products.sale_price_amount, products.regular_price_amount)
            ) as computed_price')
            ->selectRaw('
                (CASE 
                    WHEN EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = products.id AND pv.is_active = true) 
                    THEN (
                        SELECT MIN(COALESCE(h.sale_price_amount, h.regular_price_amount)) 
                        FROM product_price_histories h 
                        WHERE h.product_id = products.id 
                          AND h.product_variant_id IN (SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = products.id AND pv2.is_active = true) 
                          AND h.recorded_at >= ?
                    ) 
                    ELSE (
                        SELECT MIN(COALESCE(h.sale_price_amount, h.regular_price_amount)) 
                        FROM product_price_histories h 
                        WHERE h.product_id = products.id 
                          AND h.product_variant_id IS NULL 
                          AND h.recorded_at >= ?
                    ) 
                END) as lowest_price_last_30_days
            ', [now()->subDays(30)->toDateTimeString(), now()->subDays(30)->toDateTimeString()])
            ->publicCatalog()
            ->with(['categories:id,name,slug', 'attributeValues.attribute', 'variants.optionValues.option'])
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->where('is_approved', true)])
            ->withAvg(['reviews as average_rating' => fn ($query) => $query->where('is_approved', true)], 'rating');

        // 1. Marketing filters
        if ($request->has('is_new')) {
            $query->where('is_new', $request->boolean('is_new'));
        }
        if ($request->has('is_bestseller')) {
            $query->where('is_bestseller', $request->boolean('is_bestseller'));
        }

        // 2. Category filter
        if ($request->filled('category')) {
            $categorySlug = $request->input('category');
            $query->whereHas('categories', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // 3. Text search
        if ($request->filled('query')) {
            $searchTerm = '%' . $request->input('query') . '%';
            $query->where(function ($q) use ($searchTerm, $request) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('short_description', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm)
                  ->orWhereJsonContains('manual_tags', $request->input('query'))
                  ->orWhere('sku', 'like', $searchTerm);
            });
        }

        // 4. Price range filtering
        if ($request->filled('price_min')) {
            $priceMin = (int) $request->input('price_min');
            $query->where(function ($q) use ($priceMin) {
                $q->where(function ($sq) use ($priceMin) {
                    $sq->whereDoesntHave('variants')
                       ->whereRaw('COALESCE(sale_price_amount, regular_price_amount) >= ?', [$priceMin]);
                })
                ->orWhereHas('variants', function ($vq) use ($priceMin) {
                    $vq->where('is_active', true)
                       ->whereRaw('COALESCE(sale_price_amount, regular_price_amount) >= ?', [$priceMin]);
                });
            });
        }

        if ($request->filled('price_max')) {
            $priceMax = (int) $request->input('price_max');
            $query->where(function ($q) use ($priceMax) {
                $q->where(function ($sq) use ($priceMax) {
                    $sq->whereDoesntHave('variants')
                       ->whereRaw('COALESCE(sale_price_amount, regular_price_amount) <= ?', [$priceMax]);
                })
                ->orWhereHas('variants', function ($vq) use ($priceMax) {
                    $vq->where('is_active', true)
                       ->whereRaw('COALESCE(sale_price_amount, regular_price_amount) <= ?', [$priceMax]);
                });
            });
        }

        // 5. Dynamic attributes filtering
        if ($request->has('attributes') && is_array($request->input('attributes'))) {
            foreach ($request->input('attributes') as $attrSlug => $value) {
                if (filled($value)) {
                    $query->whereHas('attributes', function ($q) use ($attrSlug, $value) {
                        $q->where('product_attributes.slug', $attrSlug);
                        if (is_array($value)) {
                            $q->whereIn('product_attribute_product.value', $value);
                        } else {
                            $q->where('product_attribute_product.value', $value);
                        }
                    });
                }
            }
        }

        // 6. Sorting
        $sort = $request->input('sort');
        if ($sort === 'price_asc') {
            $query->orderBy('computed_price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('computed_price', 'desc');
        } elseif ($sort === 'newest') {
            $query->orderBy('published_at', 'desc')->orderBy('created_at', 'desc');
        } elseif ($sort === 'popular') {
            $query->orderBy('approved_reviews_count', 'desc');
        } else {
            $query->orderBy('name');
        }

        // 7. Pagination
        $perPage = (int) $request->input('per_page', 24);
        $paginated = $query->paginate($perPage);

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => [
                'products' => collect($paginated->items())->map(fn (Product $product) => $this->productPayload($product))->all(),
                'categories' => $categories->map(fn (ProductCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ])->all(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'has_more_pages' => $paginated->hasMorePages(),
                ]
            ],
        ]);
    }
 
    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->select('products.*')
            ->selectRaw('
                (CASE 
                    WHEN EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = products.id AND pv.is_active = true) 
                    THEN (
                        SELECT MIN(COALESCE(h.sale_price_amount, h.regular_price_amount)) 
                        FROM product_price_histories h 
                        WHERE h.product_id = products.id 
                          AND h.product_variant_id IN (SELECT pv2.id FROM product_variants pv2 WHERE pv2.product_id = products.id AND pv2.is_active = true) 
                          AND h.recorded_at >= ?
                    ) 
                    ELSE (
                        SELECT MIN(COALESCE(h.sale_price_amount, h.regular_price_amount)) 
                        FROM product_price_histories h 
                        WHERE h.product_id = products.id 
                          AND h.product_variant_id IS NULL 
                          AND h.recorded_at >= ?
                    ) 
                END) as lowest_price_last_30_days
            ', [now()->subDays(30)->toDateTimeString(), now()->subDays(30)->toDateTimeString()])
            ->publicCatalog()
            ->with(['categories:id,name,slug', 'attributeValues.attribute', 'upsells', 'crossSells', 'similarProducts', 'variants.optionValues.option', 'bundleItems.product', 'bundleItems.variant.optionValues.option'])
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->where('is_approved', true)])
            ->withAvg(['reviews as average_rating' => fn ($query) => $query->where('is_approved', true)], 'rating')
            ->where('slug', $slug)
            ->firstOrFail();
 
         return response()->json([
             'data' => $this->productPayload($product),
         ]);
    }
 
    private function productPayload(Product $product): array
    {
        $baseUrl = rtrim((string) config('services.storefront.url', config('app.url')), '/');
        $productUrl = $baseUrl . '/products/' . $product->slug;
 
        $defaultLocale = config('app.locale', 'pl');
        $supportedLocales = [$defaultLocale, config('app.fallback_locale', 'en')];
        $hreflangs = [];
        foreach ($supportedLocales as $locale) {
            $langPrefix = $locale === $defaultLocale ? '' : '/' . $locale;
            $hreflangs[] = [
                'locale' => $locale,
                'url' => $baseUrl . $langPrefix . '/products/' . $product->slug,
            ];
        }

        $reviewsCount = isset($product->approved_reviews_count) 
            ? (int) $product->approved_reviews_count 
            : $product->approvedReviewsCount();
 
        $averageRating = isset($product->average_rating)
            ? round((float) $product->average_rating, 1)
            : $product->averageRating();
 
        $lowestPrice = isset($product->lowest_price_last_30_days)
            ? (int) $product->lowest_price_last_30_days
            : $product->lowestPriceInLast30Days();
          
        $storeName = app(\App\Support\StoreSettings::class)->model()->store_name ?? config('app.name');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->short_description ?: $product->seo_description,
            'sku' => $product->sku,
            'mpn' => $product->sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->metadata['brand'] ?? $storeName,
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format($product->currentPriceAmount() / 100, 2, '.', ''),
                'priceCurrency' => $product->currency ?: 'PLN',
                'priceValidUntil' => date('Y-12-31', strtotime('+1 year')),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => $product->stock_quantity > 0 || !$product->manages_stock 
                    ? 'https://schema.org/InStock' 
                    : 'https://schema.org/OutOfStock',
                'url' => $productUrl,
            ],
        ];
 
        $imageUrl = $product->featuredImageUrl();
        if ($imageUrl) {
            $schema['image'] = $imageUrl;
        }
 
        if ($reviewsCount > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $averageRating,
                'reviewCount' => (string) $reviewsCount,
            ];
        }
 
        // Open Graph fallback logic
        $ogTitle = $product->metadata['og_title'] ?? $product->seo_title ?? $product->name;
        
        $ogDescription = $product->metadata['og_description'] ?? $product->seo_description ?? $product->short_description;
        if (blank($ogDescription) && filled($product->description)) {
            $ogDescription = \Illuminate\Support\Str::limit(strip_tags($product->description), 160);
        }
        
        $ogImage = null;
        if (filled($product->metadata['og_image_path'] ?? null)) {
            $ogImage = \App\Support\PublicMediaUrl::resolve($product->metadata['og_image_path']);
        }
        if (blank($ogImage)) {
            $ogImage = $product->featuredImageUrl();
        }
        if (blank($ogImage)) {
            $gallery = $product->galleryImageUrls();
            if (!empty($gallery)) {
                $ogImage = $gallery[0];
            }
        }

        $dynamicOgImageUrl = null;
        if (app('router')->has('og-image')) {
            try {
                $dynamicOgImageUrl = \Illuminate\Support\Facades\URL::signedRoute('og-image', [
                    'title' => $product->name,
                    'subtitle' => strip_tags((string) ($product->short_description ?: 'Sklep internetowy')),
                    'badge' => $product->is_promoted ? 'Promocja' : ($product->is_new ? 'Nowość' : ''),
                ]);
            } catch (\Throwable $e) {
            }
        }
 
        $payload = [
            'id' => $product->id,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'type' => $product->type->value,
            'name' => $product->name,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'is_new' => (bool) $product->is_new,
            'is_bestseller' => (bool) $product->is_bestseller,
            'is_noindex' => (bool) $product->is_noindex,
            'weight' => $product->weight !== null ? (float) $product->weight : null,
            'is_ai_generated' => (bool) $product->is_ai_generated,
            'ai_disclosure_text' => $product->ai_disclosure_text,
            'hs_code' => $product->hs_code,
            'is_shipped_from_outside_eu' => (bool) $product->is_shipped_from_outside_eu,
            'gpsr_manufacturer_name' => $product->gpsr_manufacturer_name,
            'gpsr_manufacturer_address' => $product->gpsr_manufacturer_address,
            'gpsr_manufacturer_email' => $product->gpsr_manufacturer_email,
            'gpsr_responsible_name' => $product->gpsr_responsible_name,
            'gpsr_responsible_address' => $product->gpsr_responsible_address,
            'gpsr_responsible_email' => $product->gpsr_responsible_email,
            'gpsr_safety_warnings' => $product->gpsr_safety_warnings,
            'gpsr_document_url' => $product->gpsr_document_path ? \App\Support\PublicMediaUrl::resolve($product->gpsr_document_path) : null,
            'digital_compatibility' => $product->digital_compatibility,
            'digital_interoperability' => $product->digital_interoperability,
            'digital_drm' => $product->digital_drm,
            'digital_updates_info' => $product->digital_updates_info,
            'featured_image_url' => $product->featuredImageUrl(),
            'featured_image_alt' => $product->metadata['featured_image_alt'] ?? null,
            'hover_image_url' => $product->hoverImageUrl(),
            'gallery_image_urls' => $product->galleryImageUrls(),
            'currency' => $product->currency,
            'regular_price_amount' => $product->regular_price_amount,
            'sale_price_amount' => $product->sale_price_amount,
            'vat_rate' => $product->vat_rate ?? 23,
            'current_price_amount' => $product->currentPriceAmount(),
            'lowest_price_last_30_days' => $lowestPrice,
            'variants' => $product->variants->map(fn (\App\Models\ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'regular_price_amount' => $variant->regular_price_amount,
                'sale_price_amount' => $variant->sale_price_amount,
                'current_price_amount' => $variant->currentPriceAmount(),
                'lowest_price_last_30_days' => $variant->lowestPriceInLast30Days(),
                'stock_quantity' => $variant->stock_quantity,
                'manages_stock' => $variant->manages_stock,
                'is_active' => $variant->is_active,
                'options' => $variant->optionValues->map(fn ($ov) => [
                    'option_name' => $ov->option?->name,
                    'value' => $ov->value,
                ])->all(),
            ])->all(),
            'average_rating' => $averageRating,
            'reviews_count' => $reviewsCount,
            'seo_title' => $product->seo_title,
            'seo_description' => $product->seo_description,
            'canonical_url' => $productUrl,
            'hreflangs' => $hreflangs,
            'social_meta' => [
                'og:title' => $ogTitle,
                'og:description' => $ogDescription,
                'og:image' => $ogImage ?: $dynamicOgImageUrl,
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $ogTitle,
                'twitter:description' => $ogDescription,
                'twitter:image' => $ogImage ?: $dynamicOgImageUrl,
            ],
            'dynamic_og_image_url' => $dynamicOgImageUrl,
            'published_at' => optional($product->published_at)->toIso8601String(),
            'metadata' => $product->metadata ?? [],
            'specs' => $product->metadata['specs'] ?? [],
            'features' => array_values($product->metadata['specs'] ?? []),
            'schema_json_ld' => $schema,
            'manual_tags' => $product->manual_tags ?? [],
            'badges' => $product->marketingBadgeLabels(),
            'homepage_flags' => $product->homepagePlacementLabels(),
            'categories' => $product->categories->map(fn (ProductCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->all(),
            'attributes' => $product->attributeValues
                ->map(fn ($assignment): ?array => filled($assignment->attribute?->name) ? [
                    'id' => $assignment->attribute->id,
                    'slug' => $assignment->attribute->slug,
                    'name' => $assignment->attribute->name,
                    'type' => $assignment->attribute->value_type,
                    'value' => $assignment->value,
                ] : null)
                ->filter(fn (?array $attribute): bool => is_array($attribute))
                ->values()
                ->all(),
        ];

        if ($product->relationLoaded('upsells')) {
            $payload['upsells'] = $product->upsells->map(fn (Product $p) => $this->relatedProductPayload($p))->all();
        }
        if ($product->relationLoaded('crossSells')) {
            $payload['cross_sells'] = $product->crossSells->map(fn (Product $p) => $this->relatedProductPayload($p))->all();
        }
        if ($product->relationLoaded('similarProducts')) {
            $payload['similar_products'] = $product->similarProducts->map(fn (Product $p) => $this->relatedProductPayload($p))->all();
        }

        if ($product->isBundle()) {
            $payload['bundle_items'] = $product->bundleItems->map(fn ($item) => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'product' => $item->product ? $this->relatedProductPayload($item->product) : null,
                'product_variant_id' => $item->product_variant_id,
                'variant' => $item->variant ? [
                    'id' => $item->variant->id,
                    'sku' => $item->variant->sku,
                    'regular_price_amount' => $item->variant->regular_price_amount,
                    'sale_price_amount' => $item->variant->sale_price_amount,
                    'current_price_amount' => $item->variant->currentPriceAmount(),
                    'options' => $item->variant->optionValues->map(fn ($ov) => [
                        'name' => $ov->option?->name,
                        'value' => $ov->value,
                    ])->all(),
                ] : null,
            ])->filter(fn ($item) => $item['product'] !== null)->values()->all();

            $componentsPriceSum = 0;
            foreach ($product->bundleItems as $item) {
                if ($item->product_variant_id && $item->variant) {
                    $componentsPriceSum += $item->variant->currentPriceAmount() * $item->quantity;
                } elseif ($item->product) {
                    $componentsPriceSum += $item->product->currentPriceAmount() * $item->quantity;
                }
            }
            $payload['components_price_sum'] = $componentsPriceSum;
        } else {
            $suggestedBundles = Product::query()
                ->publicCatalog()
                ->where('type', \App\Domain\Commerce\Enums\ProductType::Bundle->value)
                ->whereHas('bundleItems', fn($q) => $q->where('product_id', $product->id))
                ->with(['bundleItems.product', 'bundleItems.variant.optionValues.option'])
                ->get();

            $payload['suggested_bundles'] = $suggestedBundles->map(function (Product $bundle) {
                $componentsPriceSum = 0;
                foreach ($bundle->bundleItems as $item) {
                    if ($item->product_variant_id && $item->variant) {
                        $componentsPriceSum += $item->variant->currentPriceAmount() * $item->quantity;
                    } elseif ($item->product) {
                        $componentsPriceSum += $item->product->currentPriceAmount() * $item->quantity;
                    }
                }
                return [
                    'id' => $bundle->id,
                    'slug' => $bundle->slug,
                    'sku' => $bundle->sku,
                    'name' => $bundle->name,
                    'current_price_amount' => $bundle->currentPriceAmount(),
                    'regular_price_amount' => $bundle->regular_price_amount,
                    'sale_price_amount' => $bundle->sale_price_amount,
                    'featured_image_url' => $bundle->featuredImageUrl(),
                    'components_price_sum' => $componentsPriceSum,
                    'bundle_items' => $bundle->bundleItems->map(fn ($item) => [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'product' => $item->product ? $this->relatedProductPayload($item->product) : null,
                        'product_variant_id' => $item->product_variant_id,
                        'variant' => $item->variant ? [
                            'id' => $item->variant->id,
                            'sku' => $item->variant->sku,
                            'regular_price_amount' => $item->variant->regular_price_amount,
                            'sale_price_amount' => $item->variant->sale_price_amount,
                            'current_price_amount' => $item->variant->currentPriceAmount(),
                            'options' => $item->variant->optionValues->map(fn ($ov) => [
                                'name' => $ov->option?->name,
                                'value' => $ov->value,
                            ])->all(),
                        ] : null,
                    ])->filter(fn ($item) => $item['product'] !== null)->values()->all(),
                ];
            })->all();
        }

        return $payload;
    }

    public function suggest(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = trim($request->input('query', ''));
        if (strlen($query) < 2) {
            return response()->json([
                'suggestions' => [],
                'products' => [],
                'categories' => [],
            ]);
        }

        $searchTerm = '%' . $query . '%';

        // 1. Find matching active products
        $products = Product::query()
            ->publicCatalog()
            ->where(function ($q) use ($searchTerm, $query) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('sku', 'like', $searchTerm)
                  ->orWhereJsonContains('manual_tags', $query);
            })
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'sku' => $p->sku,
                'featured_image_url' => $p->featuredImageUrl(),
                'price_amount' => $p->currentPriceAmount(),
            ]);

        // 2. Find matching categories
        $categories = ProductCategory::query()
            ->where('name', 'like', $searchTerm)
            ->limit(3)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ]);

        // 3. Generate suggestions
        $suggestions = [];
        $matchingNames = Product::query()
            ->publicCatalog()
            ->where('name', 'like', $searchTerm)
            ->limit(5)
            ->pluck('name');
            
        foreach ($matchingNames as $name) {
            $suggestions[] = $name;
        }

        $allTags = Product::query()
            ->publicCatalog()
            ->whereNotNull('manual_tags')
            ->pluck('manual_tags')
            ->flatten()
            ->unique();
            
        foreach ($allTags as $tag) {
            if (stripos($tag, $query) !== false) {
                $suggestions[] = $tag;
            }
        }

        return response()->json([
            'suggestions' => array_values(array_slice(array_unique($suggestions), 0, 5)),
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function recommendations(string $slug, \Illuminate\Http\Request $request): JsonResponse
    {
        $product = Product::query()
            ->publicCatalog()
            ->where('slug', $slug)
            ->firstOrFail();

        // Manual recommendations
        $upsells = $product->upsells()->limit(4)->get();
        $crossSells = $product->crossSells()->limit(4)->get();
        $similar = $product->similarProducts()->limit(4)->get();

        // Collaborative Filtering ("Customers who bought this product also bought...")
        $alsoBoughtIds = \App\Models\OrderItem::query()
            ->whereIn('order_id', function ($q) use ($product) {
                $q->select('order_id')
                  ->from('order_items')
                  ->where('product_id', $product->id);
            })
            ->where('product_id', '!=', $product->id)
            ->select('product_id')
            ->selectRaw('COUNT(*) as cooccurrence_count')
            ->groupBy('product_id')
            ->orderByDesc('cooccurrence_count')
            ->limit(4)
            ->pluck('product_id');

        $alsoBought = Product::query()
            ->publicCatalog()
            ->whereIn('id', $alsoBoughtIds)
            ->get();

        // Same category popular recommendations
        $categoryIds = $product->categories->pluck('id');
        $sameCategory = Product::query()
            ->publicCatalog()
            ->where('id', '!=', $product->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('id', $categoryIds))
            ->orderByDesc('is_bestseller')
            ->limit(4)
            ->get();

        $mapProducts = fn ($collection) => $collection->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'sku' => $p->sku,
            'featured_image_url' => $p->featuredImageUrl(),
            'price_amount' => $p->currentPriceAmount(),
        ]);

        return response()->json([
            'upsells' => $mapProducts($upsells),
            'cross_sells' => $mapProducts($crossSells),
            'similar_products' => $mapProducts($similar),
            'also_bought' => $mapProducts($alsoBought),
            'same_category' => $mapProducts($sameCategory),
        ]);
    }

    private function relatedProductPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'name' => $product->name,
            'current_price_amount' => $product->currentPriceAmount(),
            'regular_price_amount' => $product->regular_price_amount,
            'sale_price_amount' => $product->sale_price_amount,
            'featured_image_url' => $product->featuredImageUrl(),
        ];
    }
}
