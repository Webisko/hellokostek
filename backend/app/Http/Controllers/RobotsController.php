<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __construct(
        private readonly \App\Support\StoreSettings $storeSettings,
    ) {
    }

    public function __invoke(): Response
    {
        $customRobotsTxt = data_get($this->storeSettings->model()->metadata, 'robots_txt');
        if (filled($customRobotsTxt)) {
            return response($customRobotsTxt, 200)
                ->header('Content-Type', 'text/plain');
        }

        if ($this->storeSettings->globalNoindex()) {
            $lines = [
                'User-agent: *',
                'Disallow: /',
            ];
        } else {
            $baseUrl = rtrim((string) config('services.storefront.url', config('app.url')), '/');
            $adminPath = env('FILAMENT_PATH', 'admin');

            $lines = [
                'User-agent: *',
                "Disallow: /{$adminPath}",
                'Disallow: /api/checkout',
                'Disallow: /api/cart',
                'Disallow: /checkout',
                'Disallow: /cart',
                'Disallow: /*?*sort=',
                'Disallow: /*?*price_min=',
                'Disallow: /*?*price_max=',
                'Disallow: /*?*query=',
                'Disallow: /*?*category=',
                'Disallow: /*?*utm_',
                '',
                "Sitemap: {$baseUrl}/sitemap.xml",
            ];
        }

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain');
    }
}
