<?php

namespace Tests\Feature\Api;

use App\Models\BlogPost;
use App\Models\ContentPage;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RedirectRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_valid_xml_with_resources(): void
    {
        // Setup storefront url
        config(['services.storefront.url' => 'https://test-storefront.com']);

        // Create data
        $product = Product::factory()->public()->create(['slug' => 'test-product']);
        $category = ProductCategory::factory()->create(['slug' => 'test-category', 'is_active' => true]);
        
        $post = BlogPost::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'author_name' => 'Author',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $page = ContentPage::create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'template' => 'default',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $content);
        $this->assertStringContainsString('https://test-storefront.com/', $content);
        $this->assertStringContainsString('https://test-storefront.com/products/test-product', $content);
        $this->assertStringContainsString('https://test-storefront.com/categories/test-category', $content);
        $this->assertStringContainsString('https://test-storefront.com/blog/test-post', $content);
        $this->assertStringContainsString('https://test-storefront.com/test-page', $content);
    }

    public function test_product_slug_change_creates_301_redirect(): void
    {
        $product = Product::factory()->public()->create(['slug' => 'old-product']);

        $product->update(['slug' => 'new-product']);

        $this->assertDatabaseHas('redirect_rules', [
            'source_path' => '/products/old-product',
            'target_path' => '/products/new-product',
            'status_code' => 301,
            'is_active' => true,
        ]);
    }

    public function test_product_slug_chain_redirect_prevention(): void
    {
        $product = Product::factory()->public()->create(['slug' => 'slug-a']);

        // Step 1: Change to slug-b
        $product->update(['slug' => 'slug-b']);
        $this->assertDatabaseHas('redirect_rules', [
            'source_path' => '/products/slug-a',
            'target_path' => '/products/slug-b',
        ]);

        // Step 2: Change to slug-c
        $product->update(['slug' => 'slug-c']);

        // Redirect A should now point directly to C
        $this->assertDatabaseHas('redirect_rules', [
            'source_path' => '/products/slug-a',
            'target_path' => '/products/slug-c',
        ]);

        // Redirect B should point directly to C
        $this->assertDatabaseHas('redirect_rules', [
            'source_path' => '/products/slug-b',
            'target_path' => '/products/slug-c',
        ]);
    }

    public function test_blog_post_slug_change_creates_301_redirect(): void
    {
        $post = BlogPost::create([
            'title' => 'Old Post',
            'slug' => 'old-post',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'author_name' => 'Author',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $post->update(['slug' => 'new-post']);

        $this->assertDatabaseHas('redirect_rules', [
            'source_path' => '/blog/old-post',
            'target_path' => '/blog/new-post',
            'status_code' => 301,
        ]);
    }

    public function test_content_page_slug_change_creates_301_redirect(): void
    {
        $page = ContentPage::create([
            'title' => 'Old Page',
            'slug' => 'old-page',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'template' => 'default',
            'is_active' => true,
        ]);

        $page->update(['slug' => 'new-page']);

        $this->assertDatabaseHas('redirect_rules', [
            'source_path' => '/old-page',
            'target_path' => '/new-page',
            'status_code' => 301,
        ]);
    }

    public function test_product_payload_returns_featured_image_alt(): void
    {
        $product = Product::factory()->public()->create([
            'slug' => 'test-product-alt',
            'metadata' => [
                'featured_image_alt' => 'Niesamowity alt tekst obrazka',
            ],
        ]);

        $response = $this->getJson('/api/catalog');
        $response->assertStatus(200);

        // Find the product in JSON
        $products = $response->json('data.products');
        $found = collect($products)->firstWhere('slug', 'test-product-alt');

        $this->assertNotNull($found);
        $this->assertEquals('Niesamowity alt tekst obrazka', $found['featured_image_alt']);
    }

    public function test_faq_index_returns_faqpage_schema(): void
    {
        \App\Models\FaqItem::create([
            'question' => 'Jakie sa koszty dostawy?',
            'answer' => 'Darmowa od 250 PLN.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/faq');
        $response->assertStatus(200);

        $schema = $response->json('data.schema_json_ld');
        $this->assertNotNull($schema);
        $this->assertEquals('FAQPage', $schema['@type']);
        $this->assertCount(1, $schema['mainEntity']);
        $this->assertEquals('Jakie sa koszty dostawy?', $schema['mainEntity'][0]['name']);
        $this->assertEquals('Darmowa od 250 PLN.', $schema['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_blog_post_show_returns_blogposting_schema_author_and_sources(): void
    {
        config(['services.storefront.url' => 'https://test-storefront.com']);

        $post = BlogPost::create([
            'title' => 'Test Post E-E-A-T',
            'slug' => 'test-post-eeat',
            'excerpt' => 'Post excerpt',
            'content' => 'Full post content',
            'author_name' => 'Dr Jan Kowalski',
            'is_active' => true,
            'published_at' => now(),
            'metadata' => [
                'author_bio' => 'Ekspert ds. e-commerce',
                'author_avatar_path' => 'authors/kowalski.jpg',
                'author_linkedin' => 'https://linkedin.com/in/kowalski',
                'sources' => [
                    ['title' => 'Badanie 1', 'url' => 'https://example.com/badanie1']
                ]
            ]
        ]);

        $response = $this->getJson('/api/blog/posts/test-post-eeat');
        $response->assertStatus(200);

        $data = $response->json('data.post');
        $this->assertEquals('Dr Jan Kowalski', $data['author_details']['name']);
        $this->assertEquals('Ekspert ds. e-commerce', $data['author_details']['bio']);
        $this->assertEquals('https://linkedin.com/in/kowalski', $data['author_details']['linkedin']);
        $this->assertCount(1, $data['sources']);
        $this->assertEquals('Badanie 1', $data['sources'][0]['title']);

        $schema = $data['schema_json_ld'];
        $this->assertNotNull($schema);
        $this->assertEquals('BlogPosting', $schema['@type']);
        $this->assertEquals('Dr Jan Kowalski', $schema['author']['name']);
        $this->assertEquals('Ekspert ds. e-commerce', $schema['author']['description']);
    }

    public function test_google_reviews_returns_local_business_schema_with_nap(): void
    {
        // Configure NAP settings
        $settings = \App\Models\StoreSetting::query()->first();
        if (!$settings) {
            $settings = \App\Models\StoreSetting::create([
                'store_name' => 'Sklep testowy',
                'currency' => 'PLN',
            ]);
        }
        $settings->update([
            'metadata' => [
                'phone' => '+48 123 456 789',
                'address_street' => 'ul. Wiejska 1',
                'address_city' => 'Warszawa',
                'address_postal_code' => '00-001',
                'address_country' => 'Polska',
                'latitude' => '52.23',
                'longitude' => '21.01',
            ]
        ]);

        $response = $this->getJson('/api/reviews/google');
        $response->assertStatus(200);

        $schema = $response->json('schema_json_ld');
        $this->assertEquals('LocalBusiness', $schema['@type']);
        $this->assertEquals('+48 123 456 789', $schema['telephone']);
        $this->assertEquals('ul. Wiejska 1', $schema['address']['streetAddress']);
        $this->assertEquals('Warszawa', $schema['address']['addressLocality']);
        $this->assertEquals('52.23', $schema['geo']['latitude']);
    }

    public function test_robots_txt_returns_dynamic_content_and_disallows_admin_path(): void
    {
        config(['services.storefront.url' => 'https://test-storefront.com']);
        $adminPath = env('FILAMENT_PATH', 'admin');

        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString("Disallow: /{$adminPath}", $content);
        $this->assertStringContainsString('Disallow: /api/checkout', $content);
        $this->assertStringContainsString('Disallow: /api/cart', $content);
        $this->assertStringContainsString('Disallow: /checkout', $content);
        $this->assertStringContainsString('Disallow: /cart', $content);
        $this->assertStringContainsString('Sitemap: https://test-storefront.com/sitemap.xml', $content);
    }

    public function test_product_detail_returns_og_metadata_and_canonical_url(): void
    {
        config(['services.storefront.url' => 'https://test-storefront.com']);

        // Test with fallback values
        $product = Product::factory()->public()->create([
            'slug' => 'fallback-product',
            'name' => 'Fallback Product Name',
            'short_description' => 'Fallback Short Description',
            'featured_image_path' => 'products/featured.jpg',
        ]);

        $response = $this->getJson('/api/catalog/products/fallback-product');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('https://test-storefront.com/products/fallback-product', $data['canonical_url']);
        $this->assertEquals('Fallback Product Name', $data['social_meta']['og:title']);
        $this->assertEquals('Fallback Short Description', $data['social_meta']['og:description']);
        $this->assertStringContainsString('products/featured.jpg', $data['social_meta']['og:image']);

        // Test with custom OG values
        $customProduct = Product::factory()->public()->create([
            'slug' => 'custom-product',
            'name' => 'Custom Product Name',
            'short_description' => 'Custom Short Description',
            'metadata' => [
                'og_title' => 'Custom OG Title',
                'og_description' => 'Custom OG Description',
                'og_image_path' => 'seo/og/custom.jpg',
            ]
        ]);

        $response = $this->getJson('/api/catalog/products/custom-product');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('https://test-storefront.com/products/custom-product', $data['canonical_url']);
        $this->assertEquals('Custom OG Title', $data['social_meta']['og:title']);
        $this->assertEquals('Custom OG Description', $data['social_meta']['og:description']);
        $this->assertStringContainsString('seo/og/custom.jpg', $data['social_meta']['og:image']);
    }

    public function test_blog_post_detail_returns_og_metadata_and_canonical_url(): void
    {
        config(['services.storefront.url' => 'https://test-storefront.com']);

        // Test with custom OG values
        $post = BlogPost::create([
            'title' => 'Blog Post Title',
            'slug' => 'blog-post-slug',
            'excerpt' => 'Blog Post Excerpt',
            'content' => 'Blog Post Content',
            'author_name' => 'Author',
            'is_active' => true,
            'published_at' => now()->subDay(),
            'metadata' => [
                'og_title' => 'Custom Blog OG Title',
                'og_description' => 'Custom Blog OG Description',
                'og_image_path' => 'seo/og/blog.jpg',
            ]
        ]);

        $response = $this->getJson('/api/blog/posts/blog-post-slug');
        $response->assertStatus(200);

        $data = $response->json('data.post');
        $this->assertEquals('https://test-storefront.com/blog/blog-post-slug', $data['canonical_url']);
        $this->assertEquals('Custom Blog OG Title', $data['social_meta']['og:title']);
        $this->assertEquals('Custom Blog OG Description', $data['social_meta']['og:description']);
        $this->assertStringContainsString('seo/og/blog.jpg', $data['social_meta']['og:image']);
    }

    public function test_content_page_detail_returns_og_metadata_and_canonical_url(): void
    {
        config(['services.storefront.url' => 'https://test-storefront.com']);

        // Test with fallback values
        $page = ContentPage::create([
            'title' => 'Page Title',
            'slug' => 'page-slug',
            'excerpt' => 'Page Excerpt',
            'content' => 'Page Content',
            'template' => 'default',
            'is_active' => true,
            'published_at' => now()->subDay(),
            'hero_image_path' => 'pages/hero.jpg',
        ]);

        $response = $this->getJson('/api/content/pages/page-slug');
        $response->assertStatus(200);

        $data = $response->json('data.page');
        $this->assertEquals('https://test-storefront.com/page-slug', $data['canonical_url']);
        $this->assertEquals('Page Title', $data['social_meta']['og:title']);
        $this->assertEquals('Page Excerpt', $data['social_meta']['og:description']);
        $this->assertStringContainsString('pages/hero.jpg', $data['social_meta']['og:image']);
    }

    public function test_catalog_filters_by_merchandising_flags(): void
    {
        // Setup
        Product::query()->delete();

        $newProduct = Product::factory()->public()->create([
            'slug' => 'new-prod',
            'is_new' => true,
            'is_bestseller' => false,
        ]);

        $bestsellerProduct = Product::factory()->public()->create([
            'slug' => 'best-prod',
            'is_new' => false,
            'is_bestseller' => true,
        ]);

        $normalProduct = Product::factory()->public()->create([
            'slug' => 'normal-prod',
            'is_new' => false,
            'is_bestseller' => false,
        ]);

        // Filter by is_new=1
        $response = $this->getJson('/api/catalog?is_new=1');
        $response->assertStatus(200);
        $products = $response->json('data.products');
        $this->assertCount(1, $products);
        $this->assertEquals('new-prod', $products[0]['slug']);
        $this->assertTrue($products[0]['is_new']);

        // Filter by is_bestseller=1
        $response = $this->getJson('/api/catalog?is_bestseller=1');
        $response->assertStatus(200);
        $products = $response->json('data.products');
        $this->assertCount(1, $products);
        $this->assertEquals('best-prod', $products[0]['slug']);
        $this->assertTrue($products[0]['is_bestseller']);
    }
}

