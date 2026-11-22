<?php

namespace App\Filament\Resources\StoreSettings\Pages;

use App\Filament\Pages\StoreDashboard;
use App\Filament\Resources\StoreSettings\StoreSettingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditStoreSetting extends EditRecord
{
    protected static string $resource = StoreSettingResource::class;

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
                ->icon('heroicon-o-check'),
            Action::make('backToDashboard')
                ->label('Wróć do kokpitu')
                ->color('gray')
                ->url(StoreDashboard::getUrl()),
        ];
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

        $data['metadata'] = $metadata;
        $data['seo'] = is_array($data['seo'] ?? null) ? $data['seo'] : [];
        $data['shipping_zones'] = is_array($data['shipping_zones'] ?? null) ? $data['shipping_zones'] : [];
        $data['shipping_methods'] = is_array($data['shipping_methods'] ?? null) ? $data['shipping_methods'] : [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (is_array($data['metadata'] ?? null)) {
            foreach (self::SECRET_METADATA_KEYS as $key) {
                if (($data['metadata'][$key] ?? null) === '') {
                    $data['metadata'][$key] = null;
                }
            }
        }

        return $data;
    }

    protected function afterFill(): void
    {
        $this->rememberData();
    }
}
