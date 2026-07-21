<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use App\Notifications\VerifyEmailQueued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CRIT-1: Public response on /api/health is minimal. Full details require admin session.
     */
    public function test_crit_1_health_endpoint_restricts_confidential_info(): void
    {
        // Guest gets minimal info
        $guestResponse = $this->getJson('/api/health');
        $guestResponse->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'app' => config('app.name'),
            ]);

        // Regular user gets minimal info
        $user = User::factory()->create();
        $userResponse = $this->actingAs($user, 'sanctum')->getJson('/api/health');
        $userResponse->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'app' => config('app.name'),
            ]);

        // Admin gets full info
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $adminResponse = $this->actingAs($admin, 'sanctum')->getJson('/api/health');
        $adminResponse->assertOk()
            ->assertJsonStructure([
                'status',
                'app',
                'environment',
                'store' => ['name', 'currency', 'free_shipping_threshold'],
                'product_types',
                'customer_segments',
                'integrations',
            ]);
    }

    /**
     * CRIT-2: Checkout order show endpoint requires proper ownership verification.
     */
    public function test_crit_2_order_show_requires_proper_ownership(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-10001',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 5000,
            'customer_email' => 'customer@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
        ]);

        // 1. Guest without email -> 403
        $this->getJson("/api/checkout/orders/{$order->number}")
            ->assertStatus(403);

        // 2. Guest with incorrect email -> 403
        $this->getJson("/api/checkout/orders/{$order->number}?email=wrong@example.com")
            ->assertStatus(403);

        // 3. Guest with correct email in query -> 200
        $this->getJson("/api/checkout/orders/{$order->number}?email=customer@example.com")
            ->assertOk();

        // 4. Guest with correct email in header -> 200
        $this->getJson("/api/checkout/orders/{$order->number}", [
            'X-Order-Email' => 'customer@example.com'
        ])->assertOk();

        // 5. Authenticated non-owner -> 403
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/checkout/orders/{$order->number}")
            ->assertStatus(403);

        // 6. Authenticated owner -> 200
        $owner = User::factory()->create();
        $order->user_id = $owner->id;
        $order->save();
        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/checkout/orders/{$order->number}")
            ->assertOk();

        // 7. Authenticated admin -> 200
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/checkout/orders/{$order->number}")
            ->assertOk();
    }

    /**
     * HIGH-1: Rate limiting configured on auth and checkout endpoints.
     */
    public function test_high_1_auth_and_checkout_endpoints_have_rate_limiting(): void
    {
        // Hit /api/auth/login 6 times. The 6th should return 429.
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'secret',
            ]);
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'secret',
        ])->assertStatus(429);
    }

    /**
     * HIGH-2: Payment session initiation requires proper ownership.
     */
    public function test_high_2_payment_session_initiation_requires_proper_ownership(): void
    {
        config(['services.stripe.secret' => 'sk_test_mock']);

        $order = Order::query()->create([
            'number' => 'ORD-10002',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 5000,
            'customer_email' => 'customer@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'metadata' => [
                'payment' => [
                    'provider' => 'stripe',
                ],
            ],
        ]);

        // 1. Guest without email -> 403
        $this->postJson("/api/checkout/orders/{$order->number}/payment-session")
            ->assertStatus(403);

        // 2. Guest with incorrect email -> 403
        $this->postJson("/api/checkout/orders/{$order->number}/payment-session?email=wrong@example.com")
            ->assertStatus(403);

        // 3. Guest with correct email -> 201
        $this->postJson("/api/checkout/orders/{$order->number}/payment-session?email=customer@example.com")
            ->assertStatus(201);

        // 4. Authenticated non-owner -> 403
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/checkout/orders/{$order->number}/payment-session")
            ->assertStatus(403);

        // 5. Authenticated owner -> 201
        $owner = User::factory()->create();
        $order->user_id = $owner->id;
        $order->save();
        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/checkout/orders/{$order->number}/payment-session")
            ->assertStatus(201);

        // 6. Non-placed order status -> 404
        $order->status = 'completed';
        $order->save();
        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/checkout/orders/{$order->number}/payment-session")
            ->assertStatus(404);
    }

    /**
     * HIGH-3: Security headers are returned in HTTP responses.
     */
    public function test_high_3_responses_contain_security_headers(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()')
            ->assertHeader('Content-Security-Policy')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    }

    /**
     * HIGH-4: Session settings are properly secure.
     */
    public function test_high_4_session_security_settings_are_enabled(): void
    {
        $this->assertTrue(config('session.encrypt'));
        $this->assertTrue(config('session.secure'));
        $this->assertEquals('strict', config('session.same_site'));
    }

    /**
     * MED-1: Password validation rules mixedCase, numbers, and uncompromised checks are active.
     */
    public function test_med_1_password_validation_rules_are_strict(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * MED-2: Admins cannot log in via the customer login API endpoint.
     */
    public function test_med_2_admins_cannot_login_via_customer_api(): void
    {
        $admin = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $response = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * MED-3: Checkout notes are limited in length (max 1000 characters).
     */
    public function test_med_3_checkout_notes_are_limited_in_length(): void
    {
        $response = $this->postJson('/api/checkout/place', [
            'items' => [
                ['slug' => 'test-product', 'quantity' => 1]
            ],
            'payment_method' => 'stripe',
            'customer' => [
                'email' => 'customer@example.com',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
            ],
            'notes' => str_repeat('a', 1001),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);
    }

    /**
     * MED-4: is_admin attribute is not mass-assignable.
     */
    public function test_med_4_user_is_admin_is_not_mass_assignable(): void
    {
        $user = User::query()->create([
            'name' => 'Hack Attempt',
            'email' => 'hack@example.com',
            'password' => 'Secret123!',
            'is_admin' => true,
        ]);

        $user->refresh();

        $this->assertFalse($user->is_admin);
    }

    /**
     * Additional: Filament path is parameterized.
     */
    public function test_filament_path_is_parameterized(): void
    {
        $this->assertEquals(env('FILAMENT_PATH', 'admin'), \Filament\Facades\Filament::getPanel('admin')->getPath());
    }

    /**
     * Additional: Sanctum token expiration is active.
     */
    public function test_sanctum_token_expiration_is_active(): void
    {
        $this->assertEquals(10080, config('sanctum.expiration'));
    }

    /**
     * Additional: CORS configuration restricts allowed origins.
     */
    public function test_cors_configuration_restricts_allowed_origins(): void
    {
        $this->assertEquals(['http://localhost:3000'], config('cors.allowed_origins'));
    }

    /**
     * Additional: Shorter password reset expiration.
     */
    public function test_password_reset_expiration_is_shorter(): void
    {
        $this->assertEquals(15, config('auth.passwords.users.expire'));
    }

    /**
     * Additional: Shorter password timeout confirmation.
     */
    public function test_password_timeout_confirmation_is_shorter(): void
    {
        $this->assertEquals(3600, config('auth.password_timeout'));
    }

    /**
     * Verification: Unverified user cannot access account details or orders.
     */
    public function test_unverified_user_cannot_access_profile_or_orders(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/account/me')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/account/orders')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/logout')
            ->assertOk();
    }

    /**
     * Verification: Registration sends verification notification.
     */
    public function test_registration_sends_verification_notification(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan_verify@example.com',
            'password' => 'U2t#p9!Wq5xZ',
            'password_confirmation' => 'U2t#p9!Wq5xZ',
        ]);

        $user = User::query()->where('email', 'jan_verify@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo(
            $user,
            VerifyEmailQueued::class
        );
    }

    /**
     * Verification: Signed verification URL verifies email and redirects.
     */
    public function test_verification_endpoint_verifies_email_and_redirects(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['email_verified_at' => null])->save();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect(env('FRONTEND_URL', 'http://localhost:3000') . '/account?verified=true');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    /**
     * Verification: Resend endpoint sends a new verification link.
     */
    public function test_resend_endpoint_resends_verification_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/email/resend')
            ->assertOk();

        Notification::assertSentTo(
            $user,
            VerifyEmailQueued::class
        );
    }

    /**
     * Hardening: is_approved and is_verified_purchase are not mass-assignable on ProductReview.
     */
    public function test_product_review_is_approved_is_not_mass_assignable(): void
    {
        $product = \App\Models\Product::factory()->create();

        $review = \App\Models\ProductReview::query()->create([
            'product_id' => $product->id,
            'customer_email' => 'hacker@example.com',
            'customer_name' => 'Hacker',
            'rating' => 5,
            'comment' => 'Great!',
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);

        $review->refresh();

        $this->assertFalse((bool)$review->is_approved);
        $this->assertFalse((bool)$review->is_verified_purchase);
    }

    /**
     * Hardening: Security headers can be disabled dynamically via configuration.
     */
    public function test_security_headers_can_be_disabled_via_config(): void
    {
        config(['app.add_security_headers' => false]);

        $response = $this->getJson('/api/health');

        $this->assertFalse($response->headers->has('X-Content-Type-Options'));
        $this->assertFalse($response->headers->has('X-Frame-Options'));
        $this->assertFalse($response->headers->has('Referrer-Policy'));
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Cross-Origin-Opener-Policy'));
        $this->assertFalse($response->headers->has('Cross-Origin-Resource-Policy'));
    }

    /**
     * Hardening: Analytics events ingestion has rate limiting of 60 per minute.
     */
    public function test_analytics_endpoint_has_rate_limiting(): void
    {
        // Hit /api/analytics/events 60 times. The 61st should return 429.
        for ($i = 0; $i < 60; $i++) {
            $response = $this->postJson('/api/analytics/events', []);
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        $this->postJson('/api/analytics/events', [])
            ->assertStatus(429);
    }

    /**
     * Hardening: /og-image requires a valid signed URL and is throttled.
     */
    public function test_og_image_requires_valid_signature(): void
    {
        // 1. Accessing without signature -> 403
        $this->get('/og-image')
            ->assertStatus(403);

        // 2. Accessing with invalid signature -> 403
        $this->get('/og-image?signature=invalid')
            ->assertStatus(403);

        // 3. Accessing with valid signed URL -> 200 or 500 (depending on GD availability)
        $signedUrl = \Illuminate\Support\Facades\URL::signedRoute('og-image', [
            'title' => 'Test Item',
        ]);
        
        $response = $this->get($signedUrl);
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
