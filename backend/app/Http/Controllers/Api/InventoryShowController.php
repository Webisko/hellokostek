<?php

namespace App\Http\Controllers\Api;

use App\Domain\Commerce\Inventory\InventoryAvailabilityService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class InventoryShowController extends Controller
{
    public function __construct(
        private readonly InventoryAvailabilityService $inventoryAvailabilityService,
    ) {
    }

    public function __invoke(string $sku): JsonResponse
    {
        $product = Product::query()
            ->publicCatalog()
            ->where('sku', $sku)
            ->firstOrFail();

        $inventory = $this->inventoryAvailabilityService->forProduct($product);

        return response()->json([
            'data' => [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'name' => $product->name,
                'source' => $inventory['source'],
                'showing_source' => $inventory['showing_source'],
                'quantity' => $inventory['quantity'],
                'is_available' => $inventory['is_available'],
                'is_stale' => $inventory['is_stale'],
                'sync_state' => $inventory['sync_state'],
                'last_synced_at' => $inventory['last_synced_at'],
                'ttl_seconds' => $inventory['ttl_seconds'],
                'external_product_id' => $inventory['external_product_id'],
                'external_offer_id' => $inventory['external_offer_id'],
                'warehouse_id' => $inventory['warehouse_id'],
                'stock_location' => $inventory['stock_location'],
                'last_error' => $inventory['last_error'],
            ],
        ]);
    }
}