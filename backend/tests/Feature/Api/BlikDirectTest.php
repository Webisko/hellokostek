<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlikDirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.przelewy24.enabled' => true,
            'services.przelewy24.merchant_id' => 12345,
            'services.przelewy24.pos_id' => 12345,
            'services.przelewy24.crc' => 'mock_crc_key',
            'services.przelewy24.api_key' => 'mock_api_key',
            'services.przelewy24.api_base_url' => 'https://sandbox.przelewy24.pl/api/v1',
        ]);
    }

    public function test_fails_with_invalid_blik_code(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-BLIK-1',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 10000,
            'customer_email' => 'blik@example.com',
            'customer_first_name' => 'Anna',
            'customer_last_name' => 'Kowalska',
            'metadata' => [
                'payment' => [
                    'provider' => 'przelewy24',
                ],
            ],
        ]);

        // Non-digit code
        $response = $this->postJson(
            "/api/checkout/orders/{$order->number}/payment-session",
            ['blik_code' => '123a56'],
            ['X-Order-Email' => $order->customer_email]
        );
        $response->assertStatus(422);

        // Short code
        $response = $this->postJson(
            "/api/checkout/orders/{$order->number}/payment-session",
            ['blik_code' => '12345'],
            ['X-Order-Email' => $order->customer_email]
        );
        $response->assertStatus(422);
    }

    public function test_successful_direct_blik_payment_session(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-BLIK-2',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 15000,
            'customer_email' => 'blik2@example.com',
            'customer_first_name' => 'Anna',
            'customer_last_name' => 'Kowalska',
            'metadata' => [
                'payment' => [
                    'provider' => 'przelewy24',
                ],
            ],
        ]);

        // Fake both Register Transaction and direct byCode endpoints
        Http::fake([
            'sandbox.przelewy24.pl/api/v1/transaction/register' => Http::response([
                'responseCode' => 0,
                'data' => [
                    'token' => 'mock_p24_transaction_token_123',
                ],
            ], 200),
            'sandbox.przelewy24.pl/api/v1/transaction/byCode' => Http::response([
                'responseCode' => 0,
                'data' => [
                    'status' => 'success',
                ],
            ], 200),
        ]);

        $response = $this->postJson(
            "/api/checkout/orders/{$order->number}/payment-session",
            ['blik_code' => '123456'],
            ['X-Order-Email' => $order->customer_email]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('data.payment_session.provider', 'przelewy24');
        $response->assertJsonPath('data.payment_session.status', 'initiated');
        $response->assertJsonPath('data.payment_session.redirect_url', null);
        $response->assertJsonPath('data.payment_session.next_action', 'await_blik_confirmation');

        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'provider' => 'przelewy24',
            'status' => 'initiated',
            'redirect_url' => null,
        ]);
    }
}
