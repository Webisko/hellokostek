<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'title',
        'alt_text',
        'file_name',
        'file_path',
        'disk',
        'mime_type',
        'file_size',
        'width',
        'height',
        'category',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Media $media) {
            if ($media->file_path && Storage::disk($media->disk ?? 'public')->exists($media->file_path)) {
                Storage::disk($media->disk ?? 'public')->delete($media->file_path);
            }
        });
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?? 'public')->url($this->file_path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $i), 2) . ' ' . ($units[$i] ?? 'B');
    }
}
