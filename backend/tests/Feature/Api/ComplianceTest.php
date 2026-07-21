<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure default store settings exist
        app(StoreSettings::class)->model();
    }

    public function test_guest_returns_rma_compliance(): void
    {
        Mail::fake();

        // 1. Create a guest order that is completed
        $order = Order::query()->create([
            'number' => 'ORD-GUEST-123',
            'customer_email' => 'guest@example.com',
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'status' => 'completed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 1870,
            'total_amount' => 10000,
            'user_id' => null, // guest order
        ]);

        $product = Product::factory()->public()->create([
            'name' => 'Compliance Book',
            'slug' => 'compliance-book',
            'type' => 'physical',
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => 'physical',
            'sku' => 'COMP-BOOK',
            'name' => 'Compliance Book',
            'quantity' => 2,
            'unit_price_amount' => 5000,
            'regular_unit_price_amount' => 5000,
            'discount_amount' => 0,
            'tax_amount' => 935,
            'total_amount' => 10000,
        ]);

        // 2. Try guest return with invalid data (wrong order number/email)
        $response = $this->postJson(route('api.returns.store'), [
            'order_number' => 'ORD-WRONG',
            'customer_email' => 'guest@example.com',
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
            ],
        ]);
        $response->assertStatus(404);

        // 3. Try guest return with correct data, omitting optional reason
        $response = $this->postJson(route('api.returns.store'), [
            'order_number' => 'ORD-GUEST-123',
            'customer_email' => 'guest@example.com',
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('order_returns', [
            'order_id' => $order->id,
            'user_id' => null,
            'status' => 'pending',
            'reason' => '',
        ]);

        Mail::assertSent(\App\Mail\OrderReturnConfirmationMail::class, function ($mail) use ($order) {
            return $mail->hasTo($order->customer_email);
        });
    }

    public function test_digital_cart_checkout_geoblocking_bypass(): void
    {
        // 1. Create a digital product
        $digitalProduct = Product::factory()->public()->create([
            'slug' => 'compliance-pdf',
            'type' => 'digital',
        ]);

        // Configure shipping zones so PL is the only one, but customer resides in US
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $settingsModel->update([
            'allow_guest_checkout' => true,
            'shipping_zones' => [
                [
                    'code' => 'PL',
                    'name' => 'Polska',
                    'countries' => ['PL'],
                ]
            ],
        ]);

        // US country code would normally fail for physical products, but should pass for digital
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'compliance-pdf', 'quantity' => 1]
            ],
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'us-buyer@example.com',
                'first_name' => 'Sam',
                'last_name' => 'Smith',
            ],
            'shipping_address' => [
                'country_code' => 'US',
                'city' => 'New York',
                'street' => '5th Ave',
                'postal_code' => '10001',
            ],
            'terms_accepted' => true,
            'digital_consent' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', [
            'customer_email' => 'us-buyer@example.com',
            'currency' => 'PLN',
        ]);
    }

    public function test_uk_norway_vat_thresholds(): void
    {
        // Setup products
        $product = Product::factory()->public()->create([
            'slug' => 'regular-item',
            'type' => 'physical',
            'vat_rate' => 23,
            'regular_price_amount' => 10000,
        ]);

        // Configure exchange rates
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $settingsModel->update([
            'allow_guest_checkout' => true,
            'exchange_rates' => [
                'GBP' => 0.20, // 1 PLN = 0.20 GBP -> 135 GBP threshold is 675 PLN (67500 cents)
                'NOK' => 2.70, // 1 PLN = 2.70 NOK -> 3000 NOK threshold is 1111.11 PLN (111111 cents)
            ],
            'shipping_zones' => [
                [
                    'code' => 'EUROPE',
                    'name' => 'Europe',
                    'countries' => ['GB', 'NO'],
                ]
            ],
            'shipping_methods' => [
                [
                    'code' => 'flat_rate',
                    'name' => 'Standard',
                    'amount' => 1500, // 15 PLN shipping
                    'zone_prices' => [
                        [
                            'zone_code' => 'EUROPE',
                            'amount' => 1500,
                        ]
                    ]
                ]
            ],
        ]);

        // 1. UK Order under £135 threshold
        // Total amount (product price 100 PLN = 10000 cents + shipping 15 PLN = 1500 cents = 11500 cents total)
        // converted to GBP: 11500 * 0.20 = 2300 cents (£23.00), which is <= £135.00.
        // Therefore UK local B2C VAT applies (20% VAT).
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'regular-item', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'uk-b2c@example.com',
                'first_name' => 'Harry',
                'last_name' => 'Potter',
            ],
            'shipping_address' => [
                'country_code' => 'GB',
                'city' => 'London',
                'street' => 'Privet Drive',
                'postal_code' => 'WD25 7LS',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);
        $order = Order::where('customer_email', 'uk-b2c@example.com')->first();
        $this->assertNotNull($order);
        // Let's verify VAT rate in order items is 20
        $orderItem = $order->items->first();
        $this->assertEquals(20, data_get($orderItem->metadata, 'vat_rate'));

        // 2. UK Order over £135 threshold
        // To exceed £135, total must be > 675 PLN (67500 cents). Let's purchase 7 items of 100 PLN (70000 cents).
        // converted to GBP: 71500 * 0.20 = 14300 cents (£143.00), which is > £135.00.
        // Therefore UK VAT should be 0 (subject to customs/import tax, outside our checkout VAT).
        $response2 = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'regular-item', 'quantity' => 7]
            ],
            'shipping_method_code' => 'flat_rate',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'uk-b2c-over@example.com',
                'first_name' => 'Harry',
                'last_name' => 'Potter',
            ],
            'shipping_address' => [
                'country_code' => 'GB',
                'city' => 'London',
                'street' => 'Privet Drive',
                'postal_code' => 'WD25 7LS',
            ],
            'terms_accepted' => true,
        ]);

        $response2->assertStatus(201);
        $order2 = Order::where('customer_email', 'uk-b2c-over@example.com')->first();
        $this->assertNotNull($order2);
        $orderItem2 = $order2->items->first();
        $this->assertEquals(0, data_get($orderItem2->metadata, 'vat_rate'));

        // 3. Norway VOEC Order under 3000 NOK threshold (unit price <= 3000 NOK)
        // Unit price: 100 PLN = 270 NOK, which is <= 3000 NOK.
        // Therefore Norway local B2C VAT applies (25%).
        $response3 = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'regular-item', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'no-b2c@example.com',
                'first_name' => 'Thor',
                'last_name' => 'Odin',
            ],
            'shipping_address' => [
                'country_code' => 'NO',
                'city' => 'Oslo',
                'street' => 'Karl Johans gate',
                'postal_code' => '0026',
            ],
            'terms_accepted' => true,
        ]);

        $response3->assertStatus(201);
        $order3 = Order::where('customer_email', 'no-b2c@example.com')->first();
        $this->assertNotNull($order3);
        $orderItem3 = $order3->items->first();
        $this->assertEquals(25, data_get($orderItem3->metadata, 'vat_rate'));

        // 4. Norway Order over 3000 NOK threshold (single unit price > 3000 NOK)
        // Let's create a product with regular price 1200 PLN = 3240 NOK.
        $expensiveProduct = Product::factory()->public()->create([
            'slug' => 'expensive-item',
            'type' => 'physical',
            'regular_price_amount' => 120000, // 1200 PLN
            'vat_rate' => 23,
        ]);

        $response4 = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'expensive-item', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'no-b2c-over@example.com',
                'first_name' => 'Thor',
                'last_name' => 'Odin',
            ],
            'shipping_address' => [
                'country_code' => 'NO',
                'city' => 'Oslo',
                'street' => 'Karl Johans gate',
                'postal_code' => '0026',
            ],
            'terms_accepted' => true,
        ]);

        $response4->assertStatus(201);
        $order4 = Order::where('customer_email', 'no-b2c-over@example.com')->first();
        $this->assertNotNull($order4);
        $orderItem4 = $order4->items->first();
        $this->assertEquals(0, data_get($orderItem4->metadata, 'vat_rate'));
    }

    public function test_omnibus_variant_pricing_history(): void
    {
        // 1. Create a product
        $product = Product::factory()->public()->create([
            'slug' => 'omnibus-shoe',
            'regular_price_amount' => 20000,
        ]);

        // Create a variant
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'shoe-red-42',
            'regular_price_amount' => 15000,
            'sale_price_amount' => null,
            'is_active' => true,
        ]);

        // History is populated via booted observer on creation
        $this->assertDatabaseHas('product_price_histories', [
            'product_variant_id' => $variant->id,
            'regular_price_amount' => 15000,
        ]);

        // 2. Change variant price to trigger a new price history record
        $variant->update([
            'regular_price_amount' => 18000,
            'sale_price_amount' => 12000, // discounted price
        ]);

        $this->assertDatabaseHas('product_price_histories', [
            'product_variant_id' => $variant->id,
            'regular_price_amount' => 18000,
            'sale_price_amount' => 12000,
        ]);

        // 3. Fetch product details from Catalog API
        $response = $this->getJson(route('api.catalog.show', ['slug' => 'omnibus-shoe']));
        $response->assertOk();

        // 4. Assert that the lowest price in the last 30 days is correct (12000 vs 15000)
        $productData = $response->json('data');
        $this->assertEquals(12000, $productData['lowest_price_last_30_days']);
        $this->assertEquals(12000, $productData['variants'][0]['lowest_price_last_30_days']);
    }

    public function test_eu_import_flat_duty(): void
    {
        // 1. Create a product shipped from outside EU
        $product = Product::factory()->public()->create([
            'slug' => 'outside-item',
            'type' => 'physical',
            'regular_price_amount' => 10000,
            'hs_code' => '85171300',
            'is_shipped_from_outside_eu' => true,
        ]);

        // Configure settings with EU import flat duty enabled and exchange rate for EUR
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $settingsModel->update([
            'allow_guest_checkout' => true,
            'eu_import_flat_duty_enabled' => true,
            'exchange_rates' => [
                'EUR' => 0.23, // 1 PLN = 0.23 EUR
            ],
            'shipping_zones' => [], // Empty means defaults to Poland allowed, no zone prices checked
            'shipping_methods' => [
                [
                    'code' => 'flat_rate',
                    'name' => 'Standard',
                    'amount' => 1500,
                ]
            ],
        ]);

        // Placing a checkout to PL.
        // Duty: 3 EUR = 300 / 0.23 = 1304 PLN cents.
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'outside-item', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'duty-buyer@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ],
            'shipping_address' => [
                'country_code' => 'PL',
                'city' => 'Warszawa',
                'street' => 'Marszałkowska 1',
                'postal_code' => '00-001',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);
        $order = Order::where('customer_email', 'duty-buyer@example.com')->first();
        $this->assertNotNull($order);
        
        // Let's verify metadata contains importDutyAmount
        $quote = data_get($order->metadata, 'quote');
        $this->assertNotNull($quote);
        $this->assertEquals(1304, $quote['import_duty_amount']);
        
        // Total amount = subtotal (10000) + shipping (1500) + duty (1304) = 12804
        $this->assertEquals(12804, $order->total_amount);
    }

    public function test_privileged_entrepreneur_declarations(): void
    {
        // 1. Create a physical product
        $product = Product::factory()->public()->create([
            'slug' => 'business-book',
            'type' => 'physical',
        ]);

        // Configure shipping
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $settingsModel->update([
            'allow_guest_checkout' => true,
            'shipping_zones' => [], // Empty defaults to PL
            'shipping_methods' => [
                [
                    'code' => 'flat_rate',
                    'name' => 'Standard',
                    'amount' => 1500,
                ]
            ],
        ]);

        // Place checkout with is_privileged_entrepreneur = true, wants_invoice = true, and a valid Polish NIP
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'business-book', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate',
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'jdg@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'wants_invoice' => true,
                'company_name' => 'Jan Kowalski Consulting',
                'nip' => '7740001454', // Valid Orlen NIP
                'is_privileged_entrepreneur' => true,
            ],
            'shipping_address' => [
                'country_code' => 'PL',
                'city' => 'Warszawa',
                'street' => 'Marszałkowska 1',
                'postal_code' => '00-001',
            ],
            'billing_address' => [
                'country_code' => 'PL',
                'city' => 'Warszawa',
                'street' => 'Marszałkowska 1',
                'postal_code' => '00-001',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);
        $order = Order::where('customer_email', 'jdg@example.com')->first();
        $this->assertNotNull($order);
        $this->assertTrue((bool)$order->is_privileged_entrepreneur);
    }

    public function test_cookie_consent_logging(): void
    {
        $response = $this->postJson(route('api.cookie-consents.store'), [
            'consent_token' => 'test-token-12345',
            'banner_version' => '2026-06-21',
            'consent_choices' => [
                'necessary' => true,
                'analytics' => true,
                'functional' => false,
                'marketing' => true,
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cookie_consents', [
            'consent_token' => 'test-token-12345',
            'banner_version' => '2026-06-21',
        ]);
        
        $consent = \App\Models\CookieConsent::query()->first();
        $this->assertNotNull($consent);
        $this->assertTrue($consent->consent_choices['analytics']);
        $this->assertFalse($consent->consent_choices['functional']);
    }

    public function test_return_eligibility_for_paid_placed_order(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $order = Order::query()->create([
            'number' => 'ORD-PLACED-PAID',
            'customer_email' => 'placedpaid@example.com',
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'status' => 'placed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 1870,
            'total_amount' => 10000,
        ]);

        $product = Product::factory()->public()->create([
            'name' => 'RMA Product',
            'slug' => 'rma-product',
            'type' => 'physical',
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => 'physical',
            'sku' => 'RMA-PROD',
            'name' => 'RMA Product',
            'quantity' => 1,
            'unit_price_amount' => 10000,
            'regular_unit_price_amount' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 1870,
            'total_amount' => 10000,
        ]);

        // Submit return request. It should succeed (201) because status is 'placed' and payment_status is 'paid'
        $response = $this->postJson(route('api.returns.store'), [
            'order_number' => 'ORD-PLACED-PAID',
            'customer_email' => 'placedpaid@example.com',
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('order_returns', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_order_details_can_be_viewed_in_any_status(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-COMPLETED-DETAILS',
            'customer_email' => 'details@example.com',
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'status' => 'completed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 1870,
            'total_amount' => 10000,
        ]);

        // Request details. Should succeed (200) instead of 404
        $response = $this->getJson("/api/checkout/orders/{$order->number}?email=details@example.com");
        $response->assertOk()
            ->assertJsonPath('data.order.number', 'ORD-COMPLETED-DETAILS')
            ->assertJsonPath('data.order.status', 'completed');
    }

    public function test_cannot_return_more_than_purchased_quantity_with_multiple_returns(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $order = Order::query()->create([
            'number' => 'ORD-RMA-QUANTITY-TEST',
            'customer_email' => 'rma-qty@example.com',
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'status' => 'completed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 1870,
            'total_amount' => 10000,
        ]);

        $product = Product::factory()->public()->create([
            'name' => 'RMA Product',
            'slug' => 'rma-product-qty',
            'type' => 'physical',
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => 'physical',
            'sku' => 'RMA-PROD-QTY',
            'name' => 'RMA Product',
            'quantity' => 2, // bought 2 pieces
            'unit_price_amount' => 5000,
            'regular_unit_price_amount' => 5000,
            'discount_amount' => 0,
            'tax_amount' => 935,
            'total_amount' => 10000,
        ]);

        // 1. Return 1 piece -> Should succeed
        $response = $this->postJson(route('api.returns.store'), [
            'order_number' => 'ORD-RMA-QUANTITY-TEST',
            'customer_email' => 'rma-qty@example.com',
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
            ],
        ]);
        $response->assertStatus(201);

        // 2. Try to return 2 more pieces (total requested would be 3, exceeding 2) -> Should fail
        $response2 = $this->postJson(route('api.returns.store'), [
            'order_number' => 'ORD-RMA-QUANTITY-TEST',
            'customer_email' => 'rma-qty@example.com',
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 2],
            ],
        ]);
        $response2->assertStatus(422)
            ->assertJsonFragment(['message' => 'Ilość do zwrotu (2) przekracza dostępną ilość do zwrotu (1) dla produktu: RMA Product. Wcześniej zgłoszono już 1 szt.']);

        // 3. Return the remaining 1 piece -> Should succeed
        $response3 = $this->postJson(route('api.returns.store'), [
            'order_number' => 'ORD-RMA-QUANTITY-TEST',
            'customer_email' => 'rma-qty@example.com',
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
            ],
        ]);
        $response3->assertStatus(201);

        // 4. Try to return 1 more piece (total requested would be 3, exceeding 2) -> Should fail
        $response4 = $this->postJson(route('api.returns.store'), [
            'order_number' => 'ORD-RMA-QUANTITY-TEST',
            'customer_email' => 'rma-qty@example.com',
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
            ],
        ]);
        $response4->assertStatus(422)
            ->assertJsonFragment(['message' => 'Ilość do zwrotu (1) przekracza dostępną ilość do zwrotu (0) dla produktu: RMA Product. Wcześniej zgłoszono już 2 szt.']);
    }
}
