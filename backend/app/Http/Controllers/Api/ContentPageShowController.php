<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentPageResource;
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

        return response()->json([
            'data' => [
                'page' => new ContentPageResource($page),
            ],
        ]);
    }
}