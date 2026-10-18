<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class GalleryArtwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'technique',
        'format',
        'year',
        'image_path',
        'original_url',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (GalleryArtwork $artwork): void {
            if ($artwork->year === null) {
                $artwork->year = (string) date('Y');
            }
            if ($artwork->technique === null) {
                $artwork->technique = '';
            }
        });

        static::saved(function (): void {
            \Illuminate\Support\Facades\Cache::forget('api.gallery.items');
        });

        static::deleted(function (): void {
            \Illuminate\Support\Facades\Cache::forget('api.gallery.items');
        });
    }
}
