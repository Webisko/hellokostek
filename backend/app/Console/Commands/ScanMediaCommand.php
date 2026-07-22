<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

class ScanMediaCommand extends Command
{
    protected $signature = 'media:scan';

    protected $description = 'Skanuje dysk publiczny w poszukiwaniu istniejących plików i dodaje je do tabeli media';

    public function handle(): int
    {
        $this->info('Skanowanie plików na dysku publicznym...');

        $disk = Storage::disk('public');
        $rootPath = $disk->path('');

        if (! is_dir($rootPath)) {
            $this->error('Katalog publiczny nie istnieje!');
            return Command::FAILURE;
        }

        $finder = new Finder();
        $finder->files()->in($rootPath);

        $count = 0;
        foreach ($finder as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());

            // Skip hidden git files or lock files
            if (str_starts_with($relativePath, '.') || str_contains($relativePath, '/.')) {
                continue;
            }

            // Determine category from folder path
            $category = 'general';
            if (str_starts_with($relativePath, 'products/')) {
                $category = 'products';
            } elseif (str_starts_with($relativePath, 'gallery/')) {
                $category = 'gallery';
            } elseif (str_starts_with($relativePath, 'branding/') || str_starts_with($relativePath, 'logo')) {
                $category = 'branding';
            }

            $fileName = $file->getFilename();
            $title = pathinfo($fileName, PATHINFO_FILENAME);

            $mimeType = mime_content_type($file->getRealPath()) ?: 'application/octet-stream';
            $fileSize = $file->getSize();

            $width = null;
            $height = null;

            if (str_starts_with($mimeType, 'image/')) {
                $imageSize = @getimagesize($file->getRealPath());
                if ($imageSize) {
                    $width = $imageSize[0];
                    $height = $imageSize[1];
                }
            }

            Media::query()->firstOrCreate(
                [
                    'file_path' => $relativePath,
                    'disk' => 'public',
                ],
                [
                    'title' => $title,
                    'file_name' => $fileName,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'width' => $width,
                    'height' => $height,
                    'category' => $category,
                    'alt_text' => $title,
                ]
            );

            $count++;
        }

        $this->info("Pomyślnie zreindeksowano {$count} plików do Biblioteki Multimediów.");

        return Command::SUCCESS;
    }
}
