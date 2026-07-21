<?php

namespace App\Filament\Resources\BackInStockSubscriptions\Pages;

use App\Filament\Resources\BackInStockSubscriptions\BackInStockSubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListBackInStockSubscriptions extends ListRecords
{
    protected static string $resource = BackInStockSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
