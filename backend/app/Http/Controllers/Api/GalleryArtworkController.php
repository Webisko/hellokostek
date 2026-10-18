<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryArtworkResource;
use App\Models\GalleryArtwork;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class GalleryArtworkController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $items = Cache::remember('api.gallery.items', 3600, function (): array {
            $records = GalleryArtwork::query()
                ->with('category')
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return GalleryArtworkResource::collection($records)->resolve();
        });

        return response()->json([
            'data' => [
                'items' => $items,
            ],
        ]);
    }
}
