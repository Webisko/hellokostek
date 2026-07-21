<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\StoreSettings;

class ConfigurationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            if (! $this->app->runningInConsole() || $this->app->runningUnitTests()) {
                $this->overrideConfig();
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }

    private function overrideConfig(): void
    {
        $settings = app(StoreSettings::class);
        $model = $settings->model();
        if (! $model->exists) {
            return;
        }

        // 1. Mail & Safety
        if (filled($settings->mailFromAddress())) {
            config(['mail.from.address' => $settings->mailFromAddress()]);
        }
        if (filled($settings->mailFromName())) {
            config(['mail.from.name' => $settings->mailFromName()]);
        }
        $resendKey = data_get($model->metadata, 'resend_key');
        if (filled($resendKey)) {
            config(['services.resend.key' => $resendKey]);
        }
        $mailRedirect = data_get($model->metadata, 'mail_safety_redirect');
        if (filled($mailRedirect)) {
            config(['services.mail_safety.redirect_all_to' => $mailRedirect]);
        }

        // 2. Security Headers & CSP
        $securityEnabled = data_get($model->metadata, 'security_headers_enabled', true);
        config(['app.add_security_headers' => $securityEnabled]);
        $csp = data_get($model->metadata, 'content_security_policy');
        if (filled($csp)) {
            config(['app.content_security_policy' => $csp]);
        }

        // 3. E-commerce Specific Override
        if ($settings->isEcommerce()) {
            config(['shop.store.name' => $settings->storeName()]);
            config(['shop.store.currency' => $settings->currency()]);
            config(['shop.store.free_shipping_threshold' => $settings->freeShippingThreshold()]);
            config(['shop.wholesale.minimum_regular_price_multiplier' => $settings->wholesaleMinimumRegularPriceMultiplier()]);
            config(['shop.abandoned_cart.enabled' => (bool) data_get($model->metadata, 'abandoned_cart_enabled', true)]);
            config(['shop.abandoned_cart.hours_threshold' => (int) data_get($model->metadata, 'abandoned_cart_hours_threshold', 2)]);

            // Stripe
            config(['services.stripe.enabled' => (bool) data_get($model->metadata, 'stripe_enabled', true)]);
            config(['services.stripe.key' => data_get($model->metadata, 'stripe_key')]);
            config(['services.stripe.secret' => data_get($model->metadata, 'stripe_secret')]);
            config(['services.stripe.webhook_secret' => data_get($model->metadata, 'stripe_webhook_secret')]);

            // Przelewy24
            config(['services.przelewy24.enabled' => (bool) data_get($model->metadata, 'przelewy24_enabled', true)]);
            config(['services.przelewy24.merchant_id' => data_get($model->metadata, 'przelewy24_merchant_id')]);
            config(['services.przelewy24.pos_id' => data_get($model->metadata, 'przelewy24_pos_id')]);
            config(['services.przelewy24.crc' => data_get($model->metadata, 'przelewy24_crc')]);
            config(['services.przelewy24.api_key' => data_get($model->metadata, 'przelewy24_api_key')]);
            config(['services.przelewy24.api_base_url' => data_get($model->metadata, 'przelewy24_api_base_url')]);
            config(['services.przelewy24.redirect_base_url' => data_get($model->metadata, 'przelewy24_redirect_base_url')]);
            config(['services.przelewy24.callback_token' => data_get($model->metadata, 'przelewy24_callback_token')]);

            // InPost
            config(['services.inpost.organization_id' => data_get($model->metadata, 'inpost_organization_id')]);
            config(['services.inpost.token' => data_get($model->metadata, 'inpost_token')]);
            config(['services.inpost.sandbox' => (bool) data_get($model->metadata, 'inpost_sandbox', true)]);
            config(['services.inpost.sender_email' => data_get($model->metadata, 'inpost_sender_email')]);
            config(['services.inpost.sender_phone' => data_get($model->metadata, 'inpost_sender_phone')]);
            config(['services.inpost.sender_name' => data_get($model->metadata, 'inpost_sender_name')]);
            config(['services.inpost.sender_company' => data_get($model->metadata, 'inpost_sender_company')]);
            config(['services.inpost.sender_street' => data_get($model->metadata, 'inpost_sender_street')]);
            config(['services.inpost.sender_building' => data_get($model->metadata, 'inpost_sender_building')]);
            config(['services.inpost.sender_city' => data_get($model->metadata, 'inpost_sender_city')]);
            config(['services.inpost.sender_postcode' => data_get($model->metadata, 'inpost_sender_postcode')]);

            // Orlen Paczka
            config(['services.orlen_paczka.partner_id' => data_get($model->metadata, 'orlen_partner_id')]);
            config(['services.orlen_paczka.partner_key' => data_get($model->metadata, 'orlen_partner_key')]);
            config(['services.orlen_paczka.sandbox' => (bool) data_get($model->metadata, 'orlen_sandbox', true)]);
            config(['services.orlen_paczka.sender_email' => data_get($model->metadata, 'orlen_sender_email')]);
            config(['services.orlen_paczka.sender_phone' => data_get($model->metadata, 'orlen_sender_phone')]);
            config(['services.orlen_paczka.sender_name' => data_get($model->metadata, 'orlen_sender_name')]);
            config(['services.orlen_paczka.sender_company' => data_get($model->metadata, 'orlen_sender_company')]);
            config(['services.orlen_paczka.sender_street' => data_get($model->metadata, 'orlen_sender_street')]);
            config(['services.orlen_paczka.sender_building' => data_get($model->metadata, 'orlen_sender_building')]);
            config(['services.orlen_paczka.sender_city' => data_get($model->metadata, 'orlen_sender_city')]);
            config(['services.orlen_paczka.sender_postcode' => data_get($model->metadata, 'orlen_sender_postcode')]);

            // Accounting Drivers
            $accountingDriver = data_get($model->metadata, 'active_accounting_driver', 'built_in');
            config(['accounting.drivers.built_in.enabled' => $accountingDriver === 'built_in']);
            
            config(['accounting.drivers.fakturownia.enabled' => $accountingDriver === 'fakturownia']);
            config(['accounting.drivers.fakturownia.api_token' => data_get($model->metadata, 'fakturownia_api_token')]);
            config(['accounting.drivers.fakturownia.domain' => data_get($model->metadata, 'fakturownia_domain')]);

            config(['accounting.drivers.ifirma.enabled' => $accountingDriver === 'ifirma']);
            config(['accounting.drivers.ifirma.api_key' => data_get($model->metadata, 'ifirma_api_key')]);
            config(['accounting.drivers.ifirma.username' => data_get($model->metadata, 'ifirma_username')]);

            config(['accounting.drivers.infakt.enabled' => $accountingDriver === 'infakt']);
            config(['accounting.drivers.infakt.api_key' => data_get($model->metadata, 'infakt_api_key')]);

            config(['accounting.drivers.wfirma.enabled' => $accountingDriver === 'wfirma']);
            config(['accounting.drivers.wfirma.api_key' => data_get($model->metadata, 'wfirma_api_key')]);
            config(['accounting.drivers.wfirma.access_key' => data_get($model->metadata, 'wfirma_access_key')]);

            // Google Places API
            config(['services.google_places.api_key' => data_get($model->metadata, 'google_places_api_key')]);
            config(['services.google_places.place_id' => data_get($model->metadata, 'google_places_place_id')]);
            config(['services.google_places.business_name' => data_get($model->metadata, 'google_places_business_name', 'Generic Shop')]);
        }
    }
}