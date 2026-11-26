<?php

namespace Tests\Feature\Api;

use App\Domain\Commerce\Enums\CustomerSegment;
use App\Domain\Commerce\Enums\ProductType;
use App\Domain\Commerce\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditSecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(StoreSettings::class)->model();
    }

    public function test_admin_endpoints_reject_customers_and_allow_staff(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $staff = User::factory()->create(['role' => UserRole::Manager]);

        $order = Order::query()->create([
            'number' => 'ORD-LBL-001',
            'status' => 'placed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000,
            'customer_email' => 'client@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Testowy',
            'tracking_number' => 'TRACK-123456',
        ]);

        // Customer (403)
        $this->actingAs($customer)->postJson(route('admin.sidebar.save-order'), [
            'groups' => ['Sprzedaż'],
            'resources' => [['label' => 'Zamówienia', 'group' => 'Sprzedaż']],
        ])->assertStatus(403);

        $this->actingAs($customer)->get(route('admin.exports.orders'))
            ->assertStatus(403);

        $this->actingAs($customer)->get(route('admin.exports.customers'))
            ->assertStatus(403);

        $this->actingAs($customer)->get(route('admin.orders.inpost-label', ['number' => $order->number]))
            ->assertStatus(403);

        $this->actingAs($customer)->get(route('admin.orders.orlen-label', ['number' => $order->number]))
            ->assertStatus(403);

        // Staff (Manager allowed on exports & shipping labels, sidebar sorting restricted to Admin)
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_admin' => true]);

        $this->actingAs($staff)->postJson(route('admin.sidebar.save-order'), [
            'groups' => ['Sprzedaż'],
            'resources' => [['label' => 'Zamówienia', 'group' => 'Sprzedaż']],
        ])->assertStatus(403);

        $this->actingAs($admin)->postJson(route('admin.sidebar.save-order'), [
            'groups' => ['Sprzedaż'],
            'resources' => [['label' => 'Zamówienia', 'group' => 'Sprzedaż']],
        ])->assertOk();

        $this->actingAs($staff)->get(route('admin.exports.orders'))
            ->assertOk();

        $this->actingAs($staff)->get(route('admin.exports.customers'))
            ->assertOk();

        Storage::fake();
        Storage::put("inpost/labels/{$order->tracking_number}.pdf", '%PDF-1.4 fake');
        Storage::put("orlen/labels/{$order->tracking_number}.pdf", '%PDF-1.4 fake');

        $this->actingAs($staff)->get(route('admin.orders.inpost-label', ['number' => $order->number]))
            ->assertOk();

        $this->actingAs($staff)->get(route('admin.orders.orlen-label', ['number' => $order->number]))
            ->assertOk();
    }

    public function test_client_supplied_customer_segment_is_ignored_on_quote_and_checkout(): void
    {
        $product = Product::factory()->create([
            'slug' => 'test-akwarela-seg',
            'regular_price_amount' => 30000,
            'sale_price_amount' => null,
            'type' => ProductType::Physical,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SEG-01-OR',
            'regular_price_amount' => 30000,
            'stock_quantity' => 1,
            'manages_stock' => true,
            'is_active' => true,
        ]);

        $quoteResponse = $this->postJson(route('api.quote'), [
            'items' => [
                ['slug' => $product->slug, 'quantity' => 1, 'variant_id' => $variant->id],
            ],
            'customer_segment' => CustomerSegment::WholesaleThirty->value,
        ]);

        $quoteResponse->assertOk();
        $this->assertEquals(0, $quoteResponse->json('data.discount_amount'));
        $this->assertEquals(30000, $quoteResponse->json('data.subtotal_amount'));

        $placeResponse = $this->postJson('/api/checkout/place', [
            'items' => [
                ['slug' => $product->slug, 'quantity' => 1, 'variant_id' => $variant->id],
            ],
            'payment_method' => 'bank_transfer',
            'shipping_method_code' => 'free_courier',
            'customer_segment' => CustomerSegment::LoyalEight->value,
            'terms_accepted' => true,
            'customer' => [
                'email' => 'guest-seg@example.com',
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'phone' => '500600700',
                'terms_accepted' => true,
            ],
            'shipping_address' => [
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'street' => 'Piotrkowska 10',
                'city' => 'Łódź',
                'postal_code' => '90-001',
                'country_code' => 'PL',
            ],
        ]);

        $placeResponse->assertStatus(201);
        $orderNumber = $placeResponse->json('data.order.number') ?? $placeResponse->json('order.number');
        $order = Order::query()->where('number', $orderNumber)->firstOrFail();

        $this->assertEquals(CustomerSegment::Regular, $order->customer_segment);
        $this->assertNotEmpty($order->public_token);
    }

    public function test_contact_inquiry_rejects_disallowed_upload_and_traps_bots_silently(): void
    {
        Mail::fake();
        Storage::fake('local');

        // 1. Disallowed MIME upload -> 422
        $badFile = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');
        $this->post('/api/inquiries', [
            'name' => 'Bot Tester',
            'email' => 'tester@example.com',
            'message' => 'Test upload validation',
            '_form_started_at' => (string) ((time() - 10) * 1000),
            'files' => [$badFile],
        ], ['Accept' => 'application/json'])->assertStatus(422);

        // 2. Honeypot filled -> 200 OK without saving record
        $this->postJson('/api/inquiries', [
            'name' => 'Spammer',
            'email' => 'spammer@example.com',
            'message' => 'Buy crypto',
            'hp_field' => 'http://spam.example.com',
            '_form_started_at' => (string) ((time() - 10) * 1000),
        ])->assertOk()->assertJsonPath('data.id', 0);

        $this->assertDatabaseMissing('contact_inquiries', ['email' => 'spammer@example.com']);

        // 3. Submitted faster than 1000ms -> 200 OK without saving record
        $this->postJson('/api/inquiries', [
            'name' => 'Fast Bot',
            'email' => 'fastbot@example.com',
            'message' => 'Instant message',
            '_form_started_at' => (string) (microtime(true) * 1000),
        ])->assertOk()->assertJsonPath('data.id', 0);

        $this->assertDatabaseMissing('contact_inquiries', ['email' => 'fastbot@example.com']);

        // 4. Legitimate delayed submission with valid image -> 201 Created
        $goodFile = UploadedFile::fake()->image('kot.jpg', 200, 200);
        $this->post('/api/inquiries', [
            'name' => 'Katarzyna',
            'email' => 'katarzyna@example.com',
            'subject' => 'Portret kota',
            'message' => 'Proszę o wycenę portretu.',
            '_form_started_at' => (string) ((time() - 5) * 1000),
            'files' => [$goodFile],
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $this->assertDatabaseHas('contact_inquiries', ['email' => 'katarzyna@example.com']);
    }

    public function test_authenticated_user_cannot_claim_guest_order_return_via_order_id_idor(): void
    {
        $product = Product::factory()->create();

        $guestOrder = Order::query()->create([
            'user_id' => null,
            'number' => 'ORD-GUEST-IDOR',
            'status' => 'completed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000,
            'customer_email' => 'realguest@example.com',
            'customer_first_name' => 'Real',
            'customer_last_name' => 'Guest',
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $guestOrder->id,
            'product_id' => $product->id,
            'product_type' => ProductType::Physical,
            'sku' => 'TEST-OR',
            'name' => 'Obraz',
            'quantity' => 1,
            'unit_price_amount' => 10000,
            'regular_unit_price_amount' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000,
        ]);

        $attacker = User::factory()->create(['email' => 'attacker@example.com']);

        $response = $this->actingAs($attacker, 'sanctum')->postJson('/api/returns', [
            'order_id' => $guestOrder->id,
            'reason' => 'IDOR attempt',
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [403, 404], true));
        $this->assertDatabaseCount('order_returns', 0);
    }

    public function test_order_show_authorizes_public_token_and_rejects_wrong_token(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-TOK-999',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 5000,
            'customer_email' => 'buyer@example.com',
            'customer_first_name' => 'Marek',
            'customer_last_name' => 'Kupujący',
        ]);

        $this->assertNotEmpty($order->public_token);

        $this->getJson("/api/checkout/orders/{$order->number}")
            ->assertStatus(403);

        $this->getJson("/api/checkout/orders/{$order->number}", [
            'X-Order-Token' => 'wrong-token-value',
        ])->assertStatus(403);

        $this->getJson("/api/checkout/orders/{$order->number}", [
            'X-Order-Token' => $order->public_token,
        ])->assertOk()->assertJsonPath('data.order.number', $order->number);
    }

    public function test_user_mass_assignment_ignores_role_and_is_admin(): void
    {
        $user = User::query()->create([
            'name' => 'Ordinary User',
            'email' => 'ordinary@example.com',
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::Admin->value,
            'is_admin' => true,
        ]);

        $user->refresh();

        $this->assertNotEquals(UserRole::Admin, $user->role);
        $this->assertFalse((bool) $user->is_admin);
        $this->assertFalse($user->isStaff());
    }

    public function test_expire_unpaid_orders_cancels_expired_stripe_order_and_restores_stock(): void
    {
        $product = Product::factory()->create([
            'slug' => 'expired-orig-work',
            'metadata' => ['original_available' => false],
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'EXP-01-OR',
            'regular_price_amount' => 30000,
            'stock_quantity' => 0,
            'manages_stock' => true,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'number' => 'ORD-EXP-001',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'subtotal_amount' => 30000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 30000,
            'customer_email' => 'late@example.com',
            'customer_first_name' => 'Late',
            'customer_last_name' => 'Buyer',
            'metadata' => [
                'payment' => ['provider' => 'stripe'],
                'reservation_expires_at' => now()->subMinutes(10)->toIso8601String(),
                'stock_deducted' => true,
            ],
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_type' => ProductType::Physical,
            'sku' => $variant->sku,
            'name' => $product->name,
            'quantity' => 1,
            'unit_price_amount' => 30000,
            'regular_unit_price_amount' => 30000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 30000,
        ]);

        $this->artisan('app:expire-unpaid-orders')->assertExitCode(0);

        $order->refresh();
        $variant->refresh();
        $product->refresh();

        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('failed', $order->payment_status);
        $this->assertEquals(1, $variant->stock_quantity);
        $this->assertTrue((bool) data_get($product->metadata, 'original_available'));
        $this->assertTrue((bool) data_get($order->metadata, 'stock_restored'));
    }

    public function test_omnibus_per_variant_lowest_price_before_discount_separates_original_and_print(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'omnibus-variant-split',
            'regular_price_amount' => 30000,
        ]);

        $originalVariant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SPLIT-01-OR',
            'regular_price_amount' => 30000,
            'sale_price_amount' => null,
            'stock_quantity' => 1,
            'manages_stock' => true,
            'is_active' => true,
        ]);

        $printVariant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SPLIT-01-PR',
            'regular_price_amount' => 3000,
            'sale_price_amount' => null,
            'stock_quantity' => 100,
            'manages_stock' => false,
            'is_active' => true,
        ]);

        // Discount ONLY the original variant (300 zł -> 240 zł); print stays at 30 zł
        $originalVariant->update(['sale_price_amount' => 24000]);

        $response = $this->getJson(route('api.catalog.show', ['slug' => $product->slug]))
            ->assertOk();

        $variants = collect($response->json('data.variants'))->keyBy('sku');

        $this->assertEquals(24000, $variants['SPLIT-01-OR']['current_price_amount']);
        $this->assertEquals(30000, $variants['SPLIT-01-OR']['regular_price_amount']);
        $this->assertEquals(24000, $variants['SPLIT-01-OR']['sale_price_amount']);
        $this->assertEquals(30000, $variants['SPLIT-01-OR']['lowest_price_before_discount_last_30_days']);

        $this->assertEquals(3000, $variants['SPLIT-01-PR']['current_price_amount']);
        $this->assertEquals(3000, $variants['SPLIT-01-PR']['regular_price_amount']);
        $this->assertNull($variants['SPLIT-01-PR']['sale_price_amount']);
        $this->assertEquals(3000, $variants['SPLIT-01-PR']['lowest_price_before_discount_last_30_days']);
    }

    public function test_free_shipping_threshold_updates_dynamically_from_store_settings_and_zero_disables_it(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'dynamic-threshold-print',
            'regular_price_amount' => 5000,
            'type' => ProductType::Physical,
        ]);

        $printVariant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'THR-01-PR',
            'regular_price_amount' => 5000, // 50 zł per print
            'stock_quantity' => 100,
            'manages_stock' => false,
            'is_active' => true,
        ]);

        $settings = app(StoreSettings::class)->model();
        $settings->update(['free_shipping_threshold' => 30000]); // 300 zł

        $this->getJson('/api/store/settings')
            ->assertOk()
            ->assertJsonPath('free_shipping_threshold', 30000);

        // 5 prints = 250 zł (< 300 zł) -> courier shipping charged (> 0)
        $belowResponse = $this->postJson(route('api.quote'), [
            'items' => [['slug' => $product->slug, 'quantity' => 5, 'variant_id' => $printVariant->id]],
            'shipping_method_code' => 'flat_rate:courier',
        ])->assertOk();
        $this->assertGreaterThan(0, $belowResponse->json('data.shipping_amount'));

        // 6 prints = 300 zł (>= 300 zł) -> free shipping applied (0)
        $atThresholdResponse = $this->postJson(route('api.quote'), [
            'items' => [['slug' => $product->slug, 'quantity' => 6, 'variant_id' => $printVariant->id]],
            'shipping_method_code' => 'flat_rate:courier',
        ])->assertOk();
        $this->assertEquals(0, $atThresholdResponse->json('data.shipping_amount'));

        // Setting threshold to 0 disables threshold-based free shipping
        $settings->update(['free_shipping_threshold' => 0]);

        $disabledResponse = $this->postJson(route('api.quote'), [
            'items' => [['slug' => $product->slug, 'quantity' => 6, 'variant_id' => $printVariant->id]],
            'shipping_method_code' => 'flat_rate:courier',
        ])->assertOk();
        $this->assertGreaterThan(0, $disabledResponse->json('data.shipping_amount'));
    }

    public function test_contact_inquiry_attachments_are_stored_on_local_disk_and_staff_gated(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();

        $goodFile = UploadedFile::fake()->image('referencja.jpg', 300, 300);

        $response = $this->post('/api/inquiries', [
            'name' => 'Alicja',
            'email' => 'alicja@example.com',
            'message' => 'Proszę o wycenę portretu.',
            '_form_started_at' => (string) ((time() - 5) * 1000),
            'files' => [$goodFile],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);

        $inquiry = \App\Models\ContactInquiry::query()->where('email', 'alicja@example.com')->firstOrFail();
        $storedPath = $inquiry->payload['attachments'][0];

        $this->assertStringStartsWith('contact-inquiries/', $storedPath);
        Storage::disk('local')->assertExists($storedPath);
        $this->assertEmpty(Storage::disk('public')->allFiles());

        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $staff = User::factory()->create(['role' => UserRole::Manager]);

        $this->actingAs($customer)
            ->get(route('admin.inquiries.attachment', ['inquiry' => $inquiry->id, 'index' => 0]))
            ->assertStatus(403);

        $this->actingAs($staff)
            ->get(route('admin.inquiries.attachment', ['inquiry' => $inquiry->id, 'index' => 0]))
            ->assertOk();
    }

    public function test_order_return_rejects_duplicate_order_item_ids_exceeding_purchased_quantity(): void
    {
        Mail::fake();

        $order = Order::query()->create([
            'number' => 'ORD-DUP-RMA',
            'customer_email' => 'dup-rma@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Testowy',
            'status' => 'completed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 1870,
            'total_amount' => 10000,
        ]);

        $product = Product::factory()->public()->create(['type' => ProductType::Physical]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => ProductType::Physical,
            'sku' => 'DUP-RMA-PR',
            'name' => 'Wydruk testowy',
            'quantity' => 1,
            'unit_price_amount' => 10000,
            'regular_unit_price_amount' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 1870,
            'total_amount' => 10000,
        ]);

        $this->postJson(route('api.returns.store'), [
            'order_number' => $order->number,
            'customer_email' => $order->customer_email,
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
            ],
        ])->assertStatus(422);

        $this->assertDatabaseCount('order_returns', 0);
    }

    public function test_user_force_delete_preserves_order_return_with_null_user_id(): void
    {
        $user = User::factory()->create();

        $order = Order::query()->create([
            'user_id' => $user->id,
            'number' => 'ORD-RET-KEEP',
            'customer_email' => $user->email,
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'status' => 'completed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000,
        ]);

        $orderReturn = \App\Models\OrderReturn::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => 'Zwrot testowy',
        ]);

        $user->forceDelete();

        $orderReturn->refresh();
        $this->assertNull($orderReturn->user_id);
        $this->assertDatabaseHas('order_returns', ['id' => $orderReturn->id]);
    }

    public function test_stripe_webhook_rejects_amount_mismatch_and_stale_expired_session_does_not_cancel_paid_order(): void
    {
        $webhookSecret = 'whsec_audit_test';
        config(['services.stripe.webhook_secret' => $webhookSecret]);

        $order = Order::query()->create([
            'number' => 'ORD-STRIPE-AUDIT',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 15000,
            'customer_email' => 'stripe-audit@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
        ]);

        \App\Models\PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'initiated',
            'amount' => 15000,
            'currency' => 'PLN',
            'external_session_id' => 'cs_audit_mismatch',
        ]);

        $signStripe = function (string $payload) use ($webhookSecret): string {
            $time = time();
            $sig = hash_hmac('sha256', "{$time}.{$payload}", $webhookSecret);
            return "t={$time},v1={$sig}";
        };

        $mismatchPayload = json_encode([
            'id' => 'evt_mismatch',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_audit_mismatch',
                    'payment_status' => 'paid',
                    'amount_total' => 100,
                    'currency' => 'pln',
                    'metadata' => ['order_number' => $order->number],
                ],
            ],
        ]);

        $this->call('POST', '/api/integrations/stripe/payment-callback', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signStripe($mismatchPayload),
            'CONTENT_TYPE' => 'application/json',
        ], $mismatchPayload)->assertStatus(422);

        $this->assertEquals('awaiting_payment', $order->fresh()->payment_status);

        // Stale session expired after order was paid on a newer session
        $order->forceFill(['payment_status' => 'paid', 'status' => 'placed'])->save();

        \App\Models\PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'confirmed',
            'amount' => 15000,
            'currency' => 'PLN',
            'external_session_id' => 'cs_audit_newer_paid',
        ]);

        $expiredPayload = json_encode([
            'id' => 'evt_expired_stale',
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => 'cs_audit_mismatch',
                    'payment_status' => 'unpaid',
                    'metadata' => ['order_number' => $order->number],
                ],
            ],
        ]);

        $this->call('POST', '/api/integrations/stripe/payment-callback', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signStripe($expiredPayload),
            'CONTENT_TYPE' => 'application/json',
        ], $expiredPayload)->assertOk();

        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertEquals('placed', $order->fresh()->status);
    }

    public function test_baselinker_callback_route_is_removed(): void
    {
        $this->getJson('/api/integrations/baselinker/shipment-callback')
            ->assertStatus(404);

        $this->postJson('/api/integrations/baselinker/shipment-callback', [])
            ->assertStatus(404);
    }

    public function test_forgot_password_does_not_send_reset_link_for_staff_accounts(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $admin = User::factory()->create([
            'email' => 'staff-reset@example.com',
            'role' => UserRole::Admin,
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->postJson('/api/auth/forgot-password', [
            'email' => $admin->email,
        ])->assertStatus(202);

        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_account_addresses_show_route_is_disabled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/account/addresses/1');

        $this->assertTrue(in_array($response->getStatusCode(), [404, 405], true));
    }

    public function test_guest_product_review_does_not_expose_verified_purchase_via_email(): void
    {
        $product = Product::factory()->public()->create(['slug' => 'review-privacy-test']);

        Order::query()->create([
            'number' => 'ORD-REV-PRIV',
            'customer_email' => 'realbuyer@example.com',
            'customer_first_name' => 'Real',
            'customer_last_name' => 'Buyer',
            'status' => 'completed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 5000,
        ])->items()->create([
            'product_id' => $product->id,
            'product_type' => ProductType::Physical,
            'sku' => 'REV-01',
            'name' => $product->name,
            'quantity' => 1,
            'unit_price_amount' => 5000,
            'regular_unit_price_amount' => 5000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 5000,
        ]);

        $response = $this->postJson("/api/catalog/products/{$product->slug}/reviews", [
            'rating' => 5,
            'comment' => 'Świetny obraz!',
            'customer_name' => 'Ktoś Inny',
            'customer_email' => 'realbuyer@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_verified_purchase', false);
    }

    public function test_contact_inquiry_ignores_injected_attachments_and_scopes_attachment_controller(): void
    {
        Mail::fake();
        Storage::fake('local');
        Storage::disk('local')->put('invoices/secret.pdf', 'SECRET-PDF');

        $this->postJson('/api/inquiries', [
            'name' => 'Testowy Nadawca',
            'email' => 'nadawca@example.com',
            'message' => 'Wiadomość bez załączników ze wstrzykniętym polem attachments.',
            '_form_started_at' => (string) ((time() - 5) * 1000),
            'attachments' => [
                'abc' => 'invoices/secret.pdf',
                '0' => 'invoices/secret.pdf',
            ],
        ])->assertStatus(201);

        $inquiry = \App\Models\ContactInquiry::query()->where('email', 'nadawca@example.com')->firstOrFail();
        $this->assertNull(data_get($inquiry->payload, 'attachments'));

        $tampered = \App\Models\ContactInquiry::query()->create([
            'name' => 'Tampered',
            'email' => 'tampered@example.com',
            'message' => 'Próba wskazania innego katalogu',
            'payload' => ['attachments' => ['invoices/secret.pdf']],
            'status' => 'new',
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('admin.inquiries.attachment', ['inquiry' => $tampered->id, 'index' => 0]))
            ->assertStatus(404);
    }

    public function test_checkout_supports_sanctum_bearer_auth_and_blocks_payment_session_for_paid_order(): void
    {
        $user = User::factory()->create([
            'email' => 'sanctum-buyer@example.com',
            'email_verified_at' => now(),
        ]);
        $token = $user->createToken('storefront')->plainTextToken;

        $order = Order::query()->create([
            'number' => 'ORD-SANCTUM-01',
            'user_id' => $user->id,
            'customer_email' => 'sanctum-buyer@example.com',
            'customer_first_name' => 'Anna',
            'customer_last_name' => 'Kupująca',
            'status' => 'placed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000,
            'metadata' => ['payment' => ['provider' => 'stripe']],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/checkout/orders/{$order->number}")
            ->assertOk()
            ->assertJsonPath('data.order.number', 'ORD-SANCTUM-01');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/checkout/orders/{$order->number}/payment-session")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }

    public function test_coupon_limits_count_shipped_orders_case_insensitively_and_account_orders_include_shipped(): void
    {
        $user = User::factory()->create([
            'email' => 'anna@example.com',
            'email_verified_at' => now(),
        ]);

        $product = Product::factory()->public()->create([
            'slug' => 'coupon-shipped-test',
            'regular_price_amount' => 10000,
            'stock_quantity' => 5,
            'manages_stock' => true,
        ]);

        $coupon = \App\Models\Coupon::create([
            'code' => 'SHIP10',
            'name' => 'Rabat 10%',
            'discount_type' => 'percentage',
            'value' => 10,
            'usage_limit' => 1,
            'usage_limit_per_customer' => 1,
            'is_active' => true,
        ]);

        Order::query()->create([
            'number' => 'ORD-SHIPPED-01',
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'customer_email' => 'Anna@Example.com',
            'customer_first_name' => 'Anna',
            'customer_last_name' => 'Nowak',
            'status' => 'shipped',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'discount_amount' => 1000,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 9000,
            'placed_at' => now(),
        ]);

        $this->postJson('/api/coupons/validate', [
            'code' => 'SHIP10',
            'subtotal' => 100,
        ])->assertStatus(422);

        $this->postJson('/api/checkout/place', [
            'items' => [['slug' => $product->slug, 'quantity' => 1]],
            'payment_method' => 'bank_transfer',
            'coupon_code' => 'SHIP10',
            'shipping_method_code' => 'free_courier',
            'terms_accepted' => true,
            'customer' => [
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'email' => 'anna@example.com',
                'phone' => '+48600100200',
            ],
            'shipping_address' => [
                'street' => 'Piotrkowska 1',
                'postal_code' => '90-001',
                'city' => 'Łódź',
                'country' => 'PL',
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['coupon_code']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/account/orders')
            ->assertOk()
            ->assertJsonFragment(['number' => 'ORD-SHIPPED-01']);
    }

    public function test_catalog_per_page_bounds_cart_update_max_99_and_staff_login_blocked(): void
    {
        $this->getJson('/api/catalog?per_page=0')->assertOk();

        $product = Product::factory()->public()->create([
            'slug' => 'cart-limit-99',
            'regular_price_amount' => 5000,
        ]);

        $sessionToken = $this->getJson(route('api.cart.show'))->json('data.session_token');

        $this->postJson(route('api.cart.items.store'), [
            'session_token' => $sessionToken,
            'product_id' => $product->id,
            'quantity' => 50,
        ])->assertStatus(201);

        $this->postJson(route('api.cart.items.store'), [
            'session_token' => $sessionToken,
            'product_id' => $product->id,
            'quantity' => 50,
        ])->assertStatus(422);

        $itemId = $this->getJson(route('api.cart.show') . "?session_token={$sessionToken}")
            ->json('data.items.0.id');

        $this->putJson(route('api.cart.items.update', ['itemId' => $itemId]), [
            'session_token' => $sessionToken,
            'quantity' => 100,
        ])->assertStatus(422);

        $manager = User::factory()->create([
            'email' => 'manager-api@example.com',
            'role' => UserRole::Manager,
            'password' => bcrypt('SecretPass123!'),
        ]);
        $manager->forceFill(['is_admin' => false])->save();

        $this->withServerVariables(['REMOTE_ADDR' => '10.99.88.77'])
            ->postJson('/api/auth/login', [
                'email' => 'manager-api@example.com',
                'password' => 'SecretPass123!',
            ])->assertStatus(422);
    }
}
