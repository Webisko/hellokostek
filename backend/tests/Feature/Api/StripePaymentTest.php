<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.enabled' => true,
            'services.stripe.key' => 'pk_test_mock',
            'services.stripe.secret' => 'sk_test_mock',
            'services.stripe.webhook_secret' => 'whsec_mock',
        ]);
    }

    public function test_payment_session_initiation_when_stripe_not_configured(): void
    {
        config([
            'services.stripe.secret' => null,
        ]);

        $order = Order::query()->create([
            'number' => 'ORD-12345',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 10000,
            'customer_email' => 'test@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'metadata' => [
                'payment' => [
                    'provider' => 'stripe',
                ],
            ],
        ]);

        $response = $this->postJson(
            "/api/checkout/orders/{$order->number}/payment-session",
            [],
            ['X-Order-Email' => $order->customer_email]
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.payment_session.provider', 'stripe')
            ->assertJsonPath('data.payment_session.status', 'configuration_required')
            ->assertJsonPath('data.payment_session.next_action', 'configure_stripe_credentials');
    }

    public function test_webhook_returns_400_for_invalid_signature(): void
    {
        $payload = json_encode([
            'id' => 'evt_123',
            'type' => 'checkout.session.completed',
        ]);

        $response = $this->postJson(
            '/api/integrations/stripe/payment-callback',
            json_decode($payload, true),
            ['STRIPE_SIGNATURE' => 'invalid_sig']
        );

        $response->assertStatus(400);
    }

    public function test_webhook_successfully_processes_completed_checkout(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-54321',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 15000,
            'customer_email' => 'test@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'initiated',
            'amount' => 15000,
            'currency' => 'PLN',
            'external_session_id' => 'cs_test_session123',
            'metadata' => [
                'stripe_session_id' => 'cs_test_session123',
            ],
        ]);

        $payload = json_encode([
            'id' => 'evt_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_session123',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'order_number' => 'ORD-54321',
                    ],
                ],
            ],
        ]);

        $time = time();
        $webhookSecret = 'whsec_mock';
        $signedPayload = "{$time}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $webhookSecret);
        $sigHeader = "t={$time},v1={$signature}";

        $response = $this->call(
            'POST',
            '/api/integrations/stripe/payment-callback',
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => $sigHeader,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'status' => 'confirmed',
        ]);
    }
}
