<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\ContentPage;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __construct(
        private readonly \App\Support\StoreSettings $storeSettings,
    ) {
    }

    public function __invoke(): Response
    {
        $baseUrl = rtrim((string) config('services.storefront.url', config('app.url')), '/');
        
        if ($this->storeSettings->globalNoindex()) {
            $xml = [];
            $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            $xml[] = '</urlset>';
            return response(implode("\n", $xml), 200)
                ->header('Content-Type', 'application/xml');
        }

        $xmlContent = Cache::remember('sitemap_xml', 86400, function () use ($baseUrl) {
            $xml = [];
            $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            // Homepage
            $xml[] = '    <url>';
            $xml[] = '        <loc>' . htmlspecialchars($baseUrl . '/') . '</loc>';
            $xml[] = '        <changefreq>daily</changefreq>';
            $xml[] = '        <priority>1.0</priority>';
            $xml[] = '    </url>';
            
            // Products
            $products = Product::query()->publicCatalog()->where('is_noindex', false)->get();
            foreach ($products as $product) {
                $xml[] = '    <url>';
                $xml[] = '        <loc>' . htmlspecialchars($baseUrl . '/products/' . $product->slug) . '</loc>';
                if ($product->updated_at) {
                    $xml[] = '        <lastmod>' . $product->updated_at->toAtomString() . '</lastmod>';
                }
                $xml[] = '        <changefreq>weekly</changefreq>';
                $xml[] = '        <priority>0.8</priority>';
                $xml[] = '    </url>';
            }
            
            // Categories
            $categories = ProductCategory::query()->where('is_active', true)->where('is_noindex', false)->get();
            foreach ($categories as $category) {
                $xml[] = '    <url>';
                $xml[] = '        <loc>' . htmlspecialchars($baseUrl . '/categories/' . $category->slug) . '</loc>';
                if ($category->updated_at) {
                    $xml[] = '        <lastmod>' . $category->updated_at->toAtomString() . '</lastmod>';
                }
                $xml[] = '        <changefreq>weekly</changefreq>';
                $xml[] = '        <priority>0.7</priority>';
                $xml[] = '    </url>';
            }
            
            // Blog Posts
            $posts = BlogPost::query()->publiclyVisible()->where('is_noindex', false)->get();
            foreach ($posts as $post) {
                $xml[] = '    <url>';
                $xml[] = '        <loc>' . htmlspecialchars($baseUrl . '/blog/' . $post->slug) . '</loc>';
                if ($post->updated_at) {
                    $xml[] = '        <lastmod>' . $post->updated_at->toAtomString() . '</lastmod>';
                }
                $xml[] = '        <changefreq>monthly</changefreq>';
                $xml[] = '        <priority>0.6</priority>';
                $xml[] = '    </url>';
            }
            
            // Content Pages
            $pages = ContentPage::query()->publiclyVisible()->where('is_noindex', false)->get();
            foreach ($pages as $page) {
                // If it's home, it's already added at the top
                if ($page->template === 'home' || $page->slug === 'home') {
                    continue;
                }
                
                $xml[] = '    <url>';
                $xml[] = '        <loc>' . htmlspecialchars($baseUrl . '/' . $page->slug) . '</loc>';
                if ($page->updated_at) {
                    $xml[] = '        <lastmod>' . $page->updated_at->toAtomString() . '</lastmod>';
                }
                $xml[] = '        <changefreq>monthly</changefreq>';
                $xml[] = '        <priority>0.5</priority>';
                $xml[] = '    </url>';
            }
            
            $xml[] = '</urlset>';

            return implode("\n", $xml);
        });
        
        return response($xmlContent, 200)
            ->header('Content-Type', 'application/xml');
    }
}
