<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Http\JsonResponse;

class ContentPageShowController extends Controller
{
    public function __invoke(string $slug): JsonResponse
    {
        $page = ContentPage::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        $baseUrl = rtrim((string) config('services.storefront.url', config('app.url')), '/');
        $pageUrl = ($page->slug === 'home' || $page->template === 'home') 
            ? $baseUrl . '/' 
            : $baseUrl . '/' . $page->slug;

        $defaultLocale = config('app.locale', 'pl');
        $supportedLocales = [$defaultLocale, config('app.fallback_locale', 'en')];
        $hreflangs = [];
        foreach ($supportedLocales as $locale) {
            $langPrefix = $locale === $defaultLocale ? '' : '/' . $locale;
            $hreflangs[] = [
                'locale' => $locale,
                'url' => ($page->slug === 'home' || $page->template === 'home') 
                    ? $baseUrl . $langPrefix . '/' 
                    : $baseUrl . $langPrefix . '/' . $page->slug,
            ];
        }

        $ogTitle = $page->metadata['og_title'] ?? $page->seo_title ?? $page->title;
        $ogDescription = $page->metadata['og_description'] ?? $page->seo_description ?? $page->excerpt;
        if (blank($ogDescription) && filled($page->content)) {
            $ogDescription = \Illuminate\Support\Str::limit(strip_tags($page->content), 160);
        }
        $ogImage = null;
        if (filled($page->metadata['og_image_path'] ?? null)) {
            $ogImage = \App\Support\PublicMediaUrl::resolve($page->metadata['og_image_path']);
        }
        if (blank($ogImage)) {
            $ogImage = $page->heroImageUrl();
        }

        $dynamicOgImageUrl = null;
        if (app('router')->has('og-image')) {
            try {
                $dynamicOgImageUrl = \Illuminate\Support\Facades\URL::signedRoute('og-image', [
                    'title' => $page->title,
                    'subtitle' => config('app.name', 'Sklep internetowy'),
                ]);
            } catch (\Throwable $e) {
            }
        }

        return response()->json([
            'data' => [
                'page' => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'excerpt' => $page->excerpt,
                    'content' => $page->content,
                    'hero_image_url' => $page->heroImageUrl(),
                    'hero_image_alt' => $page->metadata['hero_image_alt'] ?? null,
                    'template' => $page->template,
                    'seo_title' => $page->seo_title,
                    'seo_description' => $page->seo_description,
                    'is_noindex' => (bool) $page->is_noindex,
                    'canonical_url' => $pageUrl,
                    'hreflangs' => $hreflangs,
                    'social_meta' => [
                        'og:title' => $ogTitle,
                        'og:description' => $ogDescription,
                        'og:image' => $ogImage ?: $dynamicOgImageUrl,
                        'twitter:card' => 'summary_large_image',
                        'twitter:title' => $ogTitle,
                        'twitter:description' => $ogDescription,
                        'twitter:image' => $ogImage ?: $dynamicOgImageUrl,
                    ],
                    'dynamic_og_image_url' => $dynamicOgImageUrl,
                    'published_at' => optional($page->published_at)->toIso8601String(),
                    'metadata' => $page->metadata ?? [],
                ],
            ],
        ]);
    }
}