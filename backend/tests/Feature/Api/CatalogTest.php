<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_index_returns_ok(): void
    {
        $response = $this->getJson('/api/catalog');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['products', 'categories'],
            ]);
    }

    public function test_catalog_index_returns_empty_when_no_data(): void
    {
        $response = $this->getJson('/api/catalog');

        $response->assertOk()
            ->assertJsonPath('data.products', [])
            ->assertJsonPath('data.categories', []);
    }

    public function test_catalog_index_returns_only_public_products(): void
    {
        // Active + published product – should appear
        Product::factory()->public()->create();

        // Inactive product – must not appear
        Product::factory()->inactive()->create();

        $response = $this->getJson('/api/catalog');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.products'));
    }

    public function test_catalog_index_includes_active_categories(): void
    {
        ProductCategory::factory()->create(['is_active' => true]);
        ProductCategory::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/catalog');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.categories'));
    }

    public function test_catalog_product_show_returns_404_for_unknown_slug(): void
    {
        $response = $this->getJson('/api/catalog/products/non-existent-slug');

        $response->assertNotFound();
    }

    public function test_catalog_product_show_returns_product(): void
    {
        Product::factory()->public()->create(['slug' => 'test-produkt']);

        $response = $this->getJson('/api/catalog/products/test-produkt');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'test-produkt');
    }
}
