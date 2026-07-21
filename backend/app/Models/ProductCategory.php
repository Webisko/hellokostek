<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class ProductCategory extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'seo_title',
        'seo_description',
        'sort_order',
        'is_active',
        'is_noindex',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_noindex' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ProductCategory $category): void {
            if ($category->isDirty('slug')) {
                $oldSlug = $category->getOriginal('slug');
                $newSlug = $category->slug;

                if (filled($oldSlug) && filled($newSlug) && $oldSlug !== $newSlug) {
                    $oldPath = '/categories/' . $oldSlug;
                    $newPath = '/categories/' . $newSlug;

                    \App\Models\RedirectRule::updateOrCreate(
                        ['source_path' => $oldPath],
                        [
                            'target_path' => $newPath,
                            'status_code' => 301,
                            'is_active' => true,
                        ]
                    );

                    \App\Models\RedirectRule::query()
                        ->where('target_path', $oldPath)
                        ->update(['target_path' => $newPath]);
                }
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    public function galleryArtworks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GalleryArtwork::class, 'category_id');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\ProductAttribute::class)
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}