<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Http\JsonResponse;

class ContentPageIndexController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $pages = ContentPage::query()
            ->publiclyVisible()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return response()->json([
            'data' => [
                'pages' => $pages->map(fn (ContentPage $page): array => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'excerpt' => $page->excerpt,
                    'hero_image_url' => $page->heroImageUrl(),
                    'hero_image_alt' => $page->metadata['hero_image_alt'] ?? null,
                    'template' => $page->template,
                    'template_label' => ContentPage::templateOptions()[$page->template] ?? $page->template,
                    'published_at' => optional($page->published_at)->toIso8601String(),
                ])->all(),
            ],
        ]);
    }
}