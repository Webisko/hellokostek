<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\NewsletterSubscriber;
use App\Support\VatOssHelper;
use App\Support\MinimalPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VatOssAndMppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure default store settings exist and support EU shipping (specifically Germany and France)
        $settings = app(\App\Support\StoreSettings::class);
        $settingsModel = $settings->model();
        $settingsModel->update([
            'shipping_zones' => [
                [
                    'code' => 'EU',
                    'name' => 'Europe',
                    'countries' => ['DE', 'FR'],
                ]
            ],
        ]);
    }

    public function test_vat_oss_helper_behavior(): void
    {
        $this->assertEquals('DE', VatOssHelper::resolveCountryCode(['country_code' => 'de']));
        $this->assertEquals('PL', VatOssHelper::resolveCountryCode(['country_code' => 'PL']));
        $this->assertEquals('PL', VatOssHelper::resolveCountryCode(['country_code' => 'POLSKA']));
        $this->assertTrue(VatOssHelper::isEuCountryOtherThanPoland('DE'));
        $this->assertFalse(VatOssHelper::isEuCountryOtherThanPoland('PL'));
        $this->assertFalse(VatOssHelper::isEuCountryOtherThanPoland('US'));
        $this->assertEquals(19, VatOssHelper::getVatRateForCountry('DE'));
        $this->assertEquals(20, VatOssHelper::getVatRateForCountry('FR'));
    }

    public function test_checkout_b2c_with_vat_oss(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'type' => 'physical',
            'vat_rate' => 23,
            'regular_price_amount' => 10000, // 100.00 PLN
            'sale_price_amount' => 10000,
        ]);

        // Place checkout order for Germany (DE) - should calculate 19% VAT
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'test-product', 'quantity' => 1]
            ],
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'customer@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ],
            'shipping_address' => [
                'street' => 'Musterstrasse 1',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);
        $order = Order::query()->first();
        $this->assertNotNull($order);
        
        $item = $order->items->first();
        $this->assertNotNull($item);
        // Metadata must store applied vat rate of 19%
        $this->assertEquals(19, data_get($item->metadata, 'vat_rate'));

        // Let's verify total tax amount for item: total_amount = 10000.
        // Net = 10000 / 1.19 = 8403. Tax = 10000 - 8403 = 1597 groszy.
        $this->assertEquals(1597, $item->tax_amount);
    }

    public function test_checkout_b2b_does_not_apply_vat_oss(): void
    {
        Http::fake([
            'ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number' => Http::response([
                'valid' => true,
                'name' => 'Test Company GmbH',
                'address' => 'Musterstrasse 1, Berlin',
            ], 200)
        ]);

        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'type' => 'physical',
            'vat_rate' => 23,
            'regular_price_amount' => 10000, // 100.00 PLN (Gross)
            'sale_price_amount' => 10000,
        ]);

        // Place B2B checkout order for Germany (DE) with NIP - should apply Reverse Charge (0% VAT)
        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'test-product', 'quantity' => 1]
            ],
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'customer@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'company_name' => 'Test Company GmbH',
                'nip' => 'DE123456789',
                'wants_invoice' => true,
            ],
            'shipping_address' => [
                'street' => 'Musterstrasse 1',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'billing_address' => [
                'street' => 'Musterstrasse 1',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);
        $order = Order::query()->first();
        $this->assertNotNull($order);
        
        $item = $order->items->first();
        $this->assertEquals(0, data_get($item->metadata, 'vat_rate'));
        // Net = 10000 / 1.23 = 8130 groszy. Tax = 0.
        $this->assertEquals(8130, $item->total_amount);
        $this->assertEquals(0, $item->tax_amount);
    }

    public function test_split_payment_text_appended_for_high_value_b2b_pln_orders(): void
    {
        // 1. B2B, PLN, total >= 15000 PLN (1,500,000 groszy)
        $order1 = Order::query()->create([
            'number' => 'ORD-MPP-YES',
            'customer_email' => 'b2b@example.com',
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'currency' => 'PLN',
            'billing_nip' => '1234567890',
            'billing_company_name' => 'Test Sp. z o.o.',
            'total_amount' => 1500000,
            'payment_status' => 'paid',
        ]);
        $pdf1 = MinimalPdfGenerator::generateInvoice($order1);
        $this->assertStringContainsString('Mechanizm podzielonej platnosci', $pdf1);

        // 2. B2B, PLN, total < 15000 PLN
        $order2 = Order::query()->create([
            'number' => 'ORD-MPP-NO-PRICE',
            'customer_email' => 'b2b@example.com',
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'currency' => 'PLN',
            'billing_nip' => '1234567890',
            'billing_company_name' => 'Test Sp. z o.o.',
            'total_amount' => 1499900,
            'payment_status' => 'paid',
        ]);
        $pdf2 = MinimalPdfGenerator::generateInvoice($order2);
        $this->assertStringNotContainsString('Mechanizm podzielonej platnosci', $pdf2);

        // 3. B2C (No NIP), PLN, total >= 15000 PLN
        $order3 = Order::query()->create([
            'number' => 'ORD-MPP-NO-B2B',
            'customer_email' => 'b2c@example.com',
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'currency' => 'PLN',
            'billing_nip' => null,
            'total_amount' => 1600000,
            'payment_status' => 'paid',
        ]);
        $pdf3 = MinimalPdfGenerator::generateInvoice($order3);
        $this->assertStringNotContainsString('Mechanizm podzielonej platnosci', $pdf3);
    }

    public function test_cleanup_commands(): void
    {
        // Insert newsletter subscribers via raw DB to preserve custom timestamps
        DB::table('newsletter_subscribers')->insert([
            'email' => 'old_pending@example.com',
            'status' => 'pending',
            'created_at' => now()->subDays(15)->toDateTimeString(),
            'updated_at' => now()->subDays(15)->toDateTimeString(),
        ]);
        DB::table('newsletter_subscribers')->insert([
            'email' => 'new_pending@example.com',
            'status' => 'pending',
            'created_at' => now()->subDays(5)->toDateTimeString(),
            'updated_at' => now()->subDays(5)->toDateTimeString(),
        ]);
        DB::table('newsletter_subscribers')->insert([
            'email' => 'old_active@example.com',
            'status' => 'active',
            'created_at' => now()->subDays(20)->toDateTimeString(),
            'updated_at' => now()->subDays(20)->toDateTimeString(),
        ]);

        Artisan::call('app:cleanup-pending-subscribers', ['--days' => 14]);

        $this->assertDatabaseMissing('newsletter_subscribers', ['email' => 'old_pending@example.com']);
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'new_pending@example.com']);
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'old_active@example.com']);

        // Insert orders via raw DB to preserve custom timestamps
        DB::table('orders')->insert([
            'number' => 'ORD-OLD-DRAFT',
            'customer_email' => 'client@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'status' => 'draft',
            'created_at' => now()->subDays(31)->toDateTimeString(),
            'updated_at' => now()->subDays(31)->toDateTimeString(),
        ]);
        DB::table('orders')->insert([
            'number' => 'ORD-NEW-DRAFT',
            'customer_email' => 'client@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'status' => 'draft',
            'created_at' => now()->subDays(10)->toDateTimeString(),
            'updated_at' => now()->subDays(10)->toDateTimeString(),
        ]);
        DB::table('orders')->insert([
            'number' => 'ORD-OLD-PLACED',
            'customer_email' => 'client@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'status' => 'placed',
            'created_at' => now()->subDays(40)->toDateTimeString(),
            'updated_at' => now()->subDays(40)->toDateTimeString(),
        ]);

        Artisan::call('app:cleanup-abandoned-carts', ['--days' => 30]);

        $this->assertDatabaseMissing('orders', ['number' => 'ORD-OLD-DRAFT']);
        $this->assertDatabaseHas('orders', ['number' => 'ORD-NEW-DRAFT']);
        $this->assertDatabaseHas('orders', ['number' => 'ORD-OLD-PLACED']);
    }
}
