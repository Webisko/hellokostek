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

        // Staff (allowed past authorization gate)
        $this->actingAs($staff)->postJson(route('admin.sidebar.save-order'), [
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
}
