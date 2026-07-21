<?php

namespace App\Domain\Commerce\Inventory;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class InventoryAvailabilityService
{
    /**
     * @return array<string, mixed>
     */
    public function forProduct(Product $product): array
    {
        $managesStock = $product->managesStock();
        $availableQuantity = $product->getDynamicStockQuantity();

        return [
            'source' => 'local',
            'quantity' => $availableQuantity,
            'is_available' => $managesStock ? ($availableQuantity > 0) : true,
            'is_stale' => false,
            'sync_state' => $managesStock ? 'local' : 'not_managed',
            'last_synced_at' => null,
            'ttl_seconds' => null,
            'showing_source' => 'local',
            'external_product_id' => null,
            'external_offer_id' => null,
            'warehouse_id' => null,
            'stock_location' => null,
            'last_error' => null,
        ];
    }

    public function assertCanFulfill(Product $product, int $requestedQuantity, bool $requireFreshInventory = false, ?int $itemIndex = null): void
    {
        $inventory = $this->forProduct($product);
        $errorKey = is_int($itemIndex) ? 'items.' . $itemIndex . '.quantity' : 'items';

        if (is_int($inventory['quantity']) && $requestedQuantity > $inventory['quantity']) {
            throw ValidationException::withMessages([
                $errorKey => 'Brak wystarczajacego stanu magazynowego dla wybranego produktu.',
            ]);
        }
    }
}