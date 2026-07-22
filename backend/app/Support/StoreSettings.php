<?php

namespace App\Support;

use App\Models\StoreSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class StoreSettings
{
    private ?StoreSetting $settings = null;

    public function model(): StoreSetting
    {
        if ($this->settings instanceof StoreSetting) {
            return $this->settings;
        }

        try {
            $this->settings = Cache::remember(
                'store_settings_active_model',
                3600,
                fn (): StoreSetting => StoreSetting::query()->first() ?? StoreSetting::query()->create($this->defaults())
            );
        } catch (\Throwable $e) {
            return new StoreSetting($this->defaults());
        }

        return $this->settings ?? new StoreSetting($this->defaults());
    }

    public function purgeCache(): void
    {
        $this->settings = null;
        Cache::forget('store_settings_active_model');
    }

    public function exchangeRates(): array
    {
        return $this->model()->exchange_rates ?? [];
    }

    public function storeName(): string
    {
        return (string) $this->model()->store_name;
    }

    public function currency(): string
    {
        return (string) $this->model()->currency;
    }

    public function freeShippingThreshold(): int
    {
        return (int) ($this->model()->free_shipping_threshold ?? PHP_INT_MAX);
    }

    public function wholesaleMinimumRegularPriceMultiplier(): float
    {
        return (float) ($this->model()->wholesale_minimum_regular_price_multiplier ?? 0.0);
    }

    public function allowGuestCheckout(): bool
    {
        return (bool) ($this->model()->allow_guest_checkout ?? true);
    }

    public function codOnlyMethod(): ?string
    {
        return $this->model()->cod_only_method ?? null;
    }

    public function shippingMethods(): array
    {
        $methods = $this->model()->shipping_methods ?? [];

        return collect($methods)
            ->filter(fn ($method): bool => filled(Arr::get($method, 'code')))
            ->mapWithKeys(fn (array $method): array => [Arr::get($method, 'code') => [
                'name' => Arr::get($method, 'name'),
                'amount' => (int) Arr::get($method, 'amount', 0),
                'supports_cod' => (bool) Arr::get($method, 'supports_cod', false),
                'requires_delivery_point' => (bool) Arr::get($method, 'requires_delivery_point', false),
            ]])
            ->all();
    }

    public function shippingMethod(?string $code): ?array
    {
        if (! filled($code)) {
            return null;
        }

        return $this->shippingMethods()[$code] ?? null;
    }

    public function shippingZones(): array
    {
        return $this->model()->shipping_zones ?? [];
    }

    public function shippingMethodForCountry(?string $code, ?string $countryCode, float $cartWeight = 0.0, int $cartValue = 0): ?array
    {
        $method = $this->shippingMethod($code);
        if (! is_array($method)) {
            return null;
        }

        $zones = $this->shippingZones();
        $country = strtoupper(trim($countryCode ?? 'PL'));

        // Find which zone the country belongs to
        $matchedZone = null;
        if (! empty($zones)) {
            foreach ($zones as $zone) {
                $countriesInput = $zone['countries'] ?? [];
                $zoneCountries = is_array($countriesInput) ? $countriesInput : array_map('trim', explode(',', $countriesInput));
                $zoneCountries = array_map('strtoupper', $zoneCountries);
                if (in_array($country, $zoneCountries, true)) {
                    $matchedZone = $zone;
                    break;
                }
            }
        } else {
            // If no zones configured, default to only Poland allowed
            if ($country === 'PL' || $country === 'POLSKA' || $country === 'POL') {
                $matchedZone = ['code' => 'PL', 'name' => 'Polska'];
            }
        }

        if (! $matchedZone) {
            return null;
        }

        // Retrieve raw method from DB record
        $rawMethods = $this->model()->shipping_methods ?? [];
        $rawMethod = collect($rawMethods)->first(fn ($m) => ($m['code'] ?? null) === $code);
        if (! $rawMethod) {
            return null;
        }

        // 1. Check if dynamic rates are configured
        $rates = $rawMethod['rates'] ?? [];
        if (! empty($rates)) {
            // Find a matching rate in the matched zone
            $matchedRate = collect($rates)->first(function ($rate) use ($matchedZone, $cartWeight, $cartValue) {
                if (($rate['zone_code'] ?? null) !== $matchedZone['code']) {
                    return false;
                }

                // Verify weight range
                $minWeight = (float) ($rate['min_weight'] ?? 0.0);
                $maxWeight = isset($rate['max_weight']) && $rate['max_weight'] !== '' ? (float) $rate['max_weight'] : null;
                if ($cartWeight < $minWeight) {
                    return false;
                }
                if ($maxWeight !== null && $cartWeight > $maxWeight) {
                    return false;
                }

                // Verify value range
                $minValue = (int) ($rate['min_value'] ?? 0);
                $maxValue = isset($rate['max_value']) && $rate['max_value'] !== '' ? (int) $rate['max_value'] : null;
                if ($cartValue < $minValue) {
                    return false;
                }
                if ($maxValue !== null && $cartValue > $maxValue) {
                    return false;
                }

                return true;
            });

            if ($matchedRate) {
                $method['amount'] = (bool) ($matchedRate['free_shipping'] ?? false) ? 0 : (int) ($matchedRate['amount'] ?? 0);
                return $method;
            }

            // If rates are defined but none matched, the method is unavailable
            return null;
        }

        // 2. Fallback to backward-compatible zone_prices
        $zonePrices = $rawMethod['zone_prices'] ?? [];
        $matchedPrice = collect($zonePrices)->first(fn ($zp) => ($zp['zone_code'] ?? null) === $matchedZone['code']);

        if (! $matchedPrice) {
            if (isset($rawMethod['amount'])) {
                $method['amount'] = (int) $rawMethod['amount'];
                return $method;
            }
            return null;
        }

        // Return the method with override price
        $method['amount'] = (int) ($matchedPrice['amount'] ?? 0);
        return $method;
    }

    public function integrations(): array
    {
        return $this->model()->integrations ?? [];
    }

    public function seo(): array
    {
        return $this->model()->seo ?? [];
    }

    public function supportEmail(): ?string
    {
        return $this->model()->support_email;
    }

    public function bdoNumber(): ?string
    {
        return data_get($this->model()->metadata, 'bdo_number');
    }

    public function termsVersion(): string
    {
        return (string) data_get($this->model()->metadata, 'terms_version', 'default');
    }

    public function adminNotificationEmail(): ?string
    {
        return $this->model()->admin_notification_email;
    }

    public function orderNotificationEmail(): ?string
    {
        return $this->model()->order_notification_email;
    }

    public function mailFromName(): ?string
    {
        return $this->model()->mail_from_name;
    }

    public function mailFromAddress(): ?string
    {
        return $this->model()->mail_from_address;
    }

    public function productReviewsEnabled(): bool
    {
        return (bool) ($this->model()->product_reviews_enabled ?? true);
    }

    public function generalReviewsEnabled(): bool
    {
        return (bool) ($this->model()->general_reviews_enabled ?? true);
    }

    public function generalReviewsSource(): string
    {
        return (string) ($this->model()->general_reviews_source ?? 'both');
    }

    public function cookieBannerEnabled(): bool
    {
        return (bool) ($this->model()->cookie_banner_enabled ?? false);
    }

    public function googleTagManagerId(): ?string
    {
        return $this->model()->google_tag_manager_id;
    }

    public function googleAnalyticsId(): ?string
    {
        return $this->model()->google_analytics_id;
    }

    public function facebookPixelId(): ?string
    {
        return $this->model()->facebook_pixel_id;
    }

    public function cookieBannerTitle(): ?string
    {
        return $this->model()->cookie_banner_title;
    }

    public function cookieBannerDescription(): ?string
    {
        return $this->model()->cookie_banner_description;
    }

    public function customHeadScripts(): ?string
    {
        return $this->model()->custom_head_scripts;
    }

    public function announcementEnabled(): bool
    {
        return (bool) ($this->model()->announcement_enabled ?? false);
    }

    public function announcementText(): ?string
    {
        return $this->model()->announcement_text;
    }

    public function globalNoindex(): bool
    {
        return (bool) ($this->model()->global_noindex ?? false);
    }

    public function maintenanceModeEnabled(): bool
    {
        return (bool) ($this->model()->maintenance_mode_enabled ?? false);
    }

    public function maintenanceModeAllowedIps(): array
    {
        $ips = $this->model()->maintenance_mode_allowed_ips;
        if (! filled($ips)) {
            return [];
        }
        return array_map('trim', explode(',', $ips));
    }

    public function maintenanceModeMessage(): ?string
    {
        return $this->model()->maintenance_mode_message;
    }

    public function adminBrandName(): string
    {
        return (string) data_get($this->model()->metadata, 'admin_brand_name', 'hellokostek CMS');
    }

    public function adminLogoUrl(): ?string
    {
        $path = data_get($this->model()->metadata, 'admin_logo_path');
        return $path ? \Illuminate\Support\Facades\Storage::url($path) : null;
    }

    public function adminFaviconUrl(): ?string
    {
        $path = data_get($this->model()->metadata, 'admin_favicon_path');
        return $path ? \Illuminate\Support\Facades\Storage::url($path) : null;
    }

    public function adminLoginBackgroundUrl(): ?string
    {
        $path = data_get($this->model()->metadata, 'admin_login_background_path');
        return $path ? \Illuminate\Support\Facades\Storage::url($path) : null;
    }

    public function invoicingEnabled(): bool
    {
        return (bool) data_get($this->model()->metadata, 'invoicing_enabled', false);
    }

    public function invoiceNumberPrefix(): string
    {
        return (string) data_get($this->model()->metadata, 'invoice_number_prefix', 'FV/');
    }

    public function invoiceSellerName(): string
    {
        return (string) data_get($this->model()->metadata, 'invoice_seller_name', 'Firma testowa sp. z o.o.');
    }

    public function invoiceSellerAddress(): string
    {
        return (string) data_get($this->model()->metadata, 'invoice_seller_address', 'ul. Testowa 1, 00-001 Warszawa');
    }

    public function invoiceSellerNip(): string
    {
        return (string) data_get($this->model()->metadata, 'invoice_seller_nip', '1234567890');
    }

    public function invoiceSellerBankAccount(): string
    {
        return (string) data_get($this->model()->metadata, 'invoice_seller_bank_account', '');
    }

    public function invoicePaymentDays(): int
    {
        return (int) data_get($this->model()->metadata, 'invoice_payment_days', 14);
    }

    public function abandonedCartRecoveryEnabled(): bool
    {
        return (bool) data_get($this->model()->metadata, 'abandoned_cart_recovery_enabled', false);
    }

    public function abandonedCartRecoveryDelayHours(): int
    {
        return (int) data_get($this->model()->metadata, 'abandoned_cart_recovery_delay_hours', 2);
    }

    public function abandonedCartRecoveryDiscountEnabled(): bool
    {
        return (bool) data_get($this->model()->metadata, 'abandoned_cart_recovery_discount_enabled', false);
    }

    public function abandonedCartRecoveryDiscountPercentage(): int
    {
        return (int) data_get($this->model()->metadata, 'abandoned_cart_recovery_discount_percentage', 10);
    }

    public function abandonedCartRecoveryDiscountDurationDays(): int
    {
        return (int) data_get($this->model()->metadata, 'abandoned_cart_recovery_discount_duration_days', 3);
    }

    public function abandonedCartRecoveryUrl(): string
    {
        return (string) data_get($this->model()->metadata, 'abandoned_cart_recovery_url', config('app.url') . '/checkout?resume_draft={number}');
    }

    public function navigationGroups(): array
    {
        $groups = data_get($this->model()->metadata, 'navigation_groups');
        if (! is_array($groups) || empty($groups)) {
            return [
                'Sprzedaż & zapytania',
                'Produkty & sklep',
                'Strona & wygląd',
                'Analityka & system',
            ];
        }

        return collect($groups)
            ->sortBy('sort_order')
            ->pluck('name')
            ->map(fn ($name) => match ($name) {
                'Sprzedaż & Zapytania' => 'Sprzedaż & zapytania',
                'Oferta & galeria', 'Oferta & Galeria' => 'Produkty & sklep',
                'System & ustawienia', 'System & Ustawienia' => 'Analityka & system',
                default => $name,
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'store_name' => (string) config('shop.store.name', 'Generic Shop'),
            'currency' => (string) config('shop.store.currency', 'PLN'),
            'free_shipping_threshold' => (int) config('shop.store.free_shipping_threshold', 25000),
            'wholesale_minimum_regular_price_multiplier' => (float) config('shop.wholesale.minimum_regular_price_multiplier', 0.70),
            'allow_guest_checkout' => true,
            'cod_only_method' => config('shop.shipping.cod_only_method'),
            'support_email' => config('mail.from.address'),
            'admin_notification_email' => config('mail.from.address'),
            'order_notification_email' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
            'mail_from_address' => config('mail.from.address'),
            'shipping_methods' => collect((array) config('shop.shipping.methods', []))
                ->map(fn (array $method, string $code): array => [
                    'code' => $code,
                    'name' => $method['name'] ?? $code,
                    'amount' => (int) ($method['amount'] ?? 0),
                    'supports_cod' => (bool) ($method['supports_cod'] ?? false),
                    'requires_delivery_point' => (bool) ($method['requires_delivery_point'] ?? false),
                    'zone_prices' => [],
                ])
                ->values()
                ->all(),
            'shipping_zones' => [],
            'integrations' => (array) config('shop.integrations', []),
            'seo' => [
                'default_title' => (string) config('app.name', 'Generic Shop'),
                'default_description' => null,
            ],
            'product_reviews_enabled' => true,
            'general_reviews_enabled' => true,
            'general_reviews_source' => 'both',
            'cookie_banner_enabled' => false,
            'google_tag_manager_id' => null,
            'google_analytics_id' => null,
            'facebook_pixel_id' => null,
            'cookie_banner_title' => 'Szanujemy Twoją prywatność',
            'custom_head_scripts' => null,
            'announcement_enabled' => false,
            'announcement_text' => null,
            'global_noindex' => false,
            'maintenance_mode_enabled' => false,
            'maintenance_mode_allowed_ips' => null,
            'maintenance_mode_message' => 'Strona w budowie. Zapraszamy wkrótce!',
            'metadata' => [
                'media_convert_webp' => true,
                'media_max_width' => 1920,
                'media_max_height' => 1080,
                'media_generate_responsive' => true,
                'media_responsive_widths' => '360,720,1200',
                'admin_brand_name' => 'Hello Kostek CMS',
                'navigation_groups' => [
                    ['name' => 'Sprzedaż & zapytania', 'label' => 'Sprzedaż & zapytania', 'sort_order' => 10],
                    ['name' => 'Oferta & galeria', 'label' => 'Oferta & galeria', 'sort_order' => 20],
                    ['name' => 'System & ustawienia', 'label' => 'System & ustawienia', 'sort_order' => 30],
                ],
            ],
        ];
    }

    public function isLms(): bool
    {
        return class_exists(\App\Models\Course::class);
    }

    public function isEcommerce(): bool
    {
        return class_exists(\App\Models\Product::class);
    }

    public function adminPath(): string
    {
        $path = trim((string) data_get($this->model()->metadata, 'admin_path', env('FILAMENT_PATH', 'admin')));

        return $path !== '' ? $path : (string) env('FILAMENT_PATH', 'admin');
    }

    public function abandonedCartEnabled(): bool
    {
        return (bool) data_get($this->model()->metadata, 'abandoned_cart_enabled', config('shop.abandoned_cart.enabled', true));
    }

    public function abandonedCartHoursThreshold(): int
    {
        return (int) data_get($this->model()->metadata, 'abandoned_cart_hours_threshold', config('shop.abandoned_cart.hours_threshold', 2));
    }

    public function resourceNavigationLabel(string $resource, string $default): string
    {
        $config = $this->resourceNavigationConfig($resource);
        return (string) data_get($config, 'label', $default);
    }

    public function resourceNavigationGroup(string $resource, ?string $default): ?string
    {
        if ($resource === 'MediaResource') {
            return 'Strona & wygląd';
        }
        $config = $this->resourceNavigationConfig($resource);
        $groupKey = data_get($config, 'group', $default);
        if ($groupKey) {
            $groupKey = match ($groupKey) {
                'Sprzedaż & Zapytania' => 'Sprzedaż & zapytania',
                'Oferta & galeria', 'Oferta & Galeria' => 'Produkty & sklep',
                'System & ustawienia', 'System & Ustawienia' => 'Analityka & system',
                default => $groupKey,
            };
        }
        return $groupKey ? $this->navigationGroupLabel($groupKey) : null;
    }

    public function resourceNavigationSort(string $resource, ?int $default): ?int
    {
        if ($resource === 'MediaResource') {
            return 20;
        }
        $config = $this->resourceNavigationConfig($resource);
        return data_get($config, 'sort_order') !== null ? (int) data_get($config, 'sort_order') : $default;
    }

    public function resourceNavigationVisible(string $resource, bool $default): bool
    {
        if ($resource === 'MediaResource' || $resource === 'FaqItemResource') {
            return true;
        }
        $config = $this->resourceNavigationConfig($resource);
        return data_get($config, 'visible') !== null ? (bool) data_get($config, 'visible') : $default;
    }

    private function resourceNavigationConfig(string $resource): ?array
    {
        $resources = data_get($this->model()->metadata, 'resources_navigation', []);
        if (empty($resources)) {
            $resources = $this->defaultResourcesNavigation();
        }
        return collect($resources)->first(fn ($item) => data_get($item, 'resource') === $resource);
    }

    public function navigationGroupLabel(string $groupKey): string
    {
        $groups = data_get($this->model()->metadata, 'navigation_groups', []);
        $matched = collect($groups)->first(fn ($item) => data_get($item, 'name') === $groupKey);
        return (string) data_get($matched, 'label', $groupKey);
    }

    public function defaultResourcesNavigation(): array
    {
        return [
            // Sprzedaż & zapytania
            ['resource' => 'OrderResource', 'label' => 'Zamówienia', 'group' => 'Sprzedaż & zapytania', 'sort_order' => 10, 'visible' => true],
            ['resource' => 'ContactInquiryResource', 'label' => 'Zapytania', 'group' => 'Sprzedaż & zapytania', 'sort_order' => 20, 'visible' => true],
            ['resource' => 'InvoiceResource', 'label' => 'Faktury', 'group' => 'Sprzedaż & zapytania', 'sort_order' => 30, 'visible' => true],
            ['resource' => 'OrderReturnResource', 'label' => 'Zwroty', 'group' => 'Sprzedaż & zapytania', 'sort_order' => 40, 'visible' => true],
            ['resource' => 'CouponResource', 'label' => 'Kupony rabatowe', 'group' => 'Sprzedaż & zapytania', 'sort_order' => 50, 'visible' => true],

            // Produkty & sklep
            ['resource' => 'ProductResource', 'label' => 'Produkty', 'group' => 'Produkty & sklep', 'sort_order' => 10, 'visible' => true],
            ['resource' => 'ProductCategoryResource', 'label' => 'Kategorie', 'group' => 'Produkty & sklep', 'sort_order' => 20, 'visible' => true],
            ['resource' => 'ProductReviewResource', 'label' => 'Opinie', 'group' => 'Produkty & sklep', 'sort_order' => 30, 'visible' => true],

            // Strona & wygląd
            ['resource' => 'GalleryArtworkResource', 'label' => 'Galeria', 'group' => 'Strona & wygląd', 'sort_order' => 10, 'visible' => true],
            ['resource' => 'MediaResource', 'label' => 'Multimedia', 'group' => 'Strona & wygląd', 'sort_order' => 20, 'visible' => true],
            ['resource' => 'ContentPageResource', 'label' => 'Strony', 'group' => 'Strona & wygląd', 'sort_order' => 30, 'visible' => true],
            ['resource' => 'FaqItemResource', 'label' => 'FAQ', 'group' => 'Strona & wygląd', 'sort_order' => 40, 'visible' => true],

            // Analityka & system
            ['resource' => 'StoreSettingResource', 'label' => 'Ustawienia', 'group' => 'Analityka & system', 'sort_order' => 20, 'visible' => true],
            ['resource' => 'UserResource', 'label' => 'Użytkownicy', 'group' => 'Analityka & system', 'sort_order' => 30, 'visible' => true],

            // Wyłączone z nawigacji
            ['resource' => 'CustomerResource', 'label' => 'Klienci', 'group' => 'Sprzedaż & zapytania', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'ProductAttributeResource', 'label' => 'Atrybuty produktów', 'group' => 'Produkty & sklep', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'BackInStockSubscriptionResource', 'label' => 'Powiadomienia o dostępności', 'group' => 'Produkty & sklep', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'AbandonedCartResource', 'label' => 'Porzucone koszyki', 'group' => 'Sprzedaż & zapytania', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'EmailTemplateResource', 'label' => 'Szablony wiadomości', 'group' => 'Analityka & system', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'AdminActivityLogResource', 'label' => 'Dziennik zmian', 'group' => 'Analityka & system', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'IntegrationLogResource', 'label' => 'Integracje i webhooki', 'group' => 'Analityka & system', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'TransactionalEmailLogResource', 'label' => 'Wysłane e-maile', 'group' => 'Analityka & system', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'CookieConsentResource', 'label' => 'Zgody na cookies', 'group' => 'Analityka & system', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'RedirectRuleResource', 'label' => 'Przekierowania', 'group' => 'Analityka & system', 'sort_order' => 100, 'visible' => false],
            ['resource' => 'FailedJobResource', 'label' => 'Nieudane zadania', 'group' => 'Analityka & system', 'sort_order' => 100, 'visible' => false],
        ];
    }
}