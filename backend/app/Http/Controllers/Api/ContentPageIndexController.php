<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentPageResource;
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
                'pages' => ContentPageResource::collection($pages),
            ],
        ]);
    }
}