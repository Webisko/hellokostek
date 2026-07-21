<?php

namespace App\Domain\Commerce\Checkout\Data;

use App\Domain\Commerce\Pricing\Data\QuoteData;
use App\Domain\Commerce\Pricing\Data\QuoteItemData;
use App\Models\Coupon;

final class ResolvedCheckoutData
{
    /**
     * @param  array<int, QuoteItemData>  $quoteItems
     */
    public function __construct(
        public readonly array $quoteItems,
        public readonly QuoteData $quote,
        public readonly ?Coupon $coupon,
        public readonly ?string $shippingMethodCode,
        public readonly ?string $shippingMethodName,
        public readonly string $currency,
    ) {
    }
}