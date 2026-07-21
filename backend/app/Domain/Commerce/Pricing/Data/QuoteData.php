<?php

namespace App\Domain\Commerce\Pricing\Data;

use App\Domain\Commerce\Enums\CustomerSegment;
use App\Models\Coupon;

final class QuoteData
{
    /**
     * @param  array<int, QuoteItemData>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly CustomerSegment $customerSegment,
        public readonly ?Coupon $coupon = null,
        public readonly ?string $shippingMethodCode = null,
        public readonly ?string $shippingCountryCode = null,
        public readonly ?string $currency = null,
    ) {
    }
}