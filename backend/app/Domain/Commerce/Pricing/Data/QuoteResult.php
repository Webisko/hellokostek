<?php

namespace App\Domain\Commerce\Pricing\Data;

final class QuoteResult
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string>  $availablePaymentMethods
     */
    public function __construct(
        public readonly array $items,
        public readonly int $subtotalAmount,
        public readonly int $couponDiscountAmount,
        public readonly int $loyaltyDiscountAmount,
        public readonly int $wholesaleDiscountAmount,
        public readonly int $shippingAmount,
        public readonly int $totalAmount,
        public readonly bool $freeShippingApplied,
        public readonly ?string $appliedCouponCode,
        public readonly ?string $shippingMethodCode,
        public readonly array $availablePaymentMethods,
        public readonly int $importDutyAmount = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'subtotal_amount' => $this->subtotalAmount,
            'coupon_discount_amount' => $this->couponDiscountAmount,
            'loyalty_discount_amount' => $this->loyaltyDiscountAmount,
            'wholesale_discount_amount' => $this->wholesaleDiscountAmount,
            'shipping_amount' => $this->shippingAmount,
            'total_amount' => $this->totalAmount,
            'free_shipping_applied' => $this->freeShippingApplied,
            'applied_coupon_code' => $this->appliedCouponCode,
            'shipping_method_code' => $this->shippingMethodCode,
            'available_payment_methods' => $this->availablePaymentMethods,
            'import_duty_amount' => $this->importDutyAmount,
        ];
    }
}