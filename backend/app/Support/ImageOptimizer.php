<?php

namespace App\Support;

class ImageOptimizer
{
    /**
     * Optimizes an image: converts to WebP (optional), resizes to max constraints,
     * and generates smaller responsive copies.
     *
     * @param string $filePath Local path to the temporary/original file
     * @param bool $convertToWebP Whether to convert to WebP
     * @param int|null $maxWidth Max allowed width
     * @param int|null $maxHeight Max allowed height
     * @param array $responsiveWidths Target widths for responsive copies
     * @param int $quality Image output quality (0-100)
     * @return array|null Array with 'main_content' and 'responsive_contents' (width => content)
     */
    public static function process(
        string $filePath,
        bool $convertToWebP = true,
        ?int $maxWidth = null,
        ?int $maxHeight = null,
        array $responsiveWidths = [],
        int $quality = 80
    ): ?array {
        if (! extension_loaded('gd')) {
            return null;
        }

        $imageContent = @file_get_contents($filePath);
        if ($imageContent === false) {
            return null;
        }

        $image = @imagecreatefromstring($imageContent);
        if (! $image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // 1. Resize main image if it exceeds max constraints
        $mainImage = self::resizeIfNeeded($image, $width, $height, $maxWidth, $maxHeight);
        
        // 2. Get main image content
        $mainContent = self::getImageContent($mainImage, $convertToWebP, $filePath, $quality);
        if (! $mainContent) {
            if ($mainImage !== $image) {
                imagedestroy($mainImage);
            }
            imagedestroy($image);
            return null;
        }

        // 3. Generate responsive versions
        $responsiveContents = [];
        $mainWidth = imagesx($mainImage);
        $mainHeight = imagesy($mainImage);

        foreach ($responsiveWidths as $rWidth) {
            // Only generate copies smaller than the main image
            if ($rWidth >= $mainWidth) {
                continue;
            }

            $rImage = self::resizeToWidth($mainImage, $mainWidth, $mainHeight, $rWidth);
            if ($rImage) {
                $rContent = self::getImageContent($rImage, $convertToWebP, $filePath, $quality);
                if ($rContent) {
                    $responsiveContents[$rWidth] = $rContent;
                }
                if ($rImage !== $mainImage) {
                    imagedestroy($rImage);
                }
            }
        }

        // Cleanup
        if ($mainImage !== $image) {
            imagedestroy($mainImage);
        }
        imagedestroy($image);

        return [
            'main_content' => $mainContent,
            'responsive_contents' => $responsiveContents,
        ];
    }

    /**
     * Resizes an image resource if its dimensions exceed max width or height.
     */
    private static function resizeIfNeeded($image, int $width, int $height, ?int $maxWidth, ?int $maxHeight)
    {
        $newWidth = $width;
        $newHeight = $height;

        if ($maxWidth && $newWidth > $maxWidth) {
            $newHeight = (int) (($newHeight * $maxWidth) / $newWidth);
            $newWidth = $maxWidth;
        }

        if ($maxHeight && $newHeight > $maxHeight) {
            $newWidth = (int) (($newWidth * $maxHeight) / $newHeight);
            $newHeight = $maxHeight;
        }

        if ($newWidth === $width && $newHeight === $height) {
            return $image;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if (! $resized) {
            return $image;
        }

        // Preserve transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $resized;
    }

    /**
     * Resizes an image resource to a specific target width, preserving aspect ratio.
     */
    private static function resizeToWidth($image, int $width, int $height, int $targetWidth)
    {
        if ($width <= $targetWidth) {
            return $image;
        }

        $targetHeight = (int) (($height * $targetWidth) / $width);
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        if (! $resized) {
            return $image;
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $resized;
    }

    /**
     * Outputs the image content to a string buffer using WebP or its original mime type.
     */
    private static function getImageContent($image, bool $convertToWebP, string $originalPath, int $quality): ?string
    {
        ob_start();
        $success = false;

        if ($convertToWebP) {
            $success = imagewebp($image, null, $quality);
        } else {
            $mime = @mime_content_type($originalPath) ?: 'image/jpeg';
            if ($mime === 'image/png') {
                // GD quality for PNG is 0-9
                $pngQuality = (int) round((100 - $quality) / 10);
                $success = imagepng($image, null, min(9, max(0, $pngQuality)));
            } elseif ($mime === 'image/gif') {
                $success = imagegif($image, null);
            } else {
                $success = imagejpeg($image, null, $quality);
            }
        }

        $content = ob_get_clean();

        return $success ? $content : null;
    }
}
