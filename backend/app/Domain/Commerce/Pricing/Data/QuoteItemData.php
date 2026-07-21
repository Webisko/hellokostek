<?php

namespace App\Domain\Commerce\Pricing\Data;

use App\Domain\Commerce\Enums\ProductType;

final class QuoteItemData
{
    public function __construct(
        public readonly int $productId,
        public readonly string $slug,
        public readonly string $name,
        public readonly ProductType $productType,
        public readonly int $quantity,
        public readonly int $unitPriceAmount,
        public readonly int $regularUnitPriceAmount,
        public ?int $productVariantId = null,
    ) {
    }

    public function lineSubtotalAmount(): int
    {
        return $this->unitPriceAmount * $this->quantity;
    }

    public function lineRegularSubtotalAmount(): int
    {
        return $this->regularUnitPriceAmount * $this->quantity;
    }
}