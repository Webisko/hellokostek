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

    public function test_catalog_products_are_ordered_by_sort_order(): void
    {
        $prod3 = Product::factory()->public()->create(['name' => 'Produkt A', 'sort_order' => 30]);
        $prod1 = Product::factory()->public()->create(['name' => 'Produkt Z', 'sort_order' => 10]);
        $prod2 = Product::factory()->public()->create(['name' => 'Produkt M', 'sort_order' => 20]);

        $response = $this->getJson('/api/catalog');

        $response->assertOk();
        $products = $response->json('data.products');
        $this->assertCount(3, $products);
        $this->assertEquals($prod1->id, $products[0]['id']);
        $this->assertEquals($prod2->id, $products[1]['id']);
        $this->assertEquals($prod3->id, $products[2]['id']);
    }
}
