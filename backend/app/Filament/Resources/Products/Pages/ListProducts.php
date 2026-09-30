<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->slideOver()
                ->modalWidth('7xl')
                ->mutateFormDataUsing(function (array $data): array {
                    if (isset($data['print_regular_price']) && filled($data['print_regular_price'])) {
                        $data['regular_price_amount'] = (int) round(((float) $data['print_regular_price']) * 100);
                    } elseif (isset($data['original_regular_price']) && filled($data['original_regular_price'])) {
                        $data['regular_price_amount'] = (int) round(((float) $data['original_regular_price']) * 100);
                    } else {
                        $data['regular_price_amount'] = 0;
                    }
                    return $data;
                })
                ->after(function (\App\Models\Product $record, array $data): void {
                    \App\Models\Product::syncVariantsFromData($record, $data);
                }),
        ];
    }
}