<?php

namespace App\Domain\Commerce\Checkout;

use App\Domain\Commerce\Checkout\Data\ResolvedCheckoutData;
use App\Domain\Commerce\Enums\CustomerSegment;
use App\Domain\Commerce\Inventory\InventoryAvailabilityService;
use App\Domain\Commerce\Pricing\Data\QuoteData;
use App\Domain\Commerce\Pricing\Data\QuoteItemData;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Support\StoreSettings;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutDataResolver
{
    public function __construct(
        private readonly StoreSettings $storeSettings,
        private readonly InventoryAvailabilityService $inventoryAvailabilityService,
    ) {
    }

    public function resolve(array $validated, bool $requireFreshInventory = false): ResolvedCheckoutData
    {
        $slugs = array_map(static fn (array $item) => $item['slug'] ?? '', $validated['items']);
        $products = Product::query()
            ->publicCatalog()
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');
        $hasPhysicalProducts = $products->contains(fn ($p) => $p->type->value === 'physical');

        $user = auth('sanctum')->user() ?? auth()->user();
        $customerSegment = CustomerSegment::Regular;
        if (isset($validated['customer_segment'])) {
            $customerSegment = CustomerSegment::from($validated['customer_segment']);
        } elseif ($user) {
            $customerSegment = $user->segment();
        }

        $quoteItems = array_map(
            fn (array $item, int $index): QuoteItemData => $this->resolveQuoteItem(
                item: $item,
                products: $products,
                requireFreshInventory: $requireFreshInventory,
                itemIndex: $index,
                user: $user,
                customerSegment: $customerSegment,
            ),
            $validated['items'],
            array_keys($validated['items']),
        );

        $cartWeight = 0.0;
        $cartValue = 0;
        foreach ($quoteItems as $item) {
            $cartValue += $item->unitPriceAmount * $item->quantity;
            $product = $products->first(fn ($p) => $p->id === $item->productId);
            if ($product && $product->type->value === 'physical') {
                $cartWeight += ((float) ($product->weight ?? 0.0)) * $item->quantity;
            }
        }

        $shippingAddress = $validated['shipping_address'] ?? ($validated['billing_address'] ?? null);
        $countryCode = null;
        if (is_array($shippingAddress)) {
            $countryCode = $shippingAddress['country_code'] ?? $shippingAddress['country'] ?? null;
        }

        if ($countryCode) {
            $countryCode = strtoupper(trim($countryCode));
            if ($countryCode === 'POLSKA' || $countryCode === 'POL') {
                $countryCode = 'PL';
            }

            // Only validate country code restriction for physical products
            if ($hasPhysicalProducts) {
                $zones = $this->storeSettings->shippingZones();
                $isCountrySupported = false;
                if (empty($zones)) {
                    if (in_array($countryCode, ['PL', 'POLSKA', 'POL'], true)) {
                        $isCountrySupported = true;
                    }
                } else {
                    foreach ($zones as $zone) {
                        $countriesInput = $zone['countries'] ?? [];
                        $zoneCountries = [];
                        if (is_array($countriesInput)) {
                            $zoneCountries = $countriesInput;
                        } elseif (is_string($countriesInput)) {
                            $zoneCountries = array_map('trim', explode(',', $countriesInput));
                        }
                        $zoneCountries = array_map('strtoupper', $zoneCountries);
                        if (in_array($countryCode, $zoneCountries, true)) {
                            $isCountrySupported = true;
                            break;
                        }
                    }
                }

                if (! $isCountrySupported) {
                    throw ValidationException::withMessages([
                        'shipping_address.country_code' => 'Wysyłka do wybranego kraju jest niedostępna.',
                    ]);
                }
            }
        } else {
            $countryCode = 'PL';
        }

        $shippingMethodCode = $validated['shipping_method_code'] ?? null;
        $shippingMethod = $shippingMethodCode
            ? $this->storeSettings->shippingMethodForCountry($shippingMethodCode, $countryCode, $cartWeight, $cartValue)
            : null;

        if ($shippingMethodCode && ! is_array($shippingMethod)) {
            throw ValidationException::withMessages([
                'shipping_method_code' => 'Wysyłka do wybranego kraju jest niedostępna.',
            ]);
        }

        if ((bool) Arr::get($shippingMethod, 'requires_delivery_point', false) && blank(data_get($validated, 'delivery_point.id'))) {
            throw ValidationException::withMessages([
                'delivery_point' => 'Punkt odbioru jest wymagany dla wybranej metody wysylki.',
            ]);
        }

        $coupon = $this->resolveCoupon($validated['coupon_code'] ?? null, $validated);

        $requestedCurrency = $validated['currency'] ?? null;
        $currency = $requestedCurrency ? strtoupper(trim($requestedCurrency)) : ($products->first()?->currency ?? $this->storeSettings->currency());

        return new ResolvedCheckoutData(
            quoteItems: $quoteItems,
            quote: new QuoteData(
                items: $quoteItems,
                customerSegment: $customerSegment,
                coupon: $coupon,
                shippingMethodCode: $shippingMethodCode,
                shippingCountryCode: $countryCode,
                currency: $currency,
            ),
            coupon: $coupon,
            shippingMethodCode: $shippingMethodCode,
            shippingMethodName: is_array($shippingMethod) ? Arr::get($shippingMethod, 'name') : null,
            currency: $currency,
        );
    }

    private function logFailedCouponAttempt(?string $couponCode, string $reason, array $validated): void
    {
        \Illuminate\Support\Facades\Log::warning('Próba użycia niepoprawnego kuponu', [
            'coupon_code' => $couponCode,
            'reason' => $reason,
            'customer_email' => data_get($validated, 'customer.email'),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function resolveCoupon(?string $couponCode, array $validated): ?Coupon
    {
        if (! $couponCode) {
            return null;
        }

        $coupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [Str::lower($couponCode)])
            ->first();

        if (! $coupon) {
            $this->logFailedCouponAttempt($couponCode, 'Nie znaleziono kuponu', $validated);
            throw ValidationException::withMessages([
                'coupon_code' => 'Nie znaleziono kuponu.',
            ]);
        }

        if (! $coupon->is_active) {
            $this->logFailedCouponAttempt($couponCode, 'Kupon jest nieaktywny', $validated);
            throw ValidationException::withMessages([
                'coupon_code' => 'Ten kupon jest nieaktywny.',
            ]);
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            $this->logFailedCouponAttempt($couponCode, 'Kupon jeszcze nie jest aktywny', $validated);
            throw ValidationException::withMessages([
                'coupon_code' => 'Ten kupon jeszcze nie jest aktywny.',
            ]);
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            $this->logFailedCouponAttempt($couponCode, 'Kupon stracił ważność', $validated);
            throw ValidationException::withMessages([
                'coupon_code' => 'Ten kupon stracił ważność.',
            ]);
        }

        if ($coupon->usage_limit !== null) {
            $usedCount = \App\Models\Order::query()
                ->where('coupon_id', $coupon->id)
                ->whereIn('status', ['placed', 'completed'])
                ->count();
            if ($usedCount >= $coupon->usage_limit) {
                $this->logFailedCouponAttempt($couponCode, 'Przekroczony globalny limit użycia', $validated);
                throw ValidationException::withMessages([
                    'coupon_code' => 'Kupon został już wykorzystany maksymalną liczbę razy.',
                ]);
            }
        }

        if ($coupon->usage_limit_per_customer !== null) {
            $customerEmail = data_get($validated, 'customer.email');
            if ($customerEmail) {
                $usedByCustomerCount = \App\Models\Order::query()
                    ->where('coupon_id', $coupon->id)
                    ->where('customer_email', $customerEmail)
                    ->whereIn('status', ['placed', 'completed'])
                    ->count();
                if ($usedByCustomerCount >= $coupon->usage_limit_per_customer) {
                    $this->logFailedCouponAttempt($couponCode, 'Przekroczony limit użycia na klienta', $validated);
                    throw ValidationException::withMessages([
                        'coupon_code' => 'Wykorzystałeś już ten kupon maksymalną liczbę razy.',
                    ]);
                }
            }
        }

        return $coupon;
    }

    private function resolveQuoteItem(
        array $item,
        Collection $products,
        bool $requireFreshInventory,
        int $itemIndex,
        ?User $user,
        CustomerSegment $customerSegment,
    ): QuoteItemData
    {
        /** @var Product|null $product */
        $product = $products->get($item['slug']);

        if (! $product) {
            throw ValidationException::withMessages([
                'items.' . $itemIndex . '.slug' => 'Produkt "' . ($item['slug'] ?? '') . '" nie jest dostępny w sklepie.',
            ]);
        }

        $variantId = $item['product_variant_id'] ?? $item['variant_id'] ?? null;
        $variant = null;

        if ($variantId) {
            $variant = \App\Models\ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->find($variantId);

            if (!$variant) {
                throw ValidationException::withMessages([
                    'items.' . $itemIndex . '.variant_id' => 'Wybrany wariant produktu jest niedostępny.',
                ]);
            }
        }

        if ($variant) {
            $this->assertVariantCanFulfill($variant, (int) $item['quantity'], $itemIndex);
        } else {
            $this->inventoryAvailabilityService->assertCanFulfill(
                product: $product,
                requestedQuantity: (int) $item['quantity'],
                requireFreshInventory: $requireFreshInventory,
                itemIndex: $itemIndex,
            );
        }

        $unitPrice = $variant 
            ? $variant->currentPriceAmount($user, $customerSegment) 
            : $product->currentPriceAmount($user, $customerSegment);
            
        // Check if custom price exists
        $hasCustomPrice = false;
        if ($user) {
            $hasCustomPrice = \App\Models\ProductCustomPrice::query()
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant?->id)
                ->where('user_id', $user->id)
                ->exists();
        }
        if (!$hasCustomPrice && $customerSegment !== CustomerSegment::Regular) {
            $hasCustomPrice = \App\Models\ProductCustomPrice::query()
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant?->id)
                ->where('customer_segment', $customerSegment->value)
                ->exists();
        }

        $regularUnitPrice = $hasCustomPrice 
            ? $unitPrice 
            : ($variant ? $variant->regular_price_amount : $product->regular_price_amount);

        $name = $product->name;
        
        if ($variant) {
            $optionsStr = $variant->optionValues->map(fn($val) => $val->value)->implode(', ');
            if ($optionsStr !== '') {
                $name .= ' (' . $optionsStr . ')';
            }
        }

        $quoteItemData = new QuoteItemData(
            productId: $product->id,
            slug: $product->slug,
            name: $name,
            productType: $product->type,
            quantity: (int) $item['quantity'],
            unitPriceAmount: $unitPrice,
            regularUnitPriceAmount: $regularUnitPrice,
            productVariantId: $variantId ? (int) $variantId : null,
        );

        return $quoteItemData;
    }

    private function assertVariantCanFulfill(\App\Models\ProductVariant $variant, int $requestedQuantity, int $itemIndex): void
    {
        $errorKey = 'items.' . $itemIndex . '.quantity';
        if ($variant->manages_stock && $variant->stock_quantity !== null && $requestedQuantity > $variant->stock_quantity) {
            throw ValidationException::withMessages([
                $errorKey => 'Brak wystarczającego stanu magazynowego dla wybranego wariantu: ' . $variant->sku,
            ]);
        }
    }
}