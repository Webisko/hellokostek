<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generowanie numeru zamówienia
        $data['number'] = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $data['currency'] = $data['currency'] ?? 'PLN';

        // Wyliczenie kwot
        $subtotal = 0;
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $subtotal += ($item['total_amount'] ?? 0);
            }
        }
        $data['subtotal_amount'] = $subtotal;
        $data['total_amount'] = $subtotal + ($data['shipping_amount'] ?? 0) - ($data['discount_amount'] ?? 0);
        $data['tax_amount'] = (int) round($data['total_amount'] * 0.23 / 1.23);

        return $data;
    }
}
