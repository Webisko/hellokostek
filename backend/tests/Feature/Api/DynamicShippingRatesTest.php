<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicShippingRatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize settings
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();

        $settingsModel->update([
            'allow_guest_checkout' => true,
            'currency' => 'PLN',
            'free_shipping_threshold' => 9999999, // Set high to test rates rather than global free shipping
            'shipping_zones' => [
                [
                    'code' => 'PL',
                    'name' => 'Polska',
                    'countries' => 'PL',
                ],
                [
                    'code' => 'EU',
                    'name' => 'Unia Europejska',
                    'countries' => 'DE, FR, ES',
                ]
            ],
            'shipping_methods' => [
                [
                    'code' => 'flat_rate:courier',
                    'name' => 'Kurier',
                    'supports_cod' => false,
                    'requires_delivery_point' => false,
                    'amount' => 2000, // base price
                    'rates' => [
                        // PL weight rates
                        [
                            'zone_code' => 'PL',
                            'min_weight' => 0.0,
                            'max_weight' => 5.0,
                            'min_value' => 0,
                            'max_value' => 10000, // 0-100 PLN
                            'amount' => 1500, // 15.00 PLN
                            'free_shipping' => false,
                        ],
                        [
                            'zone_code' => 'PL',
                            'min_weight' => 0.0,
                            'max_weight' => 5.0,
                            'min_value' => 10000,
                            'max_value' => 25000, // 100-250 PLN
                            'amount' => 1000, // 10.00 PLN
                            'free_shipping' => false,
                        ],
                        [
                            'zone_code' => 'PL',
                            'min_weight' => 0.0,
                            'max_weight' => 5.0,
                            'min_value' => 25000,
                            'max_value' => null, // 250+ PLN
                            'amount' => 0,
                            'free_shipping' => true,
                        ],
                        // PL heavy weight rate
                        [
                            'zone_code' => 'PL',
                            'min_weight' => 5.0,
                            'max_weight' => 30.0,
                            'min_value' => 0,
                            'max_value' => null,
                            'amount' => 3000, // 30.00 PLN
                            'free_shipping' => false,
                        ],
                        [
                            'zone_code' => 'PL',
                            'min_weight' => 30.0,
                            'max_weight' => null, // 30+ kg (palette)
                            'amount' => 9900, // 99.00 PLN
                            'free_shipping' => false,
                        ]
                    ]
                ]
            ]
        ]);

        $settingsModel->refresh();
        $settings->model()->refresh();
    }

    public function test_shipping_rate_by_weight_thresholds(): void
    {
        // Light product
        $lightProduct = Product::factory()->public()->create([
            'sku' => 'LIGHT-1',
            'slug' => 'light-product',
            'regular_price_amount' => 5000, // 50 PLN
            'type' => 'physical',
            'weight' => 1.5, // 1.5 kg
        ]);

        // Heavy product
        $heavyProduct = Product::factory()->public()->create([
            'sku' => 'HEAVY-1',
            'slug' => 'heavy-product',
            'regular_price_amount' => 5000, // 50 PLN
            'type' => 'physical',
            'weight' => 25.0, // 25 kg
        ]);

        // 1. Checkout with 1x light product (total 1.5kg, value 50 PLN) -> Rate 1 matches (15.00 PLN)
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'light-product', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'terms_accepted' => true,
            'customer' => [
                'email' => 'test@test.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ]
        ]);

        $response->assertStatus(201);
        $this->assertEquals(1500, $response->json('data.order.shipping_amount'));

        // 2. Checkout with 6x light product (total 9kg, value 300 PLN) -> PL heavy rate (5-30kg) matches (30.00 PLN)
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'light-product', 'quantity' => 6]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'terms_accepted' => true,
            'customer' => [
                'email' => 'test@test.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ]
        ]);

        $response->assertStatus(201);
        $this->assertEquals(3000, $response->json('data.order.shipping_amount'));

        // 3. Checkout with 2x heavy product (total 50kg, value 100 PLN) -> PL palette rate (30+ kg) matches (99.00 PLN)
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'heavy-product', 'quantity' => 2]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'terms_accepted' => true,
            'customer' => [
                'email' => 'test@test.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ]
        ]);

        $response->assertStatus(201);
        $this->assertEquals(9900, $response->json('data.order.shipping_amount'));
    }

    public function test_shipping_rate_by_value_thresholds(): void
    {
        $product = Product::factory()->public()->create([
            'sku' => 'VAL-1',
            'slug' => 'val-product',
            'regular_price_amount' => 12000, // 120 PLN
            'type' => 'physical',
            'weight' => 1.0, // 1 kg
        ]);

        // 1. Checkout with 1x product (value 120 PLN, weight 1kg) -> Rate 2 matches (10.00 PLN)
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'val-product', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'terms_accepted' => true,
            'customer' => [
                'email' => 'test@test.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ]
        ]);

        $response->assertStatus(201);
        $this->assertEquals(1000, $response->json('data.order.shipping_amount'));

        // 2. Checkout with 3x product (value 360 PLN, weight 3kg) -> Rate 3 matches (0 PLN, free shipping)
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'val-product', 'quantity' => 3]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'terms_accepted' => true,
            'customer' => [
                'email' => 'test@test.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ]
        ]);

        $response->assertStatus(201);
        $this->assertEquals(0, $response->json('data.order.shipping_amount'));
    }

    public function test_shipping_method_unavailable_if_no_matching_rate_exists(): void
    {
        $giantProduct = Product::factory()->public()->create([
            'sku' => 'GIANT-1',
            'slug' => 'giant-product',
            'regular_price_amount' => 5000,
            'type' => 'physical',
            'weight' => 50.0, // 50 kg
        ]);

        // Change rates so that there is no rate for weights > 30kg
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $methods = $settingsModel->shipping_methods;
        // Keep only the first rate (0-5kg)
        $methods[0]['rates'] = [
            [
                'zone_code' => 'PL',
                'min_weight' => 0.0,
                'max_weight' => 30.0,
                'min_value' => 0,
                'max_value' => null,
                'amount' => 1500,
                'free_shipping' => false,
            ]
        ];
        $settingsModel->update(['shipping_methods' => $methods]);

        // Checkout with giant product (50kg) -> should fail as no rates match
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'giant-product', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'terms_accepted' => true,
            'customer' => [
                'email' => 'test@test.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shipping_method_code']);
    }
}
