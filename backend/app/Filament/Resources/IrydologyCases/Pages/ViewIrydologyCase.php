<?php

namespace App\Filament\Resources\IrydologyCases\Pages;

use App\Filament\Resources\IrydologyCases\IrydologyCaseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIrydologyCase extends ViewRecord
{
    protected static string $resource = IrydologyCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}