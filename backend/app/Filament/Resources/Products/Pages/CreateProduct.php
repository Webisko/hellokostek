<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['print_regular_price']) && filled($data['print_regular_price'])) {
            $data['regular_price_amount'] = (int) round(((float) $data['print_regular_price']) * 100);
        } elseif (isset($data['original_regular_price']) && filled($data['original_regular_price'])) {
            $data['regular_price_amount'] = (int) round(((float) $data['original_regular_price']) * 100);
        } else {
            $data['regular_price_amount'] = 0;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        \App\Models\Product::syncVariantsFromData($this->getRecord(), $this->form->getRawState());
    }
}