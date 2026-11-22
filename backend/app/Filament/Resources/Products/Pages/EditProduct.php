<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['print_regular_price']) && filled($data['print_regular_price'])) {
            $data['regular_price_amount'] = (int) round(((float) $data['print_regular_price']) * 100);
        } elseif (isset($data['original_regular_price']) && filled($data['original_regular_price'])) {
            $data['regular_price_amount'] = (int) round(((float) $data['original_regular_price']) * 100);
        }

        if (array_key_exists('print_sale_price', $data)) {
            if (filled($data['print_sale_price'])) {
                $sale = (int) round(((float) $data['print_sale_price']) * 100);
                $regular = $data['regular_price_amount'] ?? $this->getRecord()->regular_price_amount;
                $data['sale_price_amount'] = ($sale > 0 && $sale < $regular) ? $sale : null;
            } else {
                $data['sale_price_amount'] = null;
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        \App\Models\Product::syncVariantsFromData($this->getRecord(), $this->form->getRawState());
    }
}