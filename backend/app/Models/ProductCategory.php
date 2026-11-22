<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class ProductCategory extends Model
{
    use HasFactory;

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
        static::creating(function (ProductCategory $category): void {
            if (blank($category->slug) && filled($category->name)) {
                $category->slug = self::generateUniqueSlug((string) $category->name);
            }
            if ($category->sort_order === null) {
                $category->sort_order = (int) (static::max('sort_order') ?? 0) + 10;
            }
        });

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

    public static function createQuickByName(string $name): self
    {
        $cleanName = trim($name);
        $baseSlug = \Illuminate\Support\Str::slug($cleanName) ?: 'kategoria';

        $existing = static::query()->where('slug', $baseSlug)->first();
        if ($existing instanceof self) {
            return $existing;
        }

        return static::create([
            'name' => $cleanName,
            'slug' => self::generateUniqueSlug($cleanName),
            'is_active' => true,
            'sort_order' => (int) (static::max('sort_order') ?? 0) + 10,
        ]);
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'kategoria';
        $candidate = $base;
        $suffix = 2;

        while (static::query()->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
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