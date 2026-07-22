<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;

class StoreSettingsController extends Controller
{
    public function __construct(
        private readonly StoreSettings $storeSettings,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'store_name' => $this->storeSettings->storeName(),
            'currency' => $this->storeSettings->currency(),
            'free_shipping_threshold' => $this->storeSettings->freeShippingThreshold(),
            'allow_guest_checkout' => $this->storeSettings->allowGuestCheckout(),
            'cookie_banner_enabled' => $this->storeSettings->cookieBannerEnabled(),
            'google_tag_manager_id' => $this->storeSettings->googleTagManagerId(),
            'google_analytics_id' => $this->storeSettings->googleAnalyticsId(),
            'facebook_pixel_id' => $this->storeSettings->facebookPixelId(),
            'cookie_banner_title' => $this->storeSettings->cookieBannerTitle(),
            'cookie_banner_description' => $this->storeSettings->cookieBannerDescription(),
            'custom_head_scripts' => $this->storeSettings->customHeadScripts(),
            'global_noindex' => $this->storeSettings->globalNoindex(),
            'maintenance_mode_enabled' => $this->storeSettings->maintenanceModeEnabled(),
            'maintenance_mode_message' => $this->storeSettings->maintenanceModeMessage(),
        ]);
    }
}
