<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ViesReverseChargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure default store settings exist and support EU shipping
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $settingsModel->update([
            'allow_guest_checkout' => true,
            'currency' => 'PLN',
            'shipping_zones' => [
                [
                    'code' => 'EU',
                    'name' => 'Europe',
                    'countries' => 'DE, FR',
                ]
            ],
            'shipping_methods' => [
                [
                    'code' => 'flat_rate:courier',
                    'name' => 'Kurier',
                    'supports_cod' => false,
                    'requires_delivery_point' => false,
                    'amount' => 2000, // 20 PLN
                    'rates' => [
                        [
                            'zone_code' => 'EU',
                            'min_weight' => 0.0,
                            'max_weight' => null,
                            'min_value' => 0,
                            'max_value' => null,
                            'amount' => 2000,
                            'free_shipping' => false,
                        ]
                    ]
                ]
            ],
            'metadata' => [
                'vies_enabled' => true,
                'vies_strict_mode' => false,
            ]
        ]);
    }

    public function test_reverse_charge_applied_for_valid_eu_vat(): void
    {
        // Mock active VIES VAT
        Http::fake([
            'ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number' => Http::response([
                'valid' => true,
                'name' => 'DEUTSCH COMPANY GMBH',
                'address' => 'Musterstrasse 12, Berlin',
            ], 200)
        ]);

        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'type' => 'physical',
            'vat_rate' => 23,
            'regular_price_amount' => 12300, // 123.00 PLN (Gross). Net = 100.00 PLN. VAT = 23.00 PLN.
            'sale_price_amount' => 12300,
            'weight' => 1.0,
        ]);

        // Place checkout order for Germany (DE) as B2B with valid VAT
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'test-product', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'company@example.de',
                'first_name' => 'Hans',
                'last_name' => 'Muller',
                'company_name' => 'DEUTSCH COMPANY GMBH',
                'nip' => 'DE123456789',
                'wants_invoice' => true,
            ],
            'shipping_address' => [
                'street' => 'Musterstrasse 12',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'billing_address' => [
                'street' => 'Musterstrasse 12',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);
        $order = Order::query()->first();
        $this->assertNotNull($order);

        // Under Reverse Charge:
        // 1. VAT rate is 0%
        $item = $order->items->first();
        $this->assertEquals(0, data_get($item->metadata, 'vat_rate'));
        
        // 2. The item total amount paid is NET (100.00 PLN = 10000 gr)
        $this->assertEquals(10000, $item->total_amount);
        $this->assertEquals(0, $item->tax_amount);

        // 3. Shipping amount is NET (20 PLN / 1.23 = 16.26 PLN = 1626 gr)
        // Shipping Net = 2000 / 1.23 = 1626 gr
        $this->assertEquals(1626, $order->shipping_amount);

        // 4. Total order amount is sum of nets (10000 + 1626 = 11626 gr)
        $this->assertEquals(11626, $order->total_amount);
        $this->assertEquals(0, $order->tax_amount);

        // 5. VIES metadata is logged
        $this->assertEquals('valid', data_get($order->metadata, 'vies_status'));
        $this->assertEquals('DEUTSCH COMPANY GMBH', data_get($order->metadata, 'vies_trader_name'));
        $this->assertEquals('Musterstrasse 12, Berlin', data_get($order->metadata, 'vies_trader_address'));
    }

    public function test_checkout_fails_for_invalid_eu_vat(): void
    {
        // Mock inactive/invalid VIES VAT
        Http::fake([
            'ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number' => Http::response([
                'valid' => false,
            ], 200)
        ]);

        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'type' => 'physical',
            'vat_rate' => 23,
            'regular_price_amount' => 12300,
            'sale_price_amount' => 12300,
            'weight' => 1.0,
        ]);

        // Place checkout order with invalid NIP
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'test-product', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'company@example.de',
                'first_name' => 'Hans',
                'last_name' => 'Muller',
                'company_name' => 'DEUTSCH COMPANY GMBH',
                'nip' => 'DE000000000',
                'wants_invoice' => true,
            ],
            'shipping_address' => [
                'street' => 'Musterstrasse 12',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'billing_address' => [
                'street' => 'Musterstrasse 12',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer.nip']);
    }

    public function test_vies_down_with_strict_mode_blocks_checkout(): void
    {
        // Mock API down/timeout
        Http::fake([
            'ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number' => function () {
                throw new \Exception('VIES down');
            }
        ]);

        // Enable strict mode
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $metadata = $settingsModel->metadata;
        $metadata['vies_strict_mode'] = true;
        $settingsModel->update(['metadata' => $metadata]);

        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'type' => 'physical',
            'vat_rate' => 23,
            'regular_price_amount' => 12300,
            'sale_price_amount' => 12300,
            'weight' => 1.0,
        ]);

        // Place checkout order
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'test-product', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'company@example.de',
                'first_name' => 'Hans',
                'last_name' => 'Muller',
                'company_name' => 'DEUTSCH COMPANY GMBH',
                'nip' => 'DE123456789',
                'wants_invoice' => true,
            ],
            'shipping_address' => [
                'street' => 'Musterstrasse 12',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'billing_address' => [
                'street' => 'Musterstrasse 12',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer.nip']);
    }

    public function test_vies_down_without_strict_mode_allows_checkout(): void
    {
        // Mock API down/timeout
        Http::fake([
            'ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number' => function () {
                throw new \Exception('VIES down');
            }
        ]);

        // Disable strict mode (is default, but let's be explicit)
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $metadata = $settingsModel->metadata;
        $metadata['vies_strict_mode'] = false;
        $settingsModel->update(['metadata' => $metadata]);

        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'type' => 'physical',
            'vat_rate' => 23,
            'regular_price_amount' => 12300,
            'sale_price_amount' => 12300,
            'weight' => 1.0,
        ]);

        // Place checkout order
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'test-product', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'company@example.de',
                'first_name' => 'Hans',
                'last_name' => 'Muller',
                'company_name' => 'DEUTSCH COMPANY GMBH',
                'nip' => 'DE123456789', // valid format
                'wants_invoice' => true,
            ],
            'shipping_address' => [
                'street' => 'Musterstrasse 12',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'billing_address' => [
                'street' => 'Musterstrasse 12',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);
        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertEquals('vies_down_fallback', data_get($order->metadata, 'vies_status'));
    }
}
