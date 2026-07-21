<?php

namespace App\Providers;

use App\Domain\Communication\MailDeliveryTargetResolver;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Observers\ProductObserver;
use App\Observers\ProductVariantObserver;
use App\Observers\AuditLogObserver;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists(base_path('../public_html'))) {
            $this->app->usePublicPath(base_path('../public_html'));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Automatyczne sprzątanie osieroconego pliku public/hot (problem na Windowsie przy wyłączonym serwerze Vite lub konflikcie projektów)
        if (app()->environment('local') && file_exists(public_path('hot'))) {
            $url = trim(file_get_contents(public_path('hot')));
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'http://' . $url;
            }

            $isViteRunning = cache()->remember('vite_dev_server_status', 10, function () use ($url) {
                $testUrl = rtrim($url, '/') . '/resources/css/filament/admin/theme.css';
                $context = stream_context_create([
                    'http' => [
                        'method' => 'HEAD',
                        'timeout' => 0.05, // 50ms timeout
                        'ignore_errors' => true
                    ]
                ]);
                
                $headers = @get_headers($testUrl, false, $context);
                if ($headers && str_contains($headers[0], '200')) {
                    return true;
                }
                return false;
            });

            if (!$isViteRunning) {
                @unlink(public_path('hot'));
                if (file_exists(public_path('hot.bak'))) {
                    @unlink(public_path('hot.bak'));
                }
            }
        }

        $safetyRecipient = app(MailDeliveryTargetResolver::class)->safetyRecipient();

        if ($safetyRecipient !== null) {
            Mail::alwaysTo($safetyRecipient);
        }

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\OrderPaid::class,
            \App\Listeners\SendOrderToAccounting::class
        );

        Product::observe(ProductObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);

        $auditModels = [
            \App\Models\User::class,
            \App\Models\CustomerProfile::class,
            \App\Models\Product::class,
            \App\Models\Order::class,
            \App\Models\GalleryArtwork::class,
            \App\Models\ContentPage::class,
            \App\Models\Coupon::class,
            \App\Models\OrderReturn::class,
            \App\Models\StoreSetting::class,
        ];

        foreach ($auditModels as $model) {
            $model::observe(AuditLogObserver::class);
        }

        \Filament\Forms\Components\FileUpload::configureUsing(function (\Filament\Forms\Components\FileUpload $component) {
            $component->saveUploadedFileUsing(static function (\Filament\Forms\Components\FileUpload $component, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): ?string {
                $disk = $component->getDisk();
                $directory = $component->getDirectory();

                $settings = app(\App\Support\StoreSettings::class);
                
                $convertWebP = (bool) ($settings->model()->metadata['media_convert_webp'] ?? true);
                $maxWidth = isset($settings->model()->metadata['media_max_width']) ? (int) $settings->model()->metadata['media_max_width'] : 1920;
                $maxHeight = isset($settings->model()->metadata['media_max_height']) ? (int) $settings->model()->metadata['media_max_height'] : 1080;
                $generateResponsive = (bool) ($settings->model()->metadata['media_generate_responsive'] ?? true);
                $responsiveWidthsStr = $settings->model()->metadata['media_responsive_widths'] ?? '360,720,1200';

                $responsiveWidths = [];
                if ($generateResponsive && filled($responsiveWidthsStr)) {
                    $responsiveWidths = array_filter(array_map('intval', explode(',', $responsiveWidthsStr)));
                }

                $mime = $file->getMimeType();
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                $filename = $component->getUploadedFileNameForStorage($file);

                if (! in_array($mime, $allowedMimes)) {
                    return $component->saveUploadedFile($file);
                }

                $tempPath = $file->getRealPath();

                if ($convertWebP) {
                    $filename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
                }

                $optimizedData = \App\Support\ImageOptimizer::process(
                    $tempPath,
                    $convertWebP,
                    $maxWidth ?: null,
                    $maxHeight ?: null,
                    $responsiveWidths
                );

                if (! $optimizedData) {
                    return $component->saveUploadedFile($file);
                }

                $mainPath = trim($directory . '/' . $filename, '/');
                $disk->put($mainPath, $optimizedData['main_content']);

                if ($component->getVisibility() === 'public') {
                    rescue(fn () => $disk->setVisibility($mainPath, 'public'), report: false);
                }

                foreach ($optimizedData['responsive_contents'] as $width => $content) {
                    $responsiveFilename = pathinfo($filename, PATHINFO_FILENAME) . '-' . $width . 'w.' . pathinfo($filename, PATHINFO_EXTENSION);
                    $responsivePath = trim($directory . '/' . $responsiveFilename, '/');
                    $disk->put($responsivePath, $content);

                    if ($component->getVisibility() === 'public') {
                        rescue(fn () => $disk->setVisibility($responsivePath, 'public'), report: false);
                    }
                }

                return $mainPath;
            });
        });

        // Globalne śledzenie zasobów przez Livewire SPA dla tagów Vite
        \Illuminate\Support\Facades\Vite::useStyleTagAttributes(['data-navigate-track' => 'reload']);
        \Illuminate\Support\Facades\Vite::useScriptTagAttributes(['data-navigate-track' => 'reload']);
    }
}
