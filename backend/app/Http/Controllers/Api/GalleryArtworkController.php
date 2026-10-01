<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryArtworkResource;
use App\Models\GalleryArtwork;
use Illuminate\Http\JsonResponse;

class GalleryArtworkController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $items = GalleryArtwork::query()
            ->with('category')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => [
                'items' => GalleryArtworkResource::collection($items),
            ],
        ]);
    }
}
