<?php

namespace App\Http\Resources;

use App\Models\GalleryArtwork;
use App\Support\PublicMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin GalleryArtwork
 */
class GalleryArtworkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => 'gallery-' . $this->id,
            'title' => $this->title,
            'technique' => $this->technique,
            'format' => $this->format,
            'category' => $this->category?->name,
            'category_slug' => $this->category?->slug,
            'category_id' => $this->category_id,
            'year' => $this->year,
            'image_url' => PublicMediaUrl::resolve($this->image_path),
            'original_url' => $this->original_url
                ? (Str::startsWith($this->original_url, ['http://', 'https://']) ? $this->original_url : PublicMediaUrl::resolve($this->original_url))
                : null,
            'sort_order' => $this->sort_order,
        ];
    }
}
