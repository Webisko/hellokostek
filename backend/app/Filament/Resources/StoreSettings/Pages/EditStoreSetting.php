<?php

namespace App\Filament\Resources\StoreSettings\Pages;

use App\Filament\Resources\StoreSettings\StoreSettingResource;
use Filament\Actions\ViewAction;
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
            $this->getCancelFormAction()
                ->label('Anuluj'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $defaults = [
            'security_headers_enabled' => true,
            'invoicing_enabled' => true,
            'invoice_seller_name' => 'hellokostek Maciej Kosteczka',
            'invoice_seller_nip' => '6252363656',
            'invoice_seller_address' => 'Rynek 33, 42-470 Siewierz',
            'invoice_number_prefix' => 'FV/',
            'invoice_payment_days' => 14,
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

        $data['metadata'] = $metadata;

        return $data;
    }
}