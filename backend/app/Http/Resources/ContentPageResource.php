<?php

namespace App\Http\Resources;

use App\Models\ContentPage;
use App\Support\PublicMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * @mixin ContentPage
 */
class ContentPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseUrl = rtrim((string) config('services.storefront.url', config('app.url')), '/');
        $pageUrl = ($this->slug === 'home' || $this->template === 'home')
            ? $baseUrl . '/'
            : $baseUrl . '/' . $this->slug;

        $hreflangs = [
            [
                'locale' => 'pl',
                'url' => $pageUrl,
            ],
        ];

        $ogTitle = $this->metadata['og_title'] ?? $this->seo_title ?? $this->title;
        $ogDescription = $this->metadata['og_description'] ?? $this->seo_description ?? $this->excerpt;
        if (blank($ogDescription) && filled($this->content)) {
            $ogDescription = Str::limit(strip_tags($this->content), 160);
        }

        $ogImage = null;
        if (filled($this->metadata['og_image_path'] ?? null)) {
            $ogImage = PublicMediaUrl::resolve($this->metadata['og_image_path']);
        }
        if (blank($ogImage)) {
            $ogImage = $this->heroImageUrl();
        }

        $dynamicOgImageUrl = null;
        if (app('router')->has('og-image')) {
            try {
                $dynamicOgImageUrl = URL::signedRoute('og-image', [
                    'title' => $this->title,
                    'subtitle' => config('app.name', 'Sklep internetowy'),
                ]);
            } catch (\Throwable $e) {
            }
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'hero_image_url' => $this->heroImageUrl(),
            'hero_image_alt' => $this->metadata['hero_image_alt'] ?? null,
            'template' => $this->template,
            'template_label' => ContentPage::templateOptions()[$this->template] ?? $this->template,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'is_noindex' => (bool) $this->is_noindex,
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
            'published_at' => optional($this->published_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'last_updated_formatted' => optional($this->updated_at ?? $this->published_at)->format('d.m.Y'),
            'sections' => $this->metadata['sections'] ?? [],
            'metadata' => $this->metadata ?? [],
        ];
    }
}
