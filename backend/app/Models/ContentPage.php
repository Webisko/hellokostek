<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentPage extends Model
{
    use HasFactory, SoftDeletes;

    public static function templateOptions(): array
    {
        return [
            'default' => 'Domyslna strona',
            'home' => 'Strona glowna',
            'about' => 'O marce / o nas',
            'offer' => 'Strona ofertowa',
            'faq' => 'FAQ',
            'contact' => 'Kontakt',
            'legal' => 'Strona prawna',
            'landing' => 'Landing page',
        ];
    }

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content',
        'hero_image_path',
        'template',
        'seo_title',
        'seo_description',
        'is_active',
        'published_at',
        'sort_order',
        'metadata',
        'is_noindex',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
            'metadata' => 'array',
            'is_noindex' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ContentPage $page): void {
            if ($page->isDirty('slug')) {
                $oldSlug = $page->getOriginal('slug');
                $newSlug = $page->slug;

                if (filled($oldSlug) && filled($newSlug) && $oldSlug !== $newSlug) {
                    $oldPath = '/' . ltrim($oldSlug, '/');
                    $newPath = '/' . ltrim($newSlug, '/');

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

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $nestedQuery): Builder {
                return $nestedQuery
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function heroImageUrl(): ?string
    {
        return PublicMediaUrl::resolve($this->hero_image_path);
    }
}