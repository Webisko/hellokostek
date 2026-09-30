<?php

namespace Tests\Feature;

use App\Filament\Resources\ContentPages\Tables\ContentPagesTable;
use App\Filament\Resources\Coupons\Tables\CouponsTable;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Filament\Resources\FaqItems\Tables\FaqItemsTable;
use App\Filament\Resources\GalleryArtworks\Tables\GalleryArtworkTable;
use App\Filament\Resources\ProductAttributes\Tables\ProductAttributesTable;
use App\Filament\Resources\ProductCategories\Tables\ProductCategoriesTable;
use App\Filament\Resources\ProductReviews\Tables\ProductReviewsTable;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Resources\RedirectRules\Tables\RedirectRulesTable;
use App\Filament\Resources\StoreSettings\Tables\StoreSettingsTable;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\FaqItem;
use App\Models\GalleryArtwork;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAndDragDropOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_products_reflect_drag_and_drop_order_changes(): void
    {
        $prodA = Product::factory()->public()->create(['name' => 'Produkt A', 'sort_order' => 10]);
        $prodB = Product::factory()->public()->create(['name' => 'Produkt B', 'sort_order' => 20]);
        $prodC = Product::factory()->public()->create(['name' => 'Produkt C', 'sort_order' => 30]);

        $response = $this->getJson('/api/catalog');
        $response->assertOk();
        $this->assertEquals([$prodA->id, $prodB->id, $prodC->id], collect($response->json('data.products'))->pluck('id')->all());

        // Symulacja przeciągnięcia Produktu C na sam początek (nowy sort_order = 5)
        $prodC->update(['sort_order' => 5]);

        $response2 = $this->getJson('/api/catalog');
        $response2->assertOk();
        $this->assertEquals([$prodC->id, $prodA->id, $prodB->id], collect($response2->json('data.products'))->pluck('id')->all());
    }

    public function test_gallery_artworks_reflect_drag_and_drop_order_changes(): void
    {
        $art1 = GalleryArtwork::create([
            'title' => 'Dzieło 1',
            'year' => '2024',
            'format' => 'A3',
            'technique' => 'akwarela',
            'image_path' => 'gallery/art1.webp',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $art2 = GalleryArtwork::create([
            'title' => 'Dzieło 2',
            'year' => '2024',
            'format' => 'A3',
            'technique' => 'akwarela',
            'image_path' => 'gallery/art2.webp',
            'sort_order' => 20,
            'is_active' => true,
        ]);
        $art3 = GalleryArtwork::create([
            'title' => 'Dzieło 3',
            'year' => '2024',
            'format' => 'A3',
            'technique' => 'akwarela',
            'image_path' => 'gallery/art3.webp',
            'sort_order' => 30,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/gallery');
        $response->assertOk();
        $items = $response->json('data.items');
        $this->assertEquals(['gallery-' . $art1->id, 'gallery-' . $art2->id, 'gallery-' . $art3->id], collect($items)->pluck('id')->all());

        // Symulacja przeciągnięcia Dzieła 3 na początek (sort_order = 1)
        $art3->update(['sort_order' => 1]);

        $response2 = $this->getJson('/api/gallery');
        $response2->assertOk();
        $items2 = $response2->json('data.items');
        $this->assertEquals(['gallery-' . $art3->id, 'gallery-' . $art1->id, 'gallery-' . $art2->id], collect($items2)->pluck('id')->all());
    }

    public function test_faq_items_reflect_drag_and_drop_order_changes(): void
    {
        $faq1 = FaqItem::create(['question' => 'Q1?', 'answer' => 'A1', 'sort_order' => 10, 'is_active' => true]);
        $faq2 = FaqItem::create(['question' => 'Q2?', 'answer' => 'A2', 'sort_order' => 20, 'is_active' => true]);
        $faq3 = FaqItem::create(['question' => 'Q3?', 'answer' => 'A3', 'sort_order' => 30, 'is_active' => true]);

        $response = $this->getJson('/api/faq');
        $response->assertOk();
        $this->assertEquals([$faq1->id, $faq2->id, $faq3->id], collect($response->json('data.items'))->pluck('id')->all());

        // Przeciągnięcie FAQ 2 na sam początek (sort_order = 5)
        $faq2->update(['sort_order' => 5]);

        $response2 = $this->getJson('/api/faq');
        $response2->assertOk();
        $this->assertEquals([$faq2->id, $faq1->id, $faq3->id], collect($response2->json('data.items'))->pluck('id')->all());
    }

    public function test_site_reviews_reflect_drag_and_drop_order_changes(): void
    {
        $rev1 = ProductReview::create([
            'customer_name' => 'Anna',
            'customer_email' => 'anna@example.com',
            'comment' => 'Super',
            'rating' => 5,
            'status' => 'publiczny',
            'sort_order' => 10,
        ]);
        $rev2 = ProductReview::create([
            'customer_name' => 'Bartosz',
            'customer_email' => 'bartosz@example.com',
            'comment' => 'Ekstra',
            'rating' => 5,
            'status' => 'publiczny',
            'sort_order' => 20,
        ]);

        $response = $this->getJson('/api/reviews/site');
        $response->assertOk();
        $this->assertEquals([$rev1->id, $rev2->id], collect($response->json('data'))->pluck('id')->all());

        // Zmiana kolejności opinii (Bartosz na początek)
        $rev2->update(['sort_order' => 5]);

        $response2 = $this->getJson('/api/reviews/site');
        $response2->assertOk();
        $this->assertEquals([$rev2->id, $rev1->id], collect($response2->json('data'))->pluck('id')->all());
    }

    public function test_filament_tables_have_reorderable_and_edit_record_actions(): void
    {
        $dummyProduct = Product::factory()->public()->create();
        $livewire = $this->createMock(\Filament\Tables\Contracts\HasTable::class);

        // 1. ProductsTable
        $productsTable = ProductsTable::configure(Table::make($livewire));
        $this->assertEquals('sort_order', $productsTable->getReorderColumn());
        $this->assertEquals('edit', $productsTable->getRecordAction($dummyProduct));

        // 2. GalleryArtworkTable
        $galleryTable = GalleryArtworkTable::configure(Table::make($livewire));
        $this->assertEquals('sort_order', $galleryTable->getReorderColumn());
        $this->assertEquals('edit', $galleryTable->getRecordAction($dummyProduct));

        // 3. FaqItemsTable
        $faqTable = FaqItemsTable::configure(Table::make($livewire));
        $this->assertEquals('sort_order', $faqTable->getReorderColumn());
        $this->assertEquals('edit', $faqTable->getRecordAction($dummyProduct));

        // 4. ProductCategoriesTable
        $categoriesTable = ProductCategoriesTable::configure(Table::make($livewire));
        $this->assertEquals('sort_order', $categoriesTable->getReorderColumn());
        $this->assertEquals('edit', $categoriesTable->getRecordAction($dummyProduct));

        // 5. ProductReviewsTable
        $reviewsTable = ProductReviewsTable::configure(Table::make($livewire));
        $this->assertEquals('sort_order', $reviewsTable->getReorderColumn());
        $this->assertEquals('edit', $reviewsTable->getRecordAction($dummyProduct));

        // 6. ProductAttributesTable
        $attributesTable = ProductAttributesTable::configure(Table::make($livewire));
        $this->assertEquals('sort_order', $attributesTable->getReorderColumn());
        $this->assertEquals('edit', $attributesTable->getRecordAction($dummyProduct));

        // 7. ContentPagesTable
        $pagesTable = ContentPagesTable::configure(Table::make($livewire));
        $this->assertEquals('sort_order', $pagesTable->getReorderColumn());
        $this->assertEquals('edit', $pagesTable->getRecordAction($dummyProduct));

        // 8. CouponsTable
        $couponsTable = CouponsTable::configure(Table::make($livewire));
        $this->assertEquals('edit', $couponsTable->getRecordAction($dummyProduct));

        // 9. CustomersTable
        $customersTable = CustomersTable::configure(Table::make($livewire));
        $this->assertEquals('edit', $customersTable->getRecordAction($dummyProduct));

        // 10. StoreSettingsTable
        $settingsTable = StoreSettingsTable::configure(Table::make($livewire));
        $this->assertEquals('edit', $settingsTable->getRecordAction($dummyProduct));

        // 11. RedirectRulesTable
        $redirectsTable = RedirectRulesTable::configure(Table::make($livewire));
        $this->assertEquals('edit', $redirectsTable->getRecordAction($dummyProduct));

        // 12. UsersTable
        $usersTable = UsersTable::configure(Table::make($livewire));
        $this->assertEquals('edit', $usersTable->getRecordAction($dummyProduct));
    }

    public function test_livewire_drag_and_drop_reorders_products_in_database_and_api(): void
    {
        $admin = User::factory()->create([
            'role' => \App\Domain\Commerce\Enums\UserRole::Admin->value,
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $prodA = Product::factory()->public()->create(['name' => 'Produkt A', 'sort_order' => 1]);
        $prodB = Product::factory()->public()->create(['name' => 'Produkt B', 'sort_order' => 2]);
        $prodC = Product::factory()->public()->create(['name' => 'Produkt C', 'sort_order' => 3]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\Products\Pages\ListProducts::class)
            ->call('reorderTable', [$prodC->id, $prodA->id, $prodB->id]);

        $prodA->refresh();
        $prodB->refresh();
        $prodC->refresh();

        // Sprawdzenie, czy C ma teraz najniższy sort_order
        $this->assertTrue($prodC->sort_order < $prodA->sort_order, 'Produkt C powinien mieć niższy sort_order niż Produkt A');
        $this->assertTrue($prodA->sort_order < $prodB->sort_order, 'Produkt A powinien mieć niższy sort_order niż Produkt B');

        // Sprawdzenie w API
        $response = $this->getJson('/api/catalog');
        $response->assertOk();
        $this->assertEquals([$prodC->id, $prodA->id, $prodB->id], collect($response->json('data.products'))->pluck('id')->all());
    }

    public function test_livewire_drag_and_drop_reorders_gallery_in_database_and_api(): void
    {
        $admin = User::factory()->create(['role' => \App\Domain\Commerce\Enums\UserRole::Admin->value]);
        $admin->forceFill(['is_admin' => true])->save();

        $art1 = GalleryArtwork::create(['title' => 'Art 1', 'year' => '2024', 'format' => 'A3', 'technique' => 'akwarela', 'image_path' => 'gallery/art1.webp', 'sort_order' => 1, 'is_active' => true]);
        $art2 = GalleryArtwork::create(['title' => 'Art 2', 'year' => '2024', 'format' => 'A3', 'technique' => 'akwarela', 'image_path' => 'gallery/art2.webp', 'sort_order' => 2, 'is_active' => true]);
        $art3 = GalleryArtwork::create(['title' => 'Art 3', 'year' => '2024', 'format' => 'A3', 'technique' => 'akwarela', 'image_path' => 'gallery/art3.webp', 'sort_order' => 3, 'is_active' => true]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\GalleryArtworks\Pages\ManageGalleryArtworks::class)
            ->call('reorderTable', [$art3->id, $art1->id, $art2->id]);

        $response = $this->getJson('/api/gallery');
        $response->assertOk();
        $this->assertEquals(['gallery-' . $art3->id, 'gallery-' . $art1->id, 'gallery-' . $art2->id], collect($response->json('data.items'))->pluck('id')->all());
    }

    public function test_livewire_drag_and_drop_reorders_faq_in_database_and_api(): void
    {
        $admin = User::factory()->create(['role' => \App\Domain\Commerce\Enums\UserRole::Admin->value]);
        $admin->forceFill(['is_admin' => true])->save();

        $faq1 = FaqItem::create(['question' => 'Q1?', 'answer' => 'A1', 'sort_order' => 1, 'is_active' => true]);
        $faq2 = FaqItem::create(['question' => 'Q2?', 'answer' => 'A2', 'sort_order' => 2, 'is_active' => true]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\FaqItems\Pages\ListFaqItems::class)
            ->call('reorderTable', [$faq2->id, $faq1->id]);

        $response = $this->getJson('/api/faq');
        $response->assertOk();
        $this->assertEquals([$faq2->id, $faq1->id], collect($response->json('data.items'))->pluck('id')->all());
    }

    public function test_livewire_drag_and_drop_reorders_categories_in_database_and_api(): void
    {
        $admin = User::factory()->create(['role' => \App\Domain\Commerce\Enums\UserRole::Admin->value]);
        $admin->forceFill(['is_admin' => true])->save();

        $cat1 = ProductCategory::create(['name' => 'Kategoria 1', 'slug' => 'kat-1', 'sort_order' => 1, 'is_active' => true]);
        $cat2 = ProductCategory::create(['name' => 'Kategoria 2', 'slug' => 'kat-2', 'sort_order' => 2, 'is_active' => true]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\ProductCategories\Pages\ListProductCategories::class)
            ->call('reorderTable', [$cat2->id, $cat1->id]);

        $cat1->refresh();
        $cat2->refresh();

        $this->assertTrue($cat2->sort_order < $cat1->sort_order);

        $response = $this->getJson('/api/catalog');
        $response->assertOk();
        $this->assertEquals([$cat2->id, $cat1->id], collect($response->json('data.categories'))->pluck('id')->all());
    }

    public function test_livewire_drag_and_drop_reorders_reviews_in_database_and_api(): void
    {
        $admin = User::factory()->create(['role' => \App\Domain\Commerce\Enums\UserRole::Admin->value]);
        $admin->forceFill(['is_admin' => true])->save();

        $rev1 = ProductReview::create(['customer_name' => 'Anna', 'customer_email' => 'anna@example.com', 'comment' => 'Super', 'rating' => 5, 'status' => 'publiczny', 'sort_order' => 1]);
        $rev2 = ProductReview::create(['customer_name' => 'Bartosz', 'customer_email' => 'bartosz@example.com', 'comment' => 'Ekstra', 'rating' => 5, 'status' => 'publiczny', 'sort_order' => 2]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\ProductReviews\Pages\ListProductReviews::class)
            ->call('reorderTable', [$rev2->id, $rev1->id]);

        $response = $this->getJson('/api/reviews/site');
        $response->assertOk();
        $this->assertEquals([$rev2->id, $rev1->id], collect($response->json('data'))->pluck('id')->all());
    }
}


