<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\BackInStockSubscription;
use App\Models\TransactionalEmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BackInStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_subscribe_to_out_of_stock_product(): void
    {
        $product = Product::factory()->create([
            'manages_stock' => true,
            'stock_quantity' => 0,
        ]);

        $response = $this->postJson('/api/catalog/products/back-in-stock-subscribe', [
            'email' => 'test@example.com',
            'product_id' => $product->id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('back_in_stock_subscriptions', [
            'email' => 'test@example.com',
            'product_id' => $product->id,
            'product_variant_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_subscribe_if_already_in_stock(): void
    {
        $product = Product::factory()->create([
            'manages_stock' => true,
            'stock_quantity' => 5,
        ]);

        $response = $this->postJson('/api/catalog/products/back-in-stock-subscribe', [
            'email' => 'test@example.com',
            'product_id' => $product->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_notification_sent_when_product_stock_reconfigured(): void
    {
        Mail::fake();

        $product = Product::factory()->create([
            'manages_stock' => true,
            'stock_quantity' => 0,
        ]);

        BackInStockSubscription::create([
            'email' => 'customer@example.com',
            'product_id' => $product->id,
            'status' => 'pending',
        ]);

        // Replenish stock
        $product->stock_quantity = 10;
        $product->save();

        Mail::assertSent(\App\Mail\BackInStockMail::class, function ($mail) use ($product) {
            return $mail->hasTo('customer@example.com') && $mail->product->id === $product->id;
        });

        $this->assertDatabaseHas('back_in_stock_subscriptions', [
            'email' => 'customer@example.com',
            'product_id' => $product->id,
            'status' => 'notified',
        ]);

        $this->assertDatabaseHas('transactional_email_logs', [
            'email_type' => 'back_in_stock_notification',
            'recipient' => 'customer@example.com',
        ]);
    }
}
