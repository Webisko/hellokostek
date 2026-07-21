<?php

namespace Tests\Feature\Api;

use App\Mail\AbandonedCartMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AbandonedCartRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        // Ensure default store settings exist
        $settings = app(\App\Support\StoreSettings::class);
        $settingsModel = $settings->model();
        
        $metadata = $settingsModel->metadata ?? [];
        $metadata = array_merge($metadata, [
            'abandoned_cart_recovery_enabled' => true,
            'abandoned_cart_recovery_delay_hours' => 2,
            'abandoned_cart_recovery_discount_enabled' => true,
            'abandoned_cart_recovery_discount_percentage' => 10,
            'abandoned_cart_recovery_discount_duration_days' => 3,
            'abandoned_cart_recovery_url' => 'http://localhost:3000/checkout?resume_draft={number}',
        ]);

        $settingsModel->update([
            'allow_guest_checkout' => true,
            'metadata' => $metadata,
        ]);
    }

    public function test_abandoned_cart_recovery_command_detects_and_processes_abandoned_carts(): void
    {
        $order = Order::query()->create([
            'number' => 'DRAFT-10001',
            'status' => 'draft',
            'payment_status' => 'pending',
            'currency' => 'PLN',
            'total_amount' => 12300,
            'customer_email' => 'abandoned@test.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => 'Produkt Testowy',
            'sku' => 'PROD-1',
            'quantity' => 1,
            'unit_price_amount' => 12300,
            'total_amount' => 12300,
            'product_type' => 'physical',
        ]);

        // Manually update updated_at using query builder to bypass Eloquent event timestamp resetting
        Order::query()->where('id', $order->id)->update([
            'updated_at' => now()->subHours(3),
        ]);

        $exitCode = Artisan::call('app:recover-abandoned-carts');
        $this->assertEquals(0, $exitCode);

        // Verify email was sent
        Mail::assertSent(AbandonedCartMail::class, function ($mail) {
            return $mail->hasTo('abandoned@test.com');
        });

        // Verify coupon was created
        $coupon = Coupon::query()->where('discount_type', 'percentage')->where('value', 10)->first();
        $this->assertNotNull($coupon);
        $this->assertStringStartsWith('WROC-', $coupon->code);

        // Verify metadata was updated
        $order->refresh();
        $this->assertTrue(data_get($order->metadata, 'abandoned_email_sent'));
        $this->assertEquals($coupon->code, data_get($order->metadata, 'recovery_coupon_code'));
        $this->assertEquals(10, data_get($order->metadata, 'recovery_discount_percent'));
        $this->assertStringContainsString('resume_draft=DRAFT-10001', data_get($order->metadata, 'recovery_link'));
        $this->assertStringContainsString('coupon_code=' . $coupon->code, data_get($order->metadata, 'recovery_link'));
    }

    public function test_abandoned_cart_recovery_ignores_recently_updated_drafts(): void
    {
        $order = Order::query()->create([
            'number' => 'DRAFT-10002',
            'status' => 'draft',
            'payment_status' => 'pending',
            'currency' => 'PLN',
            'total_amount' => 12300,
            'customer_email' => 'recent@test.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => 'Produkt Testowy',
            'sku' => 'PROD-1',
            'quantity' => 1,
            'unit_price_amount' => 12300,
            'total_amount' => 12300,
            'product_type' => 'physical',
        ]);

        // updated_at is now() (less than 2 hours threshold)
        $exitCode = Artisan::call('app:recover-abandoned-carts');
        $this->assertEquals(0, $exitCode);

        // Verify no email was sent
        Mail::assertNotSent(AbandonedCartMail::class);

        $order->refresh();
        $this->assertNull(data_get($order->metadata, 'abandoned_email_sent'));
    }

    public function test_checkout_placement_with_draft_number_converts_draft(): void
    {
        // 1. Create a draft order
        $draft = Order::query()->create([
            'number' => 'DRAFT-20001',
            'status' => 'draft',
            'payment_status' => 'pending',
            'currency' => 'PLN',
            'total_amount' => 10000,
            'customer_email' => 'buyer@test.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
        ]);

        OrderItem::query()->create([
            'order_id' => $draft->id,
            'name' => 'Item 1',
            'sku' => 'I1',
            'quantity' => 1,
            'unit_price_amount' => 10000,
            'total_amount' => 10000,
            'product_type' => 'physical',
        ]);

        // Create product so validation in checkout place passes
        $product = \App\Models\Product::query()->create([
            'sku' => 'I1',
            'name' => 'Item 1',
            'slug' => 'item-1',
            'regular_price_amount' => 10000,
            'type' => 'physical',
        ]);

        // Place checkout order and pass draft_number
        $payload = [
            'items' => [
                ['slug' => 'item-1', 'quantity' => 1]
            ],
            'shipping_method_code' => 'flat_rate:courier',
            'payment_method' => 'stripe',
            'terms_accepted' => true,
            'draft_number' => 'DRAFT-20001',
            'customer' => [
                'email' => 'buyer@test.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'phone' => '123456789',
                'terms_accepted' => true,
            ]
        ];

        $response = $this->postJson(route('api.checkout.place'), $payload);
        $response->assertStatus(201);

        $newOrderNumber = $response->json('data.order.number');

        // Verify draft was converted
        $draft->refresh();
        $this->assertEquals('converted', $draft->status);
        $this->assertEquals($newOrderNumber, data_get($draft->metadata, 'converted_to_order_number'));
        $this->assertNotNull(data_get($draft->metadata, 'converted_at'));
    }
}
