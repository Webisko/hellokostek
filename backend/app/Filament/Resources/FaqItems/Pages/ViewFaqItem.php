<?php

namespace App\Filament\Resources\FaqItems\Pages;

use App\Filament\Resources\FaqItems\FaqItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFaqItem extends ViewRecord
{
    protected static string $resource = FaqItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}