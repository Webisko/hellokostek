<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure default store settings exist
        app(StoreSettings::class)->model();
    }

    public function test_checkout_fails_without_terms_acceptance(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'type' => 'physical',
        ]);

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
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['terms_accepted']);
    }

    public function test_checkout_saves_terms_version_in_metadata(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'type' => 'physical',
        ]);

        // Set terms version in settings
        $settings = app(StoreSettings::class);
        $settingsModel = $settings->model();
        $metadata = $settingsModel->metadata ?? [];
        $metadata['terms_version'] = '2026-06-14';
        $settingsModel->update(['metadata' => $metadata]);

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
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);
        
        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertEquals('2026-06-14', data_get($order->metadata, 'terms_acceptance.version'));
        $this->assertTrue(data_get($order->metadata, 'terms_acceptance.accepted'));
    }

    public function test_checkout_fails_without_digital_consent_when_digital_product_in_cart(): void
    {
        $digitalProduct = Product::factory()->public()->create([
            'slug' => 'digital-product',
            'type' => 'digital',
        ]);

        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'digital-product', 'quantity' => 1]
            ],
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'customer@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['digital_consent']);
    }

    public function test_checkout_saves_digital_consent_in_metadata(): void
    {
        $digitalProduct = Product::factory()->public()->create([
            'slug' => 'digital-product',
            'type' => 'digital',
        ]);

        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'digital-product', 'quantity' => 1]
            ],
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'customer@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ],
            'terms_accepted' => true,
            'digital_consent' => true,
        ]);

        $response->assertStatus(201);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertTrue(data_get($order->metadata, 'digital_consent.accepted'));
        $this->assertNotNull(data_get($order->metadata, 'digital_consent.accepted_at'));
    }

    public function test_cod_order_generates_invoice_on_shipped_status_change(): void
    {
        // Mock email delivery
        \Illuminate\Support\Facades\Mail::fake();

        $product = Product::factory()->public()->create([
            'slug' => 'test-product',
            'type' => 'physical',
        ]);

        $response = $this->postJson(route('api.checkout.place'), [
            'items' => [
                ['slug' => 'test-product', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:cod',
            'payment_method' => 'cod',
            'customer' => [
                'email' => 'customer@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);
        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertTrue($order->isCod());

        $invoicePath = storage_path('app/invoices/' . $order->number . '.pdf');
        if (file_exists($invoicePath)) {
            unlink($invoicePath);
        }

        // Initially proforma/unshipped - no invoice
        $this->assertFileDoesNotExist($invoicePath);

        // Update status to shipped
        $order->status = 'shipped';
        $order->save();

        // Should generate invoice PDF
        $this->assertFileExists($invoicePath);
        
        // Cleanup generated test invoice
        if (file_exists($invoicePath)) {
            unlink($invoicePath);
        }
    }
}
