<?php

namespace Tests\Feature\Api;

use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewsletterDoubleOptInMail;
use Tests\TestCase;

class NewsletterDoubleOptInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(StoreSettings::class)->model();
    }

    public function test_standalone_subscription_creates_pending_subscriber_and_sends_email(): void
    {
        Mail::fake();

        $response = $this->postJson(route('api.newsletter.subscribe'), [
            'email' => 'subscriber@example.com',
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.subscriber.status', 'pending');
        $response->assertJsonPath('data.subscriber.is_active', false);

        $subscriber = NewsletterSubscriber::query()->where('email', 'subscriber@example.com')->first();
        $this->assertNotNull($subscriber);
        $this->assertEquals('pending', $subscriber->status);
        $this->assertFalse($subscriber->is_active);
        $this->assertNotNull($subscriber->double_opt_in_token);
        $this->assertNull($subscriber->consented_at);

        Mail::assertSent(NewsletterDoubleOptInMail::class, function (NewsletterDoubleOptInMail $mail) use ($subscriber) {
            return $mail->subscriber->id === $subscriber->id && $mail->hasTo('subscriber@example.com');
        });
    }

    public function test_confirming_subscription_updates_status_to_active_and_logs_ip(): void
    {
        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'subscriber@example.com',
            'first_name' => 'Jan',
            'status' => 'pending',
            'is_active' => false,
            'double_opt_in_token' => 'test-token-12345',
            'double_opt_in_ip' => '127.0.0.1',
        ]);

        $response = $this->get(route('newsletter.confirm', ['token' => 'test-token-12345']), [
            'REMOTE_ADDR' => '192.168.1.50', // Mock client IP
        ]);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $response->assertRedirect($frontendUrl . '/newsletter/confirmed');

        $subscriber->refresh();
        $this->assertEquals('active', $subscriber->status);
        $this->assertTrue($subscriber->is_active);
        $this->assertNull($subscriber->double_opt_in_token);
        $this->assertNotNull($subscriber->double_opt_in_confirmed_at);
        $this->assertNotNull($subscriber->consented_at);
        // IP from the confirmation click is logged
        $this->assertEquals('192.168.1.50', $subscriber->double_opt_in_ip);
    }

    public function test_confirming_subscription_with_invalid_token_redirects_to_error(): void
    {
        $response = $this->get(route('newsletter.confirm', ['token' => 'invalid-token']));

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $response->assertRedirect($frontendUrl . '/newsletter/error');
    }

    public function test_checkout_with_marketing_consent_triggers_newsletter_subscription(): void
    {
        Mail::fake();

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
                'email' => 'checkout-sub@example.com',
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'marketing_accepted' => true,
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);

        // Check order metadata contains marketing consent log
        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertTrue(data_get($order->metadata, 'marketing_acceptance.accepted'));

        // Check newsletter subscriber record is created
        $subscriber = NewsletterSubscriber::query()->where('email', 'checkout-sub@example.com')->first();
        $this->assertNotNull($subscriber);
        $this->assertEquals('pending', $subscriber->status);
        $this->assertFalse($subscriber->is_active);

        // Check mail was sent
        Mail::assertSent(NewsletterDoubleOptInMail::class);
    }

    public function test_checkout_without_marketing_consent_does_not_subscribe_user(): void
    {
        Mail::fake();

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
                'email' => 'checkout-no-sub@example.com',
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'marketing_accepted' => false,
            ],
            'terms_accepted' => true,
        ]);

        $response->assertStatus(201);

        // Check order metadata does not have marketing consent log
        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertNull(data_get($order->metadata, 'marketing_acceptance'));

        // Check no subscriber record is created
        $subscriber = NewsletterSubscriber::query()->where('email', 'checkout-no-sub@example.com')->first();
        $this->assertNull($subscriber);

        // Check no mail was sent
        Mail::assertNotSent(NewsletterDoubleOptInMail::class);
    }

    public function test_unsubscribe_via_api_successfully(): void
    {
        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'active-sub@example.com',
            'status' => 'active',
            'is_active' => true,
            'consented_at' => now(),
        ]);

        $response = $this->postJson(route('api.newsletter.unsubscribe'), [
            'email' => 'active-sub@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.subscriber.status', 'unsubscribed');
        $response->assertJsonPath('data.subscriber.is_active', false);

        $subscriber->refresh();
        $this->assertEquals('unsubscribed', $subscriber->status);
        $this->assertFalse($subscriber->is_active);
        $this->assertNotNull($subscriber->unsubscribed_at);
        $this->assertNull($subscriber->double_opt_in_token);
    }

    public function test_unsubscribe_via_api_fails_if_not_found(): void
    {
        $response = $this->postJson(route('api.newsletter.unsubscribe'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_unsubscribe_via_signed_web_route_successfully(): void
    {
        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'active-sub-web@example.com',
            'status' => 'active',
            'is_active' => true,
            'consented_at' => now(),
        ]);

        $unsubscribeUrl = \Illuminate\Support\Facades\URL::signedRoute('newsletter.unsubscribe', [
            'email' => 'active-sub-web@example.com'
        ]);

        $response = $this->get($unsubscribeUrl);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $response->assertRedirect($frontendUrl . '/newsletter/unsubscribed');

        $subscriber->refresh();
        $this->assertEquals('unsubscribed', $subscriber->status);
        $this->assertFalse($subscriber->is_active);
        $this->assertNotNull($subscriber->unsubscribed_at);
    }

    public function test_unsubscribe_via_web_route_with_invalid_signature_fails(): void
    {
        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'active-sub-web-invalid@example.com',
            'status' => 'active',
            'is_active' => true,
            'consented_at' => now(),
        ]);

        $invalidUrl = route('newsletter.unsubscribe', [
            'email' => 'active-sub-web-invalid@example.com',
            'signature' => 'invalid-signature-value'
        ]);

        $response = $this->get($invalidUrl);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $response->assertRedirect($frontendUrl . '/newsletter/error');

        $subscriber->refresh();
        $this->assertEquals('active', $subscriber->status);
        $this->assertTrue($subscriber->is_active);
    }
}
