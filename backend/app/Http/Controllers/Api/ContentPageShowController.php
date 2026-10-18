<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentPageResource;
use App\Models\ContentPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ContentPageShowController extends Controller
{
    public function __invoke(string $slug): JsonResponse
    {
        $pagePayload = Cache::remember("api.content.page.{$slug}", 3600, function () use ($slug): ?array {
            $page = ContentPage::query()
                ->publiclyVisible()
                ->where('slug', $slug)
                ->first();

            return $page ? (new ContentPageResource($page))->resolve() : null;
        });

        abort_if($pagePayload === null, 404);

        return response()->json([
            'data' => [
                'page' => $pagePayload,
            ],
        ]);
    }
}