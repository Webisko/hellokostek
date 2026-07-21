<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicMediaUrl
{
    public static function resolve(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) || str_starts_with($value, '//')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return url($value);
        }

        return Storage::disk('public')->url($value);
    }

    /**
     * Resolves the responsive URLs for the given image path.
     *
     * @param string|null $value
     * @return array Map of width/original keys to absolute URLs
     */
    public static function resolveResponsive(?string $value): array
    {
        if (! filled($value)) {
            return [];
        }

        if (filter_var($value, FILTER_VALIDATE_URL) || str_starts_with($value, '//')) {
            return [
                'original' => $value,
            ];
        }

        $originalUrl = self::resolve($value);
        $result = [
            'original' => $originalUrl,
        ];

        $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return $result;
        }

        $settings = app(\App\Support\StoreSettings::class);
        $responsiveWidthsStr = $settings->model()->metadata['media_responsive_widths'] ?? null;
        if (filled($responsiveWidthsStr)) {
            $widths = array_filter(array_map('intval', explode(',', $responsiveWidthsStr)));
        } else {
            $widths = [360, 720, 1200];
        }

        $dirname = pathinfo($value, PATHINFO_DIRNAME);
        $filename = pathinfo($value, PATHINFO_FILENAME);
        $dirname = $dirname === '.' ? '' : $dirname . '/';

        $disk = Storage::disk('public');

        foreach ($widths as $width) {
            $responsivePath = $dirname . $filename . '-' . $width . 'w.' . $extension;

            // Check file existence efficiently on local disk
            try {
                $localPath = $disk->path($responsivePath);
                if (file_exists($localPath)) {
                    $result[$width . 'w'] = $disk->url($responsivePath);
                }
            } catch (\Throwable $e) {
                if ($disk->exists($responsivePath)) {
                    $result[$width . 'w'] = $disk->url($responsivePath);
                }
            }
        }

        return $result;
    }

    /**
     * Generates the srcset string for the responsive image.
     *
     * @param string|null $value
     * @return string|null
     */
    public static function responsiveSrcset(?string $value): ?string
    {
        $urls = self::resolveResponsive($value);
        if (empty($urls)) {
            return null;
        }

        $srcset = [];
        foreach ($urls as $key => $url) {
            if ($key === 'original') {
                continue;
            }
            $srcset[] = "{$url} {$key}";
        }

        if (count($srcset) > 0) {
            return implode(', ', $srcset);
        }

        return null;
    }
}