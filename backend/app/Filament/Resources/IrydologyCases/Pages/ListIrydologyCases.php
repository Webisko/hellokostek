<?php

namespace App\Filament\Resources\IrydologyCases\Pages;

use App\Filament\Resources\IrydologyCases\IrydologyCaseResource;
use Filament\Resources\Pages\ListRecords;

class ListIrydologyCases extends ListRecords
{
    protected static string $resource = IrydologyCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}