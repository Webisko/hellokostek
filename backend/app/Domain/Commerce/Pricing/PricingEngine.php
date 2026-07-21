<?php

namespace App\Domain\Commerce\Pricing;

use App\Domain\Commerce\Enums\CustomerSegment;
use App\Domain\Commerce\Enums\ProductType;
use App\Domain\Commerce\Pricing\Data\QuoteData;
use App\Domain\Commerce\Pricing\Data\QuoteResult;
use App\Models\Coupon;
use App\Support\StoreSettings;
use Illuminate\Support\Arr;

class PricingEngine
{
    public function __construct(
        private readonly StoreSettings $storeSettings,
    ) {
    }

    public function calculate(QuoteData $quote): QuoteResult
    {
        $targetCurrency = $quote->currency ?? $this->storeSettings->currency();
        $defaultCurrency = $this->storeSettings->currency();

        $exchangeRate = 1.0;
        if ($targetCurrency !== $defaultCurrency) {
            $rates = $this->storeSettings->exchangeRates();
            $exchangeRate = (float) ($rates[$targetCurrency] ?? 1.0);
        }

        $convert = fn (int $amount) => (int) round($amount * $exchangeRate);

        $lineSubtotals = [];
        $lineRegularSubtotals = [];
        foreach ($quote->items as $index => $item) {
            $convertedUnitPrice = $convert($item->unitPriceAmount);
            $convertedRegularPrice = $convert($item->regularUnitPriceAmount);
            
            $lineSubtotals[$index] = $convertedUnitPrice * $item->quantity;
            $lineRegularSubtotals[$index] = $convertedRegularPrice * $item->quantity;
        }

        $subtotalAmount = array_sum($lineSubtotals);
        $regularSubtotalAmount = array_sum($lineRegularSubtotals);

        $lineCouponDiscounts = $this->resolveCouponDiscounts($quote->coupon, $quote->items, $subtotalAmount, $exchangeRate);
        $couponDiscountAmount = array_sum($lineCouponDiscounts);

        $lineAmountsAfterCoupon = [];
        foreach ($quote->items as $index => $item) {
            $lineAmountsAfterCoupon[$index] = max(0, $lineSubtotals[$index] - $lineCouponDiscounts[$index]);
        }

        $subtotalAfterCouponAmount = array_sum($lineAmountsAfterCoupon);

        $loyaltyDiscountAmount = $this->resolveLoyaltyDiscount($quote->customerSegment, $subtotalAfterCouponAmount, $quote->items, $lineAmountsAfterCoupon);
        $wholesaleDiscountAmount = $this->resolveWholesaleDiscount($quote->customerSegment, $quote->items, $lineAmountsAfterCoupon, $exchangeRate);

        $shippingAmount = $this->resolveShippingAmount($quote, $subtotalAfterCouponAmount, $exchangeRate);

        // Import duty calculation (3 EUR flat duty per HS code group for EU B2C imports from outside EU)
        $importDutyAmount = 0;
        $shippingCountry = strtoupper(trim($quote->shippingCountryCode ?? 'PL'));
        $isEuCountry = \App\Support\VatOssHelper::isEuCountryOtherThanPoland($shippingCountry) || $shippingCountry === 'PL';
        $isB2c = ($quote->customerSegment !== CustomerSegment::WholesaleThirty);
        $flatDutyEnabled = (bool) ($this->storeSettings->model()->eu_import_flat_duty_enabled ?? false);

        if ($flatDutyEnabled && $isEuCountry && $isB2c) {
            $uniqueHsCodes = [];
            foreach ($quote->items as $item) {
                $product = \App\Models\Product::find($item->productId);
                if ($product && $product->is_shipped_from_outside_eu) {
                    $hsCode = trim($product->hs_code ?: '000000');
                    if (!in_array($hsCode, $uniqueHsCodes, true)) {
                        $uniqueHsCodes[] = $hsCode;
                    }
                }
            }

            if (!empty($uniqueHsCodes)) {
                $rates = $this->storeSettings->exchangeRates();
                $rateEUR = (float) ($rates['EUR'] ?? 0.23);
                $threeEurInPln = (int) round(300 / $rateEUR); // 3.00 EUR in PLN cents
                
                $dutyPerHsCode = \App\Support\TaxHelper::convertAmount($threeEurInPln, $targetCurrency);
                $importDutyAmount = $dutyPerHsCode * count($uniqueHsCodes);
            }
        }

        $totalAmount = max(0, $subtotalAfterCouponAmount - $loyaltyDiscountAmount - $wholesaleDiscountAmount + $shippingAmount + $importDutyAmount);

        $items = [];
        foreach ($quote->items as $index => $item) {
            $items[] = [
                'product_id' => $item->productId,
                'slug' => $item->slug,
                'name' => $item->name,
                'type' => $item->productType->value,
                'quantity' => $item->quantity,
                'line_subtotal_amount' => $lineSubtotals[$index],
                'coupon_discount_amount' => $lineCouponDiscounts[$index],
                'line_total_after_coupon_amount' => $lineAmountsAfterCoupon[$index],
            ];
        }

        return new QuoteResult(
            items: $items,
            subtotalAmount: $subtotalAmount,
            couponDiscountAmount: $couponDiscountAmount,
            loyaltyDiscountAmount: $loyaltyDiscountAmount,
            wholesaleDiscountAmount: $wholesaleDiscountAmount,
            shippingAmount: $shippingAmount,
            totalAmount: $totalAmount,
            freeShippingApplied: $shippingAmount === 0 && $this->hasPhysicalItems($quote),
            appliedCouponCode: $couponDiscountAmount > 0 ? $quote->coupon?->code : null,
            shippingMethodCode: $quote->shippingMethodCode,
            availablePaymentMethods: $this->resolvePaymentMethods($quote->shippingMethodCode),
            importDutyAmount: $importDutyAmount,
        );
    }

    private function resolveCouponDiscounts(?Coupon $coupon, array $items, int $subtotalAmount, float $exchangeRate): array
    {
        $discounts = array_fill(0, count($items), 0);

        if (! $coupon || ! $this->isCouponApplicable($coupon, $subtotalAmount, $exchangeRate)) {
            return $discounts;
        }

        if ($coupon->discount_type === 'percentage') {
            foreach ($items as $index => $item) {
                $convertedUnitPrice = (int) round($item->unitPriceAmount * $exchangeRate);
                $lineSubtotal = $convertedUnitPrice * $item->quantity;
                $discounts[$index] = (int) round($lineSubtotal * ($coupon->value / 100));
            }

            return $discounts;
        }

        $couponValue = (int) round($coupon->value * $exchangeRate);

        if ($coupon->discount_type === 'fixed_product') {
            foreach ($items as $index => $item) {
                $convertedUnitPrice = (int) round($item->unitPriceAmount * $exchangeRate);
                $lineSubtotal = $convertedUnitPrice * $item->quantity;
                $discounts[$index] = min($lineSubtotal, $couponValue * $item->quantity);
            }

            return $discounts;
        }

        if ($coupon->discount_type === 'fixed_cart') {
            $targetDiscount = min($couponValue, $subtotalAmount);
            $allocated = 0;

            foreach ($items as $index => $item) {
                $convertedUnitPrice = (int) round($item->unitPriceAmount * $exchangeRate);
                $lineSubtotal = $convertedUnitPrice * $item->quantity;

                if ($index === array_key_last($items)) {
                    $discounts[$index] = $targetDiscount - $allocated;
                    break;
                }

                $share = (int) floor($targetDiscount * ($lineSubtotal / max(1, $subtotalAmount)));
                $discounts[$index] = min($lineSubtotal, $share);
                $allocated += $discounts[$index];
            }
        }

        return $discounts;
    }

    private function isCouponApplicable(Coupon $coupon, int $subtotalAmount, float $exchangeRate): bool
    {
        if (! $coupon->is_active) {
            return false;
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return false;
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return false;
        }

        if ($coupon->minimum_subtotal_amount) {
            $minSubtotal = (int) round($coupon->minimum_subtotal_amount * $exchangeRate);
            if ($subtotalAmount < $minSubtotal) {
                return false;
            }
        }

        return true;
    }

    private function resolveLoyaltyDiscount(CustomerSegment $segment, int $subtotalAfterCouponAmount, array $items, array $lineAmountsAfterCoupon): int
    {
        if ($segment === CustomerSegment::WholesaleThirty) {
            return 0;
        }

        $discountPercent = match ($segment) {
            CustomerSegment::LoyalFive => 5,
            CustomerSegment::LoyalEight => 8,
            default => 0,
        };

        if ($discountPercent === 0) {
            return 0;
        }

        $eligibleAmount = 0;
        foreach ($items as $index => $item) {
            $hasCustomPrice = false;
            $user = auth('sanctum')->user() ?? auth()->user();
            if ($user) {
                $hasCustomPrice = \App\Models\ProductCustomPrice::query()
                    ->where('product_id', $item->productId)
                    ->where('product_variant_id', $item->productVariantId)
                    ->where('user_id', $user->id)
                    ->exists();
            }
            if (!$hasCustomPrice) {
                $hasCustomPrice = \App\Models\ProductCustomPrice::query()
                    ->where('product_id', $item->productId)
                    ->where('product_variant_id', $item->productVariantId)
                    ->where('customer_segment', $segment->value)
                    ->exists();
            }

            if (!$hasCustomPrice) {
                $eligibleAmount += $lineAmountsAfterCoupon[$index];
            }
        }

        return (int) round($eligibleAmount * ($discountPercent / 100));
    }

    private function resolveWholesaleDiscount(CustomerSegment $segment, array $items, array $lineAmountsAfterCoupon, float $exchangeRate): int
    {
        if ($segment !== CustomerSegment::WholesaleThirty) {
            return 0;
        }

        $multiplier = $this->storeSettings->wholesaleMinimumRegularPriceMultiplier();
        $discount = 0;

        foreach ($items as $index => $item) {
            $hasCustomPrice = false;
            $user = auth('sanctum')->user() ?? auth()->user();
            if ($user) {
                $hasCustomPrice = \App\Models\ProductCustomPrice::query()
                    ->where('product_id', $item->productId)
                    ->where('product_variant_id', $item->productVariantId)
                    ->where('user_id', $user->id)
                    ->exists();
            }
            if (!$hasCustomPrice) {
                $hasCustomPrice = \App\Models\ProductCustomPrice::query()
                    ->where('product_id', $item->productId)
                    ->where('product_variant_id', $item->productVariantId)
                    ->where('customer_segment', $segment->value)
                    ->exists();
            }

            if ($hasCustomPrice) {
                continue;
            }

            $convertedRegularPrice = (int) round($item->regularUnitPriceAmount * $exchangeRate);
            $lineRegularSubtotal = $convertedRegularPrice * $item->quantity;
            $floorAmount = (int) round($lineRegularSubtotal * $multiplier);
            if ($lineAmountsAfterCoupon[$index] > $floorAmount) {
                $discount += $lineAmountsAfterCoupon[$index] - $floorAmount;
            }
        }

        return $discount;
    }

    private function resolveShippingAmount(QuoteData $quote, int $subtotalAfterCouponAmount, float $exchangeRate): int
    {
        if (! $quote->shippingMethodCode || ! $this->hasPhysicalItems($quote)) {
            return 0;
        }

        // Calculate total weight of physical items in the cart
        $cartWeight = 0.0;
        foreach ($quote->items as $item) {
            $product = \App\Models\Product::find($item->productId);
            if ($product && $product->type->value === 'physical') {
                $cartWeight += ((float) ($product->weight ?? 0.0)) * $item->quantity;
            }
        }

        // Calculate base cart value in base currency (PLN grosze) for threshold matching
        $baseCartValue = (int) round($subtotalAfterCouponAmount / $exchangeRate);

        $method = $this->storeSettings->shippingMethodForCountry(
            $quote->shippingMethodCode,
            $quote->shippingCountryCode,
            $cartWeight,
            $baseCartValue
        );

        if (! is_array($method)) {
            return 0;
        }

        // If the matching rate has free shipping, return 0
        if (isset($method['amount']) && $method['amount'] === 0) {
            return 0;
        }

        // Check global free shipping threshold (in base currency)
        $freeShippingThreshold = (int) round($this->storeSettings->freeShippingThreshold() * $exchangeRate);
        if ($subtotalAfterCouponAmount >= $freeShippingThreshold) {
            return 0;
        }

        $amount = (int) Arr::get($method, 'amount', 0);
        return (int) round($amount * $exchangeRate);
    }

    private function hasPhysicalItems(QuoteData $quote): bool
    {
        foreach ($quote->items as $item) {
            if ($item->productType === ProductType::Physical) {
                return true;
            }
        }

        return false;
    }

    private function resolvePaymentMethods(?string $shippingMethodCode): array
    {
        if ($shippingMethodCode === $this->storeSettings->codOnlyMethod()) {
            return ['cod'];
        }

        return ['przelewy24', 'stripe'];
    }
}