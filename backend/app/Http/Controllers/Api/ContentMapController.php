<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\ContentPage;
use App\Models\FaqItem;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;

class ContentMapController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $pages = ContentPage::query()
            ->publiclyVisible()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'slug', 'title', 'template']);

        $faqGroups = FaqItem::query()
            ->active()
            ->select('group_name')
            ->distinct()
            ->orderBy('group_name')
            ->pluck('group_name')
            ->filter(fn (?string $group): bool => filled($group))
            ->values()
            ->all();

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'slug', 'name']);

        return response()->json([
            'data' => [
                'pages' => $pages->map(fn (ContentPage $page): array => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'template' => $page->template,
                    'template_label' => ContentPage::templateOptions()[$page->template] ?? $page->template,
                ])->all(),
                'blog' => [
                    'posts_endpoint' => route('api.blog.posts.index', absolute: false),
                    'published_posts_count' => BlogPost::query()->publiclyVisible()->count(),
                ],
                'faq' => [
                    'items_endpoint' => route('api.faq.index', absolute: false),
                    'groups' => $faqGroups,
                ],
                'catalog' => [
                    'catalog_endpoint' => route('api.catalog.index', absolute: false),
                    'categories' => $categories->map(fn (ProductCategory $category): array => [
                        'id' => $category->id,
                        'slug' => $category->slug,
                        'name' => $category->name,
                    ])->all(),
                ],
            ],
        ]);
    }
}