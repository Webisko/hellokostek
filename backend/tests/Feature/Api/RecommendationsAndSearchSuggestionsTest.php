<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationsAndSearchSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_returns_empty_when_query_too_short(): void
    {
        $response = $this->getJson(route('api.catalog.search.suggest', ['query' => 'a']));

        $response->assertStatus(200)
            ->assertJson([
                'suggestions' => [],
                'products' => [],
                'categories' => [],
            ]);
    }

    public function test_suggest_returns_matching_products_and_categories_and_phrases(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Książki Science Fiction',
            'slug' => 'ksiazki-sci-fi',
        ]);

        $product1 = Product::factory()->public()->create([
            'name' => 'Władca Pierścieni',
            'slug' => 'wladca-pierscieni',
            'sku' => 'LOTR-001',
            'manual_tags' => ['fantasy', 'tolkien'],
        ]);

        $product2 = Product::factory()->public()->create([
            'name' => 'Władca Much',
            'slug' => 'wladca-much',
            'sku' => 'WM-002',
            'manual_tags' => ['klasyka'],
        ]);

        // Connect product to category
        $product1->categories()->attach($category->id);

        // Search for "Władca"
        $response = $this->getJson(route('api.catalog.search.suggest', ['query' => 'Władca']));

        $response->assertStatus(200);
        
        $data = $response->json();
        
        $this->assertCount(2, $data['products']);
        $this->assertContains('Władca Pierścieni', $data['suggestions']);
        $this->assertContains('Władca Much', $data['suggestions']);

        // Search for category name "Książki"
        $response2 = $this->getJson(route('api.catalog.search.suggest', ['query' => 'Książki']));
        $response2->assertStatus(200);
        $data2 = $response2->json();
        $this->assertCount(1, $data2['categories']);
        $this->assertEquals('Książki Science Fiction', $data2['categories'][0]['name']);
    }

    public function test_recommendations_manual_relations(): void
    {
        $product = Product::factory()->public()->create(['slug' => 'main-product']);
        $upsellProduct = Product::factory()->public()->create(['slug' => 'upsell-product']);
        $crossSellProduct = Product::factory()->public()->create(['slug' => 'cross-sell-product']);
        $similarProduct = Product::factory()->public()->create(['slug' => 'similar-product']);

        // Create relations
        ProductRelation::create([
            'product_id' => $product->id,
            'related_product_id' => $upsellProduct->id,
            'relation_type' => 'upsell',
            'sort_order' => 1,
        ]);

        ProductRelation::create([
            'product_id' => $product->id,
            'related_product_id' => $crossSellProduct->id,
            'relation_type' => 'cross_sell',
            'sort_order' => 1,
        ]);

        ProductRelation::create([
            'product_id' => $product->id,
            'related_product_id' => $similarProduct->id,
            'relation_type' => 'similar',
            'sort_order' => 1,
        ]);

        $response = $this->getJson(route('api.catalog.products.recommendations', ['slug' => 'main-product']));

        $response->assertStatus(200)
            ->assertJsonPath('upsells.0.slug', 'upsell-product')
            ->assertJsonPath('cross_sells.0.slug', 'cross-sell-product')
            ->assertJsonPath('similar_products.0.slug', 'similar-product');
    }

    public function test_recommendations_collaborative_filtering_also_bought(): void
    {
        $productA = Product::factory()->public()->create(['slug' => 'product-a']);
        $productB = Product::factory()->public()->create(['slug' => 'product-b']);
        $productC = Product::factory()->public()->create(['slug' => 'product-c']);
        
        // Order 1: Product A + Product B
        $order1 = Order::create([
            'number' => 'ORD-2026-001',
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'pending',
            'currency' => 'PLN',
            'customer_email' => 'test@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'subtotal_amount' => 3000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 3000,
            'billing_address' => [],
            'shipping_address' => [],
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $productA->id,
            'product_type' => 'physical',
            'sku' => $productA->sku,
            'name' => $productA->name,
            'quantity' => 1,
            'unit_price_amount' => 1000,
            'regular_unit_price_amount' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $productB->id,
            'product_type' => 'physical',
            'sku' => $productB->sku,
            'name' => $productB->name,
            'quantity' => 1,
            'unit_price_amount' => 2000,
            'regular_unit_price_amount' => 2000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 2000,
        ]);

        // Order 2: Product A + Product B + Product C
        $order2 = Order::create([
            'number' => 'ORD-2026-002',
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'pending',
            'currency' => 'PLN',
            'customer_email' => 'test@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'subtotal_amount' => 6000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 6000,
            'billing_address' => [],
            'shipping_address' => [],
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $productA->id,
            'product_type' => 'physical',
            'sku' => $productA->sku,
            'name' => $productA->name,
            'quantity' => 1,
            'unit_price_amount' => 1000,
            'regular_unit_price_amount' => 1000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $productB->id,
            'product_type' => 'physical',
            'sku' => $productB->sku,
            'name' => $productB->name,
            'quantity' => 1,
            'unit_price_amount' => 2000,
            'regular_unit_price_amount' => 2000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 2000,
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $productC->id,
            'product_type' => 'physical',
            'sku' => $productC->sku,
            'name' => $productC->name,
            'quantity' => 1,
            'unit_price_amount' => 3000,
            'regular_unit_price_amount' => 3000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 3000,
        ]);

        $response = $this->getJson(route('api.catalog.products.recommendations', ['slug' => 'product-a']));

        $response->assertStatus(200);

        $alsoBought = $response->json('also_bought');
        $this->assertCount(2, $alsoBought);

        // Product B cooccurred 2 times, Product C cooccurred 1 time.
        // Therefore, Product B should be recommended first.
        $this->assertEquals('product-b', $alsoBought[0]['slug']);
        $this->assertEquals('product-c', $alsoBought[1]['slug']);
    }
}
