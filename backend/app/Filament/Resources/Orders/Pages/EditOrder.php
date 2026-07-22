<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Admin\AdminActivityLogger;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Carbon\CarbonInterface;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Zapisz')
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label('Anuluj'),
            ViewAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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