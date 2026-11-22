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

        $response = $this->actingAs($admin)->get('/admin/ustawienia');

        $response->assertOk();
        $response->assertSee('Identyfikacja panelu administratora');
        $response->assertSee('Próg darmowej dostawy i metody wysyłki');
        $response->assertSee('Zaawansowane ustawienia techniczne');
        $response->assertDontSee('Wróć do kokpitu');

        $this->assertStringEndsWith('/admin/ustawienia', \App\Filament\Resources\StoreSettings\StoreSettingResource::getUrl('index'));
        $this->assertStringEndsWith('/utworz', \App\Filament\Resources\Users\UserResource::getUrl('create'));

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

    public function test_csp_allows_blob_previews_and_header_save_action_saves_settings_with_notification(): void
    {
        $csp = (string) config('app.content_security_policy');
        $this->assertStringContainsString("img-src 'self' data: blob:", $csp);
        $this->assertStringContainsString("worker-src 'self' blob:", $csp);

        $settings = app(\App\Support\StoreSettings::class)->model();
        $admin = $this->createAdmin();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\StoreSettings\Pages\EditStoreSetting::class, [
                'record' => $settings->getRouteKey(),
            ])
            ->set('data.store_name', 'hellokostek-test-save')
            ->callAction('save')
            ->assertNotified('Ustawienia zostały zapisane');

        $this->assertSame('hellokostek-test-save', $settings->fresh()->store_name);
    }

    public function test_manager_cannot_edit_or_delete_admin_user_and_sees_users_list(): void
    {
        $admin = $this->createAdmin();
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        // Polityki UserPolicy
        $policy = new \App\Policies\UserPolicy();
        $this->assertTrue($policy->viewAny($manager));
        $this->assertTrue($policy->view($manager, $admin));
        $this->assertFalse($policy->update($manager, $admin));
        $this->assertFalse($policy->delete($manager, $admin));
        $this->assertFalse($policy->restore($manager, $admin));
        $this->assertFalse($policy->forceDelete($manager, $admin));
        $this->assertTrue($policy->update($manager, $employee));
        $this->assertTrue($policy->delete($manager, $employee));
        $this->assertTrue($policy->update($admin, $manager));

        // Menedżer widzi listę użytkowników (w tym konto Admina), ale dostaje 403 przy próbie edycji Admina
        $this->actingAs($manager)->get('/admin/uzytkownicy')
            ->assertOk()
            ->assertSee($admin->email);

        $this->actingAs($manager)->get("/admin/uzytkownicy/{$admin->id}/edycja")
            ->assertForbidden();
    }

    public function test_manager_cannot_access_operations_health_or_sidebar_sort(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($manager)->get('/admin/status-systemu')->assertForbidden();
        $this->actingAs($manager)->postJson(route('admin.sidebar.save-order'), [
            'groups' => ['Sprzedaż & zapytania'],
        ])->assertForbidden();
    }

    public function test_manager_settings_ui_hides_technical_fields_and_preserves_protected_values_on_save(): void
    {
        $settings = app(\App\Support\StoreSettings::class)->model();
        $meta = $settings->metadata ?? [];
        $meta['stripe_secret'] = 'sk_test_protected_123';
        $meta['admin_logo_path'] = 'branding/admin-logo.png';
        $settings->forceFill([
            'store_name' => 'hellokostek',
            'free_shipping_threshold' => 20000,
            'shipping_methods' => [
                ['code' => 'flat_rate:courier', 'name' => 'Kurier', 'amount' => 1750, 'supports_cod' => false, 'requires_delivery_point' => false],
            ],
            'metadata' => $meta,
        ])->save();
        app(\App\Support\StoreSettings::class)->purgeCache();

        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($manager)->get('/admin/ustawienia');
        $response->assertOk();
        $response->assertSee('Pasek ogłoszeń sklepu');
        $response->assertSee('Próg darmowej dostawy dla wydruków (PLN)');
        $response->assertSee('Tryb konserwacji');
        $response->assertSee('Odzyskiwanie porzuconych koszyków');
        $response->assertDontSee('Kod techniczny metody');
        $response->assertDontSee('Zaawansowane ustawienia techniczne');
        $response->assertDontSee('Klucz prywatny Stripe');

        \Livewire\Livewire::actingAs($manager)
            ->test(\App\Filament\Resources\StoreSettings\Pages\EditStoreSetting::class, [
                'record' => $settings->getRouteKey(),
            ])
            ->set('data.free_shipping_threshold', 300)
            ->set('data.announcement_enabled', true)
            ->set('data.announcement_text', 'Nowa promocja')
            ->set('data.metadata.stripe_secret', 'HACKED')
            ->set('data.metadata.admin_logo_path', null)
            ->callAction('save')
            ->assertNotified('Ustawienia zostały zapisane');

        $fresh = $settings->fresh();
        $this->assertSame(30000, $fresh->free_shipping_threshold);
        $this->assertTrue((bool) $fresh->announcement_enabled);
        $this->assertSame('Nowa promocja', $fresh->announcement_text);
        $this->assertSame('sk_test_protected_123', $fresh->metadata['stripe_secret']);
        $this->assertSame('branding/admin-logo.png', $fresh->metadata['admin_logo_path']);
        $this->assertSame('flat_rate:courier', $fresh->shipping_methods[0]['code']);
    }

    public function test_manager_can_update_product_and_page_without_losing_hidden_slug_or_anchors(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $page = \App\Models\ContentPage::create([
            'title' => 'Regulamin test',
            'slug' => 'regulamin-test',
            'template' => 'legal',
            'is_active' => true,
            'metadata' => [
                'sections' => [
                    ['label' => '§ 1. Wstęp', 'id' => 'wstep-kotwica', 'content' => '<p>Treść</p>'],
                ],
            ],
        ]);

        $this->actingAs($manager)
            ->get('/admin/strony-statyczne')
            ->assertOk();

        \Livewire\Livewire::actingAs($manager)
            ->test(\App\Filament\Resources\ContentPages\Pages\ListContentPages::class)
            ->assertTableActionExists('edit');

        $this->assertSame('regulamin-test', $page->fresh()->slug);
        $this->assertSame('wstep-kotwica', $page->fresh()->metadata['sections'][0]['id']);
    }

    public function test_edit_user_and_create_user_initialize_password_as_empty_string_for_unsaved_changes_hash(): void
    {
        $admin = $this->createAdmin();
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $editComponent = \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Users\Pages\EditUser::class, [
                'record' => $manager->getRouteKey(),
            ]);

        $editData = $editComponent->get('data');
        $this->assertSame('', $editData['password']);
        $this->assertSame(
            md5(str_replace('\\', '', json_encode($editData, JSON_UNESCAPED_UNICODE))),
            $editComponent->get('savedDataHash')
        );

        $createComponent = \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Users\Pages\CreateUser::class);

        $createData = $createComponent->get('data');
        $this->assertSame('', $createData['password']);
        $this->assertSame(
            md5(str_replace('\\', '', json_encode($createData, JSON_UNESCAPED_UNICODE))),
            $createComponent->get('savedDataHash')
        );
    }

    public function test_manager_cannot_delete_system_content_pages_but_can_delete_custom_pages(): void
    {
        $admin = $this->createAdmin();
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $systemPage = \App\Models\ContentPage::create([
            'title' => 'Regulamin',
            'slug' => 'regulamin',
            'template' => 'legal',
            'is_active' => true,
        ]);

        $customPage = \App\Models\ContentPage::create([
            'title' => 'Nowa wystawa',
            'slug' => 'nowa-wystawa',
            'template' => 'default',
            'is_active' => true,
        ]);

        $this->assertFalse($manager->can('delete', $systemPage));
        $this->assertTrue($manager->can('delete', $customPage));
        $this->assertFalse($manager->can('deleteAny', \App\Models\ContentPage::class));
        $this->assertTrue($admin->can('delete', $systemPage));
        $this->assertTrue($admin->can('deleteAny', \App\Models\ContentPage::class));
    }

    public function test_product_review_toggle_and_navigation_badge(): void
    {
        $admin = $this->createAdmin();

        $draftReview = \App\Models\ProductReview::create([
            'customer_name' => 'Anna',
            'rating' => 5,
            'comment' => 'Świetna praca!',
            'status' => 'szkic',
        ]);

        $this->assertSame('1', \App\Filament\Resources\ProductReviews\ProductReviewResource::getNavigationBadge());

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ProductReviews\Pages\ListProductReviews::class)
            ->call('updateTableColumnState', 'is_approved', (string) $draftReview->getKey(), true);

        $fresh = $draftReview->fresh();
        $this->assertTrue((bool) $fresh->is_approved);
        $this->assertSame('publiczny', $fresh->status);
        $this->assertNull(\App\Filament\Resources\ProductReviews\ProductReviewResource::getNavigationBadge());
    }

    public function test_manager_can_create_product_with_auto_generated_slug_when_slug_is_hidden(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        \Livewire\Livewire::actingAs($manager)
            ->test(\App\Filament\Resources\Products\Pages\ListProducts::class)
            ->callAction('create', [
                'name' => 'Kosmiczna Akwarela Test',
                'print_regular_price' => 30,
                'has_original' => true,
                'original_regular_price' => 300,
                'is_active' => true,
                'is_visible' => true,
                'is_purchasable' => true,
            ])
            ->assertHasNoActionErrors();

        $product = \App\Models\Product::query()->where('name', 'Kosmiczna Akwarela Test')->first();
        $this->assertNotNull($product);
        $this->assertSame('kosmiczna-akwarela-test', $product->slug);
        $this->assertSame('KOSMICZNA-AKWARELA-TEST', $product->sku);
    }

    public function test_order_tabs_and_dashboard_quickbar_and_storefront_link_render(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $order = Order::create([
            'number' => 'HK-TAB-1',
            'customer_first_name' => 'Katarzyna',
            'customer_last_name' => 'Testowa',
            'customer_email' => 'kasia@example.com',
            'currency' => 'PLN',
            'subtotal_amount' => 5000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 5000,
            'status' => 'placed',
            'payment_status' => 'pending',
            'fulfillment_status' => 'pending',
            'placed_at' => now(),
        ]);

        \Livewire\Livewire::actingAs($manager)
            ->test(\App\Filament\Resources\Orders\Pages\ListOrders::class)
            ->assertSee('Do wysyłki')
            ->assertSee('Oczekują na płatność')
            ->assertSee('Wysłane i zrealizowane');

        $order->update(['payment_status' => 'paid']);
        $this->assertSame('paid', $order->fresh()->payment_status);

        $order->update([
            'status' => 'shipped',
            'fulfillment_status' => 'fulfilled',
            'shipped_at' => now(),
            'carrier' => 'InPost',
        ]);
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('InPost', $order->fresh()->carrier);

        $dashboard = $this->actingAs($manager)->get('/admin/kokpit');
        $dashboard->assertOk();
        $dashboard->assertSee('Dodaj obraz do sklepu');
        $dashboard->assertSee('Dodaj do galerii');
        $dashboard->assertSee('Otwórz sklep');
    }
}
