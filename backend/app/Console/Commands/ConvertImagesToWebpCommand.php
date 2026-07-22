<?php

namespace App\Console\Commands;

use App\Models\GalleryArtwork;
use App\Models\Media;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

class ConvertImagesToWebpCommand extends Command
{
    protected $signature = 'media:convert-webp {--dry-run : Pokazuje pliki do konwersji bez zapisu}';

    protected $description = 'Konwertuje wszystkie zdjęcia JPG/PNG na serwerze do formatu WebP i aktualizuje odnośniki w bazie danych';

    public function handle(): int
    {
        $this->info('Rozpoczynam analizę i konwersję plików graficznych do formatu WebP...');

        if (! extension_loaded('gd')) {
            $this->error('Brak rozszerzenia PHP GD! Konwersja nie jest możliwa.');
            return Command::FAILURE;
        }

        $disk = Storage::disk('public');
        $rootPath = $disk->path('');

        if (! is_dir($rootPath)) {
            $this->error("Katalog dysku publicznego [{$rootPath}] nie istnieje.");
            return Command::FAILURE;
        }

        $finder = new Finder();
        $finder->files()->in($rootPath)->name(['*.jpg', '*.jpeg', '*.png', '*.JPG', '*.JPEG', '*.PNG']);

        $convertedCount = 0;
        $totalOriginalSize = 0;
        $totalWebpSize = 0;
        $replacements = [];

        foreach ($finder as $file) {
            $originalRealPath = $file->getRealPath();
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());

            // Determine webp relative and real path
            $pathInfo = pathinfo($relativePath);
            $webpRelativePath = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] . '/' : '') . $pathInfo['filename'] . '.webp';
            $webpRealPath = $disk->path($webpRelativePath);

            $origSize = $file->getSize();
            $totalOriginalSize += $origSize;

            if ($this->option('dry-run')) {
                $this->line("[DRY-RUN] Do konwersji: {$relativePath} -> {$webpRelativePath}");
                $convertedCount++;
                continue;
            }

            // Convert using ImageOptimizer
            $result = ImageOptimizer::process(
                $originalRealPath,
                true, // convert to webp
                1920, // max width
                1920, // max height
                [360, 720, 1200],
                82 // quality
            );

            if ($result && ! empty($result['main_content'])) {
                // Write webp main file
                file_put_contents($webpRealPath, $result['main_content']);

                // Write responsive copies
                foreach ($result['responsive_contents'] as $w => $rContent) {
                    $rRelativePath = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] . '/' : '') . $pathInfo['filename'] . "_{$w}w.webp";
                    file_put_contents($disk->path($rRelativePath), $rContent);
                }

                $newSize = filesize($webpRealPath);
                $totalWebpSize += $newSize;

                // Save mapping for DB update
                $replacements[$relativePath] = $webpRelativePath;

                $this->info("Skonwertowano: {$relativePath} -> {$webpRelativePath} (" . round($origSize / 1024, 1) . "KB -> " . round($newSize / 1024, 1) . "KB)");
                $convertedCount++;
            } else {
                $this->warn("Nie udało się skonwertować: {$relativePath}");
            }
        }

        if ($this->option('dry-run')) {
            $this->info("Zakończono symulację (dry-run). Zidentyfikowano {$convertedCount} plików do konwersji.");
            return Command::SUCCESS;
        }

        // Update DB References
        $this->info('Aktualizuję odnośniki w bazie danych...');
        $updatedDbCount = 0;

        foreach ($replacements as $oldPath => $newPath) {
            // 1. Products primary_image
            $updatedDbCount += Product::query()
                ->where('primary_image', $oldPath)
                ->update(['primary_image' => $newPath]);

            // 2. Products gallery_images (JSON)
            $products = Product::query()->whereNotNull('gallery_images')->get();
            foreach ($products as $p) {
                $gallery = $p->gallery_images;
                if (is_array($gallery) && in_array($oldPath, $gallery)) {
                    $newGallery = array_map(fn ($img) => $img === $oldPath ? $newPath : $img, $gallery);
                    $p->gallery_images = $newGallery;
                    $p->save();
                    $updatedDbCount++;
                }
            }

            // 3. GalleryArtwork image_path
            $updatedDbCount += GalleryArtwork::query()
                ->where('image_path', $oldPath)
                ->update(['image_path' => $newPath]);

            // 4. Media file_path & file_name
            $oldFilename = basename($oldPath);
            $newFilename = basename($newPath);

            $updatedDbCount += Media::query()
                ->where('file_path', $oldPath)
                ->update([
                    'file_path' => $newPath,
                    'file_name' => $newFilename,
                    'mime_type' => 'image/webp',
                ]);
        }

        // 5. Update StoreSettings branding images if needed
        $setting = StoreSetting::first();
        if ($setting && ! empty($setting->metadata)) {
            $meta = $setting->metadata;
            $changed = false;
            foreach ($replacements as $oldPath => $newPath) {
                foreach (['admin_logo', 'admin_favicon', 'admin_login_bg'] as $key) {
                    if (isset($meta[$key]) && $meta[$key] === $oldPath) {
                        $meta[$key] = $newPath;
                        $changed = true;
                    }
                }
            }
            if ($changed) {
                $setting->metadata = $meta;
                $setting->save();
                $updatedDbCount++;
            }
        }

        // Re-index media library
        $this->call('media:scan');

        $savedKB = round(($totalOriginalSize - $totalWebpSize) / 1024, 1);
        $this->info("Zakończono sukcesem! Skonwertowano {$convertedCount} plików (Zaoszczędzono ok. {$savedKB} KB). Zaktualizowano {$updatedDbCount} wpisów w bazie danych.");

        return Command::SUCCESS;
    }
}
