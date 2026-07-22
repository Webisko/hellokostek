<?php

namespace App\Support\SEO;

use App\Models\Course;
use App\Models\Product;

class JsonLdHelper
{
    /**
     * Generuje schemat JSON-LD dla produktu
     */
    public static function product(Product $product): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'image' => $product->featuredImageUrl() ?: ($baseUrl . '/images/placeholder.png'),
            'description' => strip_tags($product->short_description ?: $product->description ?: ''),
            'sku' => $product->sku ?: 'PROD-' . $product->id,
            'offers' => [
                '@type' => 'Offer',
                'url' => $baseUrl . '/products/' . $product->slug,
                'priceCurrency' => $product->currency ?: 'PLN',
                'price' => number_format(($product->sale_price_amount ?? $product->regular_price_amount) / 100, 2, '.', ''),
                'priceValidUntil' => now()->addYear()->toDateString(),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => $product->stock_quantity > 0 || !$product->manages_stock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ]
        ];

        // Dodaj oceny, jeśli produkt ma zatwierdzone opinie
        $avgRating = $product->averageRating();
        $reviewsCount = $product->approvedReviewsCount();
        if ($reviewsCount > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $avgRating,
                'reviewCount' => $reviewsCount,
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        return self::render($data);
    }

    /**
     * Generuje schemat JSON-LD dla kursu (LMS)
     */
    public static function course($course): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course->title ?? '',
            'description' => strip_tags($course->description ?? ''),
            'provider' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'sameAs' => $baseUrl,
            ]
        ];

        return self::render($data);
    }

    /**
     * Generuje schemat JSON-LD dla strony FAQ
     */
    public static function faq(iterable $faqItems): string
    {
        $mainEntity = [];
        foreach ($faqItems as $item) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $item->question ?? '',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($item->answer ?? ''),
                ]
            ];
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];

        return self::render($data);
    }



    private static function render(array $data): string
    {
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }
}
