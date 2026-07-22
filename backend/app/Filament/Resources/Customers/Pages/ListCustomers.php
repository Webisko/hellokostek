<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->slideOver()->modalWidth('7xl'),
            Action::make('exportCustomers')
                ->label('Eksport CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('lime')
                ->url(route('admin.exports.customers'), shouldOpenInNewTab: true),
        ];
    }
}