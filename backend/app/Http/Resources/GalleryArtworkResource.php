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
    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return (string) ($value['pl'] ?? $value['PL'] ?? $value['en'] ?? $value['EN'] ?? reset($value) ?: '');
        }
        $str = trim((string) $value);
        if (str_starts_with($str, '{') && str_ends_with($str, '}')) {
            $decoded = json_decode($str, true);
            if (is_array($decoded)) {
                return (string) ($decoded['pl'] ?? $decoded['PL'] ?? $decoded['en'] ?? $decoded['EN'] ?? reset($decoded) ?: $str);
            }
        }
        return $str;
    }

    public function toArray(Request $request): array
    {
        $rawBullets = data_get($this->metadata, 'bullet_points');
        $bulletPoints = null;
        if (is_string($rawBullets)) {
            $bulletPoints = array_values(array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $rawBullets) ?: []),
                fn (string $line) => $line !== ''
            ));
        } elseif (is_array($rawBullets)) {
            $bulletPoints = array_values(array_filter(
                array_map(fn ($line) => trim((string) $line), $rawBullets),
                fn (string $line) => $line !== ''
            ));
        }

        return [
            'id' => 'gallery-' . $this->id,
            'title' => $this->normalizeText($this->title),
            'technique' => $this->normalizeText($this->technique),
            'format' => $this->normalizeText($this->format),
            'category' => $this->normalizeText($this->category?->name),
            'category_slug' => $this->category?->slug,
            'category_id' => $this->category_id,
            'year' => $this->year,
            'bullet_points' => $bulletPoints,
            'image_url' => PublicMediaUrl::resolve($this->image_path),
            'original_url' => $this->original_url
                ? (Str::startsWith($this->original_url, ['http://', 'https://']) ? $this->original_url : PublicMediaUrl::resolve($this->original_url))
                : null,
            'sort_order' => $this->sort_order,
        ];
    }
}
