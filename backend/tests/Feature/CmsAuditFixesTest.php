<?php

namespace Tests\Feature;

use App\Domain\Commerce\Enums\UserRole;
use App\Filament\Pages\OperationsHealth;
use App\Filament\Pages\StoreDashboard;
use App\Filament\Resources\ContactInquiries\ContactInquiryResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\ContactInquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
    }

    public function test_operations_health_urls_do_not_return_404(): void
    {
        app(\App\Support\StoreSettings::class)->model();
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $page = new OperationsHealth();
        $stats = $page->stats();

        $this->assertNotEmpty($stats);

        foreach ($stats as $stat) {
            $response = $this->get($stat['url']);
            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302], true),
                "Expected 200 or 302 for OperationsHealth URL [{$stat['url']}], got {$response->getStatusCode()}"
            );
        }
    }

    public function test_store_dashboard_counts_only_paid_orders_for_revenue_and_fulfillment(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        // Nieopłacone zamówienie nie powinno być liczone do przychodu ani do wysyłki
        Order::create([
            'number' => 'HK-UNPAID-1',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Nieopłacony',
            'customer_email' => 'unpaid@example.com',
            'currency' => 'PLN',
            'subtotal_amount' => 10000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 10000,
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'fulfillment_status' => 'pending',
            'placed_at' => now(),
        ]);

        // Opłacone zamówienie oczekujące na wysyłkę
        Order::create([
            'number' => 'HK-PAID-1',
            'customer_first_name' => 'Anna',
            'customer_last_name' => 'Opłacona',
            'customer_email' => 'paid@example.com',
            'currency' => 'PLN',
            'subtotal_amount' => 30000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 30000,
            'status' => 'placed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'pending',
            'placed_at' => now(),
        ]);

        $dashboard = new StoreDashboard();
        $heroStats = collect($dashboard->heroStats())->keyBy('label');
        $operationalStats = collect($dashboard->operationalStats())->keyBy('label');

        $this->assertSame('300,00 PLN', $heroStats['Dzisiejszy przychód']['value']);
        $this->assertSame('1', $operationalStats['Zamówienia do wysyłki']['value']);
    }

    public function test_product_variant_promo_prices_sync_and_record_omnibus_history(): void
    {
        $product = Product::factory()->public()->create([
            'name' => 'Obraz Testowy',
            'slug' => 'obraz-testowy',
            'sku' => 'OBRAZ-TEST',
            'regular_price_amount' => 3000,
        ]);

        Product::syncVariantsFromData($product, [
            'print_regular_price' => 30,
            'print_sale_price' => 24,
            'has_original' => true,
            'original_regular_price' => 300,
            'original_sale_price' => 240,
        ]);

        $printVariant = $product->variants()->where('sku', 'OBRAZ-TEST-PR')->firstOrFail();
        $origVariant = $product->variants()->where('sku', 'OBRAZ-TEST-OR')->firstOrFail();

        $this->assertSame(3000, $printVariant->regular_price_amount);
        $this->assertSame(2400, $printVariant->sale_price_amount);
        $this->assertSame(30000, $origVariant->regular_price_amount);
        $this->assertSame(24000, $origVariant->sale_price_amount);

        // Historia Omnibus zapisana osobno dla każdego wariantu
        $this->assertDatabaseHas('product_price_histories', [
            'product_variant_id' => $printVariant->id,
            'regular_price_amount' => 3000,
            'sale_price_amount' => 2400,
        ]);
        $this->assertDatabaseHas('product_price_histories', [
            'product_variant_id' => $origVariant->id,
            'regular_price_amount' => 30000,
            'sale_price_amount' => 24000,
        ]);

        // Wyczyszczenie promocji przy pustych polach
        Product::syncVariantsFromData($product->fresh(), [
            'print_regular_price' => 30,
            'print_sale_price' => null,
            'has_original' => true,
            'original_regular_price' => 300,
            'original_sale_price' => '',
        ]);

        $this->assertNull($printVariant->fresh()->sale_price_amount);
        $this->assertNull($origVariant->fresh()->sale_price_amount);
    }

    public function test_my_profile_renders_password_change_form_and_user_menu_label(): void
    {
        app(\App\Support\StoreSettings::class)->model();
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/moj-profil');

        $response->assertOk();
        $response->assertSee('Mój profil i hasło');
        $response->assertSee('Twoje hasło');
        $response->assertSee('Aktualne hasło');
        $response->assertSee('Nowe hasło');
        $response->assertSee('Potwierdź nowe hasło');
    }

    public function test_store_settings_page_renders_with_reorganized_sections(): void
    {
        $setting = app(\App\Support\StoreSettings::class)->model();
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get("/admin/ustawienia/{$setting->id}/edit");

        $response->assertOk();
        $response->assertSee('Identyfikacja panelu administratora');
        $response->assertSee('Próg darmowej dostawy i metody wysyłki');
        $response->assertSee('Zaawansowane ustawienia techniczne');
        $response->assertSee('Wróć do kokpitu');

        // Zweryfikuj, że 8 sekretnych pól jest inicjalizowanych jako '' zamiast null (ochrona przed fałszywym alertem niezapisanych zmian)
        $page = new \App\Filament\Resources\StoreSettings\Pages\EditStoreSetting();
        $method = new \ReflectionMethod($page, 'mutateFormDataBeforeFill');
        $method->setAccessible(true);
        $filled = $method->invoke($page, $setting->toArray());

        foreach ([
            'inpost_token',
            'orlen_partner_key',
            'przelewy24_api_key',
            'przelewy24_callback_token',
            'przelewy24_crc',
            'resend_key',
            'stripe_secret',
            'stripe_webhook_secret',
        ] as $secretKey) {
            $this->assertSame('', $filled['metadata'][$secretKey]);
        }
    }

    public function test_navigation_badges_count_actionable_orders_and_inquiries(): void
    {
        $this->assertNull(OrderResource::getNavigationBadge());
        $this->assertNull(ContactInquiryResource::getNavigationBadge());

        Order::create([
            'number' => 'HK-PEND-1',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'customer_email' => 'jan@example.com',
            'currency' => 'PLN',
            'subtotal_amount' => 3000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 3000,
            'status' => 'placed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'pending',
            'placed_at' => now(),
        ]);

        Order::create([
            'number' => 'HK-UNPAID-2',
            'customer_first_name' => 'Piotr',
            'customer_last_name' => 'Nowak',
            'customer_email' => 'piotr@example.com',
            'currency' => 'PLN',
            'subtotal_amount' => 3000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 3000,
            'status' => 'placed',
            'payment_status' => 'pending',
            'fulfillment_status' => 'pending',
            'placed_at' => now(),
        ]);

        ContactInquiry::create([
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'subject' => 'Portret rodzinny',
            'message' => 'Zapytanie testowe.',
            'status' => 'new',
        ]);

        $this->assertSame('1', OrderResource::getNavigationBadge());
        $this->assertSame('warning', OrderResource::getNavigationBadgeColor());
        $this->assertSame('1', ContactInquiryResource::getNavigationBadge());
        $this->assertSame('danger', ContactInquiryResource::getNavigationBadgeColor());
    }

    public function test_admin_panel_renders_brand_logo_lockup_and_lowercase_brand_defaults(): void
    {
        $settings = app(\App\Support\StoreSettings::class)->model();
        $meta = $settings->metadata ?? [];
        $meta['admin_brand_name'] = 'hellokostek CMS';
        $meta['admin_logo_path'] = 'branding/admin-logo.png';
        $meta['admin_favicon_path'] = 'branding/admin-favicon.png';
        $settings->forceFill(['metadata' => $meta])->save();
        app(\App\Support\StoreSettings::class)->purgeCache();

        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->get('/admin/moj-profil');

        $response->assertOk();
        $response->assertSee('fi-brand-lockup');
        $response->assertSee('branding/admin-logo.png');
        $response->assertSee('branding/admin-favicon.png');
        $response->assertSee('hellokostek CMS');

        $shopConfigRaw = file_get_contents(base_path('config/shop.php'));
        $this->assertStringContainsString("env('APP_NAME', 'hellokostek')", (string) $shopConfigRaw);
        $this->assertStringNotContainsString('Hello Kostek', (string) $shopConfigRaw);
    }
}
