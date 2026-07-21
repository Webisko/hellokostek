<?php

namespace Tests\Feature\Api;

use App\Domain\Commerce\Enums\CustomerSegment;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCustomPrice;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSpecificPricingTest extends TestCase
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
            'shipping_zones' => [
                [
                    'code' => 'PL',
                    'name' => 'Polska',
                    'countries' => 'PL',
                ]
            ],
            'shipping_methods' => [
                [
                    'code' => 'flat_rate:courier',
                    'name' => 'Kurier',
                    'supports_cod' => false,
                    'requires_delivery_point' => false,
                    'amount' => 1500, // 15 PLN
                    'rates' => [
                        [
                            'zone_code' => 'PL',
                            'min_weight' => 0.0,
                            'max_weight' => null,
                            'min_value' => 0,
                            'max_value' => null,
                            'amount' => 1500,
                            'free_shipping' => false,
                        ]
                    ]
                ]
            ],
        ]);
    }

    public function test_regular_guest_gets_default_price(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'regular_price_amount' => 10000, // 100 PLN
            'sale_price_amount' => null,
        ]);

        $this->assertEquals(10000, $product->currentPriceAmount());
    }

    public function test_customer_segment_custom_price(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'regular_price_amount' => 10000, // 100 PLN
            'sale_price_amount' => null,
        ]);

        // Create a custom price for Wholesale segment (hurt_30) -> 70 PLN (7000 gr)
        ProductCustomPrice::create([
            'product_id' => $product->id,
            'customer_segment' => CustomerSegment::WholesaleThirty,
            'price_amount' => 7000,
        ]);

        // Verify guest still gets 100 PLN
        $this->assertEquals(10000, $product->currentPriceAmount());

        // Verify Wholesale segment gets 70 PLN
        $this->assertEquals(7000, $product->currentPriceAmount(segment: CustomerSegment::WholesaleThirty));
    }

    public function test_specific_user_custom_price_overrides_segment_and_default(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'regular_price_amount' => 10000, // 100 PLN
            'sale_price_amount' => null,
        ]);

        $user = User::factory()->create();
        CustomerProfile::create([
            'user_id' => $user->id,
            'segment' => CustomerSegment::WholesaleThirty,
        ]);

        // 1. Segment price = 70 PLN
        ProductCustomPrice::create([
            'product_id' => $product->id,
            'customer_segment' => CustomerSegment::WholesaleThirty,
            'price_amount' => 7000,
        ]);

        // 2. User specific price = 60 PLN
        ProductCustomPrice::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'price_amount' => 6000,
        ]);

        // Resolve price for this user - should get 60 PLN
        $this->assertEquals(6000, $product->currentPriceAmount($user));
    }

    public function test_variant_level_custom_pricing(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'regular_price_amount' => 10000,
            'sale_price_amount' => null,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'test-product-l',
            'regular_price_amount' => 12000, // 120 PLN
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        // Custom price for the variant for this specific user = 95 PLN (9500 gr)
        ProductCustomPrice::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'user_id' => $user->id,
            'price_amount' => 9500,
        ]);

        // Guest variant price should be default 120 PLN
        $this->assertEquals(12000, $variant->currentPriceAmount());

        // Logged user variant price should be 95 PLN
        $this->assertEquals(9500, $variant->currentPriceAmount($user));
    }

    public function test_checkout_applies_custom_pricing_consistently(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'regular_price_amount' => 10000, // 100 PLN
            'sale_price_amount' => null,
            'vat_rate' => 23,
            'weight' => 1.0,
        ]);

        $user = User::factory()->create();
        CustomerProfile::create([
            'user_id' => $user->id,
            'segment' => CustomerSegment::WholesaleThirty,
        ]);

        // Wholesale custom price = 70 PLN (7000 gr)
        ProductCustomPrice::create([
            'product_id' => $product->id,
            'customer_segment' => CustomerSegment::WholesaleThirty,
            'price_amount' => 7000,
        ]);

        // Place checkout as logged in Wholesale B2B user
        $response = $this->actingAs($user, 'sanctum')->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'test-product', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => $user->email,
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'company_name' => 'Hurtownia Janusz',
                'nip' => '1234567890',
                'wants_invoice' => true,
            ],
            'shipping_address' => [
                'street' => 'Wiejska 1',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country_code' => 'PL',
            ],
            'billing_address' => [
                'street' => 'Wiejska 1',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country_code' => 'PL',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);

        $order = Order::query()->first();
        $this->assertNotNull($order);

        // Order total amount should use the custom price (70 PLN product + 15 PLN shipping = 85 PLN = 8500 gr)
        $this->assertEquals(8500, $order->total_amount);

        // Item price should be the custom price 70 PLN
        $item = $order->items->first();
        $this->assertEquals(7000, $item->unit_price_amount);
        $this->assertEquals(7000, $item->regular_unit_price_amount);
    }
}
