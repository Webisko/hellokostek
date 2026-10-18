<?php

namespace Tests\Feature;

use App\Domain\Commerce\Enums\ProductType;
use App\Domain\Commerce\Enums\UserRole;
use App\Filament\Resources\ContactInquiries\Pages\ListContactInquiries;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\FaqItems\Pages\ListFaqItems;
use App\Filament\Resources\GalleryArtworks\Pages\ManageGalleryArtworks;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\OrderReturns\Pages\ListOrderReturns;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\ProductCategories\Pages\ListProductCategories;
use App\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\StoreSettings\Pages\EditStoreSetting;
use App\Models\ContactInquiry;
use App\Models\ContentPage;
use App\Models\Coupon;
use App\Models\FaqItem;
use App\Models\GalleryArtwork;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ComprehensiveCmsCrudAndSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin-audit@hellokostek.pl',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_products_cms_crud_syncs_with_catalog_and_product_detail_api(): void
    {
        $category = ProductCategory::create([
            'name' => 'Akwarela',
            'slug' => 'akwarela',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        // 1. CREATE in Filament CMS
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'type' => ProductType::Physical->value,
                'name' => 'Nowy Obraz Testowy CMS',
                'slug' => 'nowy-obraz-testowy-cms',
                'sku' => 'NOWY-CMS-01',
                'categories' => [$category->id],
                'metadata.year' => '2026',
                'short_description' => 'Krótki opis bez znaczników HTML',
                'description' => 'Pełny opis nowego obrazu z CMS bez znaczników HTML.',
                'print_regular_price' => 45,
                'print_stock_quantity' => 500,
                'has_original' => true,
                'original_regular_price' => 450,
                'original_stock_quantity' => 1,
                'vat_rate' => 23,
                'currency' => 'PLN',
                'is_active' => true,
                'is_visible' => true,
                'is_purchasable' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'nowy-obraz-testowy-cms')->firstOrFail();
        $this->assertEquals('2026', $product->metadata['year'] ?? null);
        $this->assertTrue($product->metadata['original_available'] ?? false);

        // Verify API synchronization (Catalog + Single Product endpoint)
        $apiShow = $this->getJson('/api/catalog/products/nowy-obraz-testowy-cms');
        $apiShow->assertOk()
            ->assertJsonPath('data.name', 'Nowy Obraz Testowy CMS')
            ->assertJsonPath('data.metadata.year', '2026')
            ->assertJsonPath('data.description', 'Pełny opis nowego obrazu z CMS bez znaczników HTML.');

        $variants = collect($apiShow->json('data.variants'));
        $printVariant = $variants->first(fn ($v) => str_ends_with($v['sku'], '-PR'));
        $origVariant = $variants->first(fn ($v) => str_ends_with($v['sku'], '-OR'));
        $this->assertNotNull($printVariant);
        $this->assertNotNull($origVariant);
        $this->assertEquals(4500, $printVariant['regular_price_amount']);
        $this->assertEquals(45000, $origVariant['regular_price_amount']);
        $this->assertEquals(1, $origVariant['stock_quantity']);

        // 2. EDIT in Filament CMS
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'name' => 'Zmieniony Obraz Testowy CMS',
                'metadata.year' => '2025',
                'description' => 'Zaktualizowany opis obrazu.',
                'print_regular_price' => 60,
                'has_original' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $apiShowAfterEdit = $this->getJson('/api/catalog/products/nowy-obraz-testowy-cms');
        $apiShowAfterEdit->assertOk()
            ->assertJsonPath('data.name', 'Zmieniony Obraz Testowy CMS')
            ->assertJsonPath('data.metadata.year', '2025')
            ->assertJsonPath('data.description', 'Zaktualizowany opis obrazu.');

        $variantsAfterEdit = collect($apiShowAfterEdit->json('data.variants'));
        $printAfterEdit = $variantsAfterEdit->first(fn ($v) => str_ends_with($v['sku'], '-PR'));
        $origAfterEdit = $variantsAfterEdit->first(fn ($v) => str_ends_with($v['sku'], '-OR'));
        $this->assertEquals(6000, $printAfterEdit['regular_price_amount']);
        $this->assertFalse($origAfterEdit['is_active']);
        $this->assertEquals(0, $origAfterEdit['stock_quantity']);

        // 3. DELETE in Filament CMS
        Livewire::test(ListProducts::class)
            ->callTableAction('delete', $product);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->getJson('/api/catalog/products/nowy-obraz-testowy-cms')->assertNotFound();
    }

    public function test_gallery_faq_reviews_categories_pages_and_coupons_crud_sync_with_api(): void
    {
        // --- A. GALLERY ARTWORKS ---
        $artwork = GalleryArtwork::create([
            'title' => 'Portret Testowy 1',
            'technique' => 'Akwarela na papierze',
            'image_path' => 'images/01-portret-edited.webp',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->getJson('/api/gallery')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Portret Testowy 1', 'sort_order' => 10]);

        Livewire::test(ManageGalleryArtworks::class)
            ->callTableAction('edit', $artwork, data: [
                'title' => 'Portret Testowy Po Edycji',
                'technique' => 'Cienkopis i akwarela',
                'year' => '2025',
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->getJson('/api/gallery')
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Portret Testowy Po Edycji',
                'technique' => 'Cienkopis i akwarela',
                'year' => '2025',
            ]);

        Livewire::test(ManageGalleryArtworks::class)
            ->callTableAction('delete', $artwork);

        $this->assertDatabaseMissing('gallery_artworks', ['id' => $artwork->id]);

        // --- B. FAQ ITEMS ---
        Livewire::test(ListFaqItems::class)
            ->callAction('create', data: [
                'question' => 'Czy można zamówić ekspresowy portret?',
                'answer' => 'Tak, po wcześniejszym kontakcie telefonicznym.',
                'category' => 'Realizacja',
                'sort_order' => 10,
                'is_visible' => true,
            ])
            ->assertHasNoActionErrors();

        $faq = FaqItem::where('question', 'Czy można zamówić ekspresowy portret?')->firstOrFail();

        $this->getJson('/api/faq')
            ->assertOk()
            ->assertJsonFragment(['question' => 'Czy można zamówić ekspresowy portret?']);

        Livewire::test(ListFaqItems::class)
            ->callTableAction('edit', $faq, data: [
                'question' => 'Czy można zamówić portret w 48h?',
                'answer' => 'Zależy od wybranej techniki.',
                'category' => 'Realizacja',
                'sort_order' => 10,
                'is_visible' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->getJson('/api/faq')
            ->assertOk()
            ->assertJsonFragment(['question' => 'Czy można zamówić portret w 48h?']);

        Livewire::test(ListFaqItems::class)
            ->callTableAction('delete', $faq);

        $this->assertDatabaseMissing('faq_items', ['id' => $faq->id]);

        // --- C. SITE REVIEWS (OPINIE) ---
        Livewire::test(ListProductReviews::class)
            ->callAction('create', data: [
                'customer_name' => 'Marek Testowy',
                'rating' => 5,
                'status' => 'publiczny',
                'emoji' => '🎨',
                'meta' => 'Portret na zamówienie',
                'comment' => 'Wspaniały obraz akwarelowy!',
            ])
            ->assertHasNoActionErrors();

        $review = ProductReview::where('customer_name', 'Marek Testowy')->firstOrFail();

        $this->getJson('/api/reviews/site')
            ->assertOk()
            ->assertJsonFragment(['author' => 'Marek Testowy', 'text' => 'Wspaniały obraz akwarelowy!']);

        Livewire::test(ListProductReviews::class)
            ->callTableAction('edit', $review, data: [
                'customer_name' => 'Marek Zaktualizowany',
                'rating' => 5,
                'status' => 'publiczny',
                'emoji' => '✨',
                'meta' => 'Obiekt oryginalny',
                'comment' => 'Rewelacyjna jakość wykonania!',
            ])
            ->assertHasNoTableActionErrors();

        $this->getJson('/api/reviews/site')
            ->assertOk()
            ->assertJsonFragment(['author' => 'Marek Zaktualizowany', 'text' => 'Rewelacyjna jakość wykonania!']);

        Livewire::test(ListProductReviews::class)
            ->callTableAction('delete', $review);

        $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);

        // --- D. PRODUCT CATEGORIES ---
        Livewire::test(ListProductCategories::class)
            ->callAction('create', data: [
                'name' => 'Szkice Węglem',
                'slug' => 'szkice-weglem',
                'description' => 'Nowa kategoria testowa',
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $cat = ProductCategory::where('slug', 'szkice-weglem')->firstOrFail();
        $this->getJson('/api/catalog')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'szkice-weglem', 'name' => 'Szkice Węglem']);

        Livewire::test(ListProductCategories::class)
            ->callTableAction('delete', $cat);

        $this->assertDatabaseMissing('product_categories', ['id' => $cat->id]);

        // --- E. CONTENT PAGES (STRONY PRAWNE) ---
        $legalPage = ContentPage::create([
            'slug' => 'regulamin',
            'title' => 'Regulamin Sklepu',
            'template' => 'legal',
            'is_active' => true,
            'published_at' => now()->subMinute(),
            'metadata' => [
                'sections' => [
                    ['id' => 'postanowienia', 'label' => '§ 1. Postanowienia ogólne', 'content' => '<p>Treść początkowa.</p>'],
                ],
            ],
        ]);

        Livewire::test(ListContentPages::class)
            ->callTableAction('edit', $legalPage, data: [
                'title' => 'Regulamin Sklepu Zaktualizowany',
                'slug' => 'regulamin',
                'template' => 'legal',
                'is_active' => true,
                'metadata' => [
                    'sections' => [
                        ['id' => 'postanowienia', 'label' => '§ 1. Nowy nagłówek regulaminu', 'content' => '<p>Zaktualizowana treść regulaminu z CMS.</p>'],
                    ],
                ],
            ])
            ->assertHasNoTableActionErrors();

        $this->getJson('/api/content/pages/regulamin')
            ->assertOk()
            ->assertJsonPath('data.page.title', 'Regulamin Sklepu Zaktualizowany')
            ->assertJsonPath('data.page.sections.0.label', '§ 1. Nowy nagłówek regulaminu');

        // --- F. COUPONS (KUPONY RABATOWE) ---
        Livewire::test(ListCoupons::class)
            ->callAction('create', data: [
                'code' => ' rabat25 ',
                'name' => 'Rabat Wiosenny',
                'discount_type' => 'percentage',
                'value' => 25,
                'currency' => 'PLN',
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $coupon = Coupon::where('code', 'RABAT25')->firstOrFail();

        $this->postJson('/api/coupons/validate', ['code' => 'rabat25', 'subtotal' => 200])
            ->assertOk()
            ->assertJson(['valid' => true, 'code' => 'RABAT25', 'discount_amount' => 50]);

        Livewire::test(ListCoupons::class)
            ->callTableAction('delete', $coupon);

        $this->assertSoftDeleted('coupons', ['id' => $coupon->id]);
    }

    public function test_inquiries_orders_invoices_returns_and_store_settings_work_end_to_end(): void
    {
        // --- A. STORE SETTINGS (Cookieless default + Announcement bar sync) ---
        $setting = StoreSetting::firstOrCreate([], [
            'store_name' => 'Hello Kostek',
            'currency' => 'PLN',
            'cookie_banner_enabled' => false,
            'announcement_enabled' => false,
        ]);

        $this->getJson('/api/store/settings')
            ->assertOk()
            ->assertJsonPath('cookie_banner_enabled', false)
            ->assertJsonPath('announcement_enabled', false);

        Livewire::test(EditStoreSetting::class, ['record' => $setting->getRouteKey()])
            ->fillForm([
                'store_name' => 'Hello Kostek Pracownia',
                'currency' => 'PLN',
                'announcement_enabled' => true,
                'announcement_text' => 'Promocja świąteczna na portrety!',
                'cookie_banner_enabled' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->getJson('/api/store/settings')
            ->assertOk()
            ->assertJsonPath('store_name', 'Hello Kostek Pracownia')
            ->assertJsonPath('announcement_enabled', true)
            ->assertJsonPath('announcement_text', 'Promocja świąteczna na portrety!')
            ->assertJsonPath('cookie_banner_enabled', false);

        // --- B. CONTACT INQUIRIES ---
        $inquiry = ContactInquiry::create([
            'name' => 'Katarzyna Klientka',
            'email' => 'katarzyna@example.com',
            'phone' => '600111222',
            'subject' => 'Portret olejny',
            'message' => 'Chciałabym zamówić portret 50x70.',
            'status' => 'new',
        ]);

        Livewire::test(ListContactInquiries::class)
            ->callTableAction('edit', $inquiry, data: [
                'status' => 'in_progress',
                'admin_notes' => 'Wycena wysłana mailem.',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('contact_inquiries', [
            'id' => $inquiry->id,
            'status' => 'in_progress',
            'admin_notes' => 'Wycena wysłana mailem.',
        ]);

        // --- C. ORDERS, INVOICES & GUEST RETURNS ---
        $order = Order::create([
            'number' => 'ORD-AUDIT-001',
            'status' => 'placed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'pending',
            'currency' => 'PLN',
            'customer_email' => 'gość@example.com',
            'customer_first_name' => 'Piotr',
            'customer_last_name' => 'Zieliński',
            'subtotal_amount' => 30000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 5609,
            'total_amount' => 30000,
            'placed_at' => now(),
        ]);

        $orderItem = $order->items()->create([
            'product_type' => 'physical',
            'sku' => 'AKW-2-OR',
            'name' => 'Obiekt II (Oryginał)',
            'quantity' => 1,
            'unit_price_amount' => 30000,
            'regular_unit_price_amount' => 30000,
            'discount_amount' => 0,
            'tax_amount' => 5609,
            'total_amount' => 30000,
        ]);

        // Test Invoice list page loads cleanly even when an invoice is linked to an order
        $order->invoices()->create([
            'number' => 'FV/2026/10/001',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'subtotal_amount' => 24391,
            'tax_amount' => 5609,
            'total_amount' => 30000,
            'currency' => 'PLN',
            'status' => 'issued',
        ]);

        Livewire::test(ListInvoices::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($order->invoices);

        // Test creating a Return for a Guest Order (user_id = null) in CMS
        Livewire::test(ListOrderReturns::class)
            ->callAction('create', data: [
                'order_id' => $order->id,
                'status' => 'pending',
                'refund_amount' => 300,
                'reason' => 'Uszkodzenie w transporcie',
                'items' => [
                    [
                        'order_item_id' => $orderItem->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertHasNoActionErrors();

        $this->assertEquals(1, OrderReturn::where('order_id', $order->id)->count());

        // Test soft-deleting order in ListOrders
        Livewire::test(ListOrders::class)
            ->callTableAction('delete', $order);

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }
}
