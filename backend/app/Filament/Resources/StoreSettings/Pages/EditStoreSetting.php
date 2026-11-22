<?php

namespace App\Filament\Resources\StoreSettings\Pages;

use App\Filament\Resources\StoreSettings\StoreSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditStoreSetting extends EditRecord
{
    protected static string $resource = StoreSettingResource::class;

    public function mount(int|string|null $record = null): void
    {
        $record ??= app(\App\Support\StoreSettings::class)->model()->getKey();

        parent::mount($record);
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Ustawienia';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Ustawienia';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Zapisz')
                ->icon('heroicon-o-check')
                ->submit(null)
                ->action(fn () => $this->save()),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Ustawienia zostały zapisane';
    }

    protected function afterSave(): void
    {
        app(\App\Support\StoreSettings::class)->purgeCache();
    }

    protected function getFormActions(): array
    {
        return [];
    }

    private const SECRET_METADATA_KEYS = [
        'inpost_token',
        'orlen_partner_key',
        'przelewy24_api_key',
        'przelewy24_callback_token',
        'przelewy24_crc',
        'resend_key',
        'stripe_secret',
        'stripe_webhook_secret',
    ];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $defaults = [
            'security_headers_enabled' => true,
            'invoicing_enabled' => true,
            'invoice_seller_name' => 'hellokostek Maciej Kosteczka',
            'invoice_seller_nip' => '6252363656',
            'invoice_seller_address' => 'Rynek 33, 42-470 Siewierz',
            'invoice_seller_bank_account' => 'PL 74 1050 1227 1000 0090 8312 8604',
            'invoice_number_prefix' => 'FV/',
            'invoice_payment_days' => 14,
            'bank_transfer_reservation_days' => 3,
            'abandoned_cart_enabled' => true,
            'abandoned_cart_hours_threshold' => 2,
            'stripe_enabled' => true,
            'przelewy24_enabled' => true,
            'inpost_sandbox' => true,
            'orlen_sandbox' => true,
        ];

        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $metadata) || $metadata[$key] === null) {
                $metadata[$key] = $value;
            }
        }

        // Password-revealable inputs hydrate null as '' in Alpine/Livewire, which would otherwise trigger false unsaved alerts.
        foreach (self::SECRET_METADATA_KEYS as $key) {
            if (! array_key_exists($key, $metadata) || $metadata[$key] === null) {
                $metadata[$key] = '';
            }
        }

        $storeSettings = app(\App\Support\StoreSettings::class);
        if (! is_array($metadata['artwork_techniques'] ?? null)) {
            $metadata['artwork_techniques'] = $storeSettings->artworkTechniques();
        } else {
            $metadata['artwork_techniques'] = \App\Support\StoreSettings::normalizeDictionaryList($metadata['artwork_techniques']);
        }
        if (! is_array($metadata['artwork_formats'] ?? null)) {
            $metadata['artwork_formats'] = $storeSettings->artworkFormats();
        } else {
            $metadata['artwork_formats'] = \App\Support\StoreSettings::normalizeDictionaryList($metadata['artwork_formats']);
        }

        $data['metadata'] = $metadata;
        $data['seo'] = is_array($data['seo'] ?? null) ? $data['seo'] : [];
        $data['shipping_zones'] = is_array($data['shipping_zones'] ?? null) ? $data['shipping_zones'] : [];
        $data['shipping_methods'] = is_array($data['shipping_methods'] ?? null) ? $data['shipping_methods'] : [];

        return $data;
    }

    private const MANAGER_ALLOWED_METADATA_KEYS = [
        'artwork_techniques',
        'artwork_formats',
        'invoicing_enabled',
        'invoice_seller_name',
        'invoice_seller_nip',
        'invoice_seller_address',
        'invoice_seller_bank_account',
        'bank_transfer_reservation_days',
        'invoice_number_prefix',
        'invoice_payment_days',
        'abandoned_cart_enabled',
        'abandoned_cart_hours_threshold',
    ];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (is_array($data['metadata'] ?? null)) {
            foreach (self::SECRET_METADATA_KEYS as $key) {
                if (($data['metadata'][$key] ?? null) === '') {
                    $data['metadata'][$key] = null;
                }
            }

            foreach (['artwork_techniques', 'artwork_formats'] as $dictKey) {
                if (array_key_exists($dictKey, $data['metadata']) && is_array($data['metadata'][$dictKey])) {
                    $data['metadata'][$dictKey] = \App\Support\StoreSettings::normalizeDictionaryList($data['metadata'][$dictKey]);
                }
            }
        }

        if (! auth()->user()?->isAdmin()) {
            /** @var \App\Models\StoreSetting $record */
            $record = $this->getRecord();
            $existingMeta = is_array($record->metadata) ? $record->metadata : [];
            $incomingMeta = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];

            foreach (self::MANAGER_ALLOWED_METADATA_KEYS as $key) {
                if (array_key_exists($key, $incomingMeta)) {
                    $existingMeta[$key] = $incomingMeta[$key];
                }
            }
            $data['metadata'] = $existingMeta;

            $existingMethods = array_values(is_array($record->shipping_methods) ? $record->shipping_methods : []);
            $incomingMethods = array_values(is_array($data['shipping_methods'] ?? null) ? $data['shipping_methods'] : []);
            foreach ($existingMethods as $index => $existingMethod) {
                if (isset($incomingMethods[$index]) && is_array($incomingMethods[$index])) {
                    if (isset($incomingMethods[$index]['name'])) {
                        $existingMethods[$index]['name'] = $incomingMethods[$index]['name'];
                    }
                    if (array_key_exists('amount', $incomingMethods[$index]) && $incomingMethods[$index]['amount'] !== null) {
                        $existingMethods[$index]['amount'] = (int) $incomingMethods[$index]['amount'];
                    }
                }
            }
            $data['shipping_methods'] = $existingMethods;

            $protectedTopLevelKeys = [
                'currency',
                'allow_guest_checkout',
                'shipping_zones',
                'google_tag_manager_id',
                'google_analytics_id',
                'facebook_pixel_id',
                'cookie_banner_enabled',
                'cookie_banner_title',
                'cookie_banner_description',
                'seo',
                'custom_head_scripts',
                'global_noindex',
                'mail_from_name',
                'mail_from_address',
            ];
            foreach ($protectedTopLevelKeys as $field) {
                $data[$field] = $record->{$field};
            }
        }

        return $data;
    }

    protected function afterFill(): void
    {
        $this->rememberData();
    }
}
