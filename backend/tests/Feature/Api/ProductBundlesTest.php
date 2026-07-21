<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductBundleItem;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBundlesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $settingsModel->update([
            'allow_guest_checkout' => true,
            'currency' => 'PLN',
            'shipping_methods' => [
                [
                    'code' => 'flat_rate:courier',
                    'name' => 'Kurier',
                    'supports_cod' => false,
                    'requires_delivery_point' => false,
                    'amount' => 1500,
                    'rates' => []
                ]
            ]
        ]);
    }

    public function test_bundle_dynamic_stock_calculation(): void
    {
        // 1. Component product with variants
        $component1 = Product::factory()->public()->create([
            'type' => 'physical',
            'manages_stock' => false,
        ]);
        $variant1 = ProductVariant::create([
            'product_id' => $component1->id,
            'sku' => 'COMP1-VAR1',
            'regular_price_amount' => 5000,
            'stock_quantity' => 10,
            'manages_stock' => true,
            'is_active' => true,
        ]);

        // 2. Simple component product (no variants)
        $component2 = Product::factory()->public()->create([
            'type' => 'physical',
            'manages_stock' => true,
            'stock_quantity' => 15,
        ]);

        // 3. Bundle product
        $bundle = Product::factory()->public()->create([
            'type' => 'bundle',
            'manages_stock' => false,
            'stock_quantity' => null,
        ]);

        // Create bundle items
        ProductBundleItem::create([
            'bundle_product_id' => $bundle->id,
            'product_id' => $component1->id,
            'product_variant_id' => $variant1->id,
            'quantity' => 2,
        ]);

        ProductBundleItem::create([
            'bundle_product_id' => $bundle->id,
            'product_id' => $component2->id,
            'product_variant_id' => null,
            'quantity' => 3,
        ]);

        // Stock quantity should be determined by the lowest available component
        // Component 1 available: 10 / 2 = 5 bundles possible
        // Component 2 available: 15 / 3 = 5 bundles possible
        $this->assertEquals(5, $bundle->getDynamicStockQuantity());

        // Update component 2 stock to 6 (so 6 / 3 = 2 bundles possible)
        $component2->update(['stock_quantity' => 6]);
        $bundle->unsetRelation('bundleItems');
        $this->assertEquals(2, $bundle->getDynamicStockQuantity());
    }

    public function test_checkout_validation_and_stock_reduction_for_bundles(): void
    {
        $component1 = Product::factory()->public()->create(['type' => 'physical', 'manages_stock' => false]);
        $variant1 = ProductVariant::create([
            'product_id' => $component1->id,
            'sku' => 'COMP-VAR',
            'regular_price_amount' => 5000,
            'stock_quantity' => 4,
            'manages_stock' => true,
            'is_active' => true,
        ]);

        $component2 = Product::factory()->public()->create(['type' => 'physical', 'manages_stock' => true, 'stock_quantity' => 10]);

        $bundle = Product::factory()->public()->create([
            'slug' => 'super-bundle',
            'type' => 'bundle',
            'regular_price_amount' => 10000,
            'sale_price_amount' => 10000,
            'weight' => 2.0,
        ]);

        ProductBundleItem::create([
            'bundle_product_id' => $bundle->id,
            'product_id' => $component1->id,
            'product_variant_id' => $variant1->id,
            'quantity' => 2,
        ]);

        ProductBundleItem::create([
            'bundle_product_id' => $bundle->id,
            'product_id' => $component2->id,
            'product_variant_id' => null,
            'quantity' => 3,
        ]);

        // Place order requesting 3 bundles (needs 6 variants and 9 component2) -> should fail (only 4 variants available)
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'super-bundle', 'quantity' => 3]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'client@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'wants_invoice' => false,
            ],
            'shipping_address' => [
                'street' => 'Jasna 10',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country_code' => 'PL',
            ],
            'billing_address' => [
                'street' => 'Jasna 10',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country_code' => 'PL',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422);

        // Place order requesting 2 bundles (needs 4 variants and 6 component2) -> should succeed
        $responseSucceed = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'super-bundle', 'quantity' => 2]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'client@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'wants_invoice' => false,
            ],
            'shipping_address' => [
                'street' => 'Jasna 10',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country_code' => 'PL',
            ],
            'billing_address' => [
                'street' => 'Jasna 10',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country_code' => 'PL',
            ],
            'terms_accepted' => true,
        ]);

        $responseSucceed->assertStatus(201);

        // Check stock reductions
        $this->assertEquals(0, $variant1->fresh()->stock_quantity); // 4 - (2 * 2) = 0
        $this->assertEquals(4, $component2->fresh()->stock_quantity); // 10 - (2 * 3) = 4
    }

    public function test_restocking_bundle_components_on_cancellation(): void
    {
        $component1 = Product::factory()->public()->create(['type' => 'physical', 'manages_stock' => false]);
        $variant1 = ProductVariant::create([
            'product_id' => $component1->id,
            'sku' => 'RESTOCK-VAR',
            'regular_price_amount' => 5000,
            'stock_quantity' => 4,
            'manages_stock' => true,
            'is_active' => true,
        ]);

        $component2 = Product::factory()->public()->create(['type' => 'physical', 'manages_stock' => true, 'stock_quantity' => 10]);

        $bundle = Product::factory()->public()->create([
            'slug' => 'restock-bundle',
            'type' => 'bundle',
            'regular_price_amount' => 10000,
            'sale_price_amount' => 10000,
            'weight' => 2.0,
        ]);

        ProductBundleItem::create([
            'bundle_product_id' => $bundle->id,
            'product_id' => $component1->id,
            'product_variant_id' => $variant1->id,
            'quantity' => 2,
        ]);

        ProductBundleItem::create([
            'bundle_product_id' => $bundle->id,
            'product_id' => $component2->id,
            'product_variant_id' => null,
            'quantity' => 3,
        ]);

        // Place order for 1 bundle
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'restock-bundle', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'client@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'wants_invoice' => false,
            ],
            'shipping_address' => [
                'street' => 'Jasna 10',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country_code' => 'PL',
            ],
            'billing_address' => [
                'street' => 'Jasna 10',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country_code' => 'PL',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);

        $order = Order::first();
        $this->assertNotNull($order);

        // Stock after checkout
        $this->assertEquals(2, $variant1->fresh()->stock_quantity); // 4 - 2 = 2
        $this->assertEquals(7, $component2->fresh()->stock_quantity); // 10 - 3 = 7

        // Cancel order -> should trigger restock
        $order->update(['status' => 'cancelled']);

        // Stock after cancellation
        $this->assertEquals(4, $variant1->fresh()->stock_quantity); // 2 + 2 = 4
        $this->assertEquals(10, $component2->fresh()->stock_quantity); // 7 + 3 = 10
    }
}
