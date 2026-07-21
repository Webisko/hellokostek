<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryArtwork;
use App\Support\PublicMediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class GalleryArtworkController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $items = GalleryArtwork::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => [
                'items' => $items->map(fn (GalleryArtwork $item): array => [
                    'id' => 'gallery-' . $item->id,
                    'title' => $item->title,
                    'technique' => $item->technique,
                    'year' => $item->year,
                    'image_url' => PublicMediaUrl::resolve($item->image_path),
                    'original_url' => $item->original_url
                        ? (Str::startsWith($item->original_url, ['http://', 'https://']) ? $item->original_url : PublicMediaUrl::resolve($item->original_url))
                        : null,
                    'sort_order' => $item->sort_order,
                ])->all(),
            ],
        ]);
    }
}
