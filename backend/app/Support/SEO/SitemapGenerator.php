<?php

namespace App\Support\SEO;

use App\Models\ContentPage;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SitemapGenerator
{
    public function generate(): string
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $baseUrl = rtrim(config('app.url'), '/');

        // 1. Strona główna
        $xml[] = $this->formatUrl($baseUrl . '/', now()->toAtomString(), 'daily', '1.0');

        // 2. Podstrony statyczne (ContentPage)
        $pages = ContentPage::publiclyVisible()->where('is_noindex', false)->get();
        foreach ($pages as $page) {
            $slug = ltrim($page->slug, '/');
            // Pomijamy stronę główną jeśli jest zdefiniowana w CMS pod osobnym slugiem
            if ($slug === '' || $slug === 'home' || $page->template === 'home') {
                continue;
            }
            $xml[] = $this->formatUrl(
                $baseUrl . '/' . $slug,
                $page->updated_at->toAtomString(),
                'weekly',
                '0.6'
            );
        }

        // 3. Produkty (Products)
        if (class_exists(Product::class)) {
            $products = Product::publicCatalog()->where('is_noindex', false)->get();
            foreach ($products as $product) {
                $xml[] = $this->formatUrl(
                    $baseUrl . '/products/' . $product->slug,
                    $product->updated_at->toAtomString(),
                    'daily',
                    '0.8'
                );
            }
        }

        // 4. Kategorie produktów (ProductCategories)
        if (class_exists(ProductCategory::class)) {
            $categories = ProductCategory::where('is_active', true)->get();
            foreach ($categories as $category) {
                $xml[] = $this->formatUrl(
                    $baseUrl . '/categories/' . $category->slug,
                    $category->updated_at->toAtomString(),
                    'weekly',
                    '0.7'
                );
            }
        }

        $xml[] = '</urlset>';

        $content = implode("\n", $xml);

        // Zapisz do katalogu public
        file_put_contents(public_path('sitemap.xml'), $content);

        return $content;
    }

    private function formatUrl(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return sprintf(
            "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>",
            htmlspecialchars($loc),
            $lastmod,
            $changefreq,
            $priority
        );
    }
}
