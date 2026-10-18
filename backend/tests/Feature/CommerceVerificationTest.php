<?php

namespace Tests\Feature;

use App\Models\ContactInquiry;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TransactionalEmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommerceVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_original_artwork_stock_cannot_be_oversold(): void
    {
        // 1. Create a product with stock = 1 (unique original artwork)
        $product = Product::create([
            'name' => 'Akwarela Unikatowa',
            'slug' => 'akwarela-unikatowa-test',
            'sku' => 'AKW-TEST',
            'type' => 'physical',
            'price_amount' => 120000, // 1200.00 PLN
            'regular_price_amount' => 120000,
            'stock_quantity' => 1,
            'manages_stock' => true,
            'is_active' => true,
            'is_visible' => true,
            'has_original' => true,
        ]);

        $this->assertEquals(1, $product->stock_quantity);

        // Place draft or checkout order for this item
        $payload = [
            'items' => [
                ['slug' => $product->slug, 'quantity' => 1]
            ],
            'payment_method' => 'stripe',
            'customer' => [
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'email' => 'jan.kowalski@example.com',
                'phone' => '+48600100200',
            ],
            'shipping_address' => [
                'street' => 'Piotrkowska 1',
                'postal_code' => '90-001',
                'city' => 'Łódź',
                'country' => 'PL',
            ],
            'shipping_method_code' => 'free_courier',
            'terms_accepted' => true,
        ];

        // 2. Order 1
        $response1 = $this->postJson('/api/checkout/place', $payload);
        $response1->assertStatus(201);

        // Product stock is decremented to 0
        $this->assertEquals(0, $product->fresh()->stock_quantity);

        // 3. Second order attempt for the same unique product must fail
        $payloadCustomer2 = $payload;
        $payloadCustomer2['customer']['email'] = 'anna.nowak@example.com';

        $response2 = $this->postJson('/api/checkout/place', $payloadCustomer2);
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_contact_inquiry_with_photos_creates_record_and_log(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('portret_pieska.jpg', 600, 600);

        $response = $this->postJson('/api/inquiries', [
            'name' => 'Zofia Nowak',
            'email' => 'zofia@example.com',
            'phone' => '+48500200300',
            'subject' => 'portrait_commission',
            'message' => 'Dzień dobry, proszę o wycenę portretu pieska ze zdjęcia na formacie A3.',
            'files' => [$file],
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        // Assert record exists in database
        $inquiry = ContactInquiry::where('email', 'zofia@example.com')->first();
        $this->assertNotNull($inquiry);
        $this->assertEquals('Zofia Nowak', $inquiry->name);
        $this->assertNotNull($inquiry->payload);
        $this->assertArrayHasKey('attachments', $inquiry->payload);

        // Assert transactional email log was created
        $this->assertDatabaseHas('transactional_email_logs', [
            'email_type' => 'contact_inquiry_admin',
        ]);
    }

    public function test_checkout_with_original_variant_uses_original_price_and_decrements_variant_stock(): void
    {
        $product = Product::create([
            'name' => 'Obraz Testowy z Wariantami',
            'slug' => 'obraz-testowy-warianty',
            'sku' => 'OBR-VAR',
            'type' => 'physical',
            'regular_price_amount' => 3000, // 30 PLN print base
            'stock_quantity' => 999,
            'manages_stock' => true,
            'is_active' => true,
            'is_visible' => true,
            'is_purchasable' => true,
            'metadata' => ['year' => '2025'],
        ]);

        Product::syncVariantsFromData($product, [
            'print_regular_price' => 30,
            'print_stock_quantity' => 999,
            'has_original' => true,
            'original_regular_price' => 300,
            'original_stock_quantity' => 1,
        ]);

        $originalVariant = $product->variants()->where('sku', 'OBR-VAR-OR')->firstOrFail();
        $printVariant = $product->variants()->where('sku', 'OBR-VAR-PR')->firstOrFail();

        $this->assertEquals(30000, $originalVariant->regular_price_amount);
        $this->assertEquals(3000, $printVariant->regular_price_amount);
        $this->assertTrue($product->fresh()->metadata['original_available']);

        $payload = [
            'items' => [
                [
                    'slug' => $product->slug,
                    'quantity' => 1,
                    'variant_id' => $originalVariant->id,
                    'product_variant_id' => $originalVariant->id,
                ]
            ],
            'payment_method' => 'bank_transfer',
            'customer' => [
                'first_name' => 'Anna',
                'last_name' => 'Kowalska',
                'email' => 'anna@example.com',
                'phone' => '+48600700800',
            ],
            'shipping_address' => [
                'street' => 'Piotrkowska 10',
                'postal_code' => '90-001',
                'city' => 'Łódź',
                'country' => 'PL',
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'terms_accepted' => true,
        ];

        $response = $this->postJson('/api/checkout/place', $payload);
        $response->assertStatus(201);
        $this->assertEquals(30000, $response->json('data.order.subtotal_amount'));
        $this->assertEquals(0, $originalVariant->fresh()->stock_quantity);
    }
}
