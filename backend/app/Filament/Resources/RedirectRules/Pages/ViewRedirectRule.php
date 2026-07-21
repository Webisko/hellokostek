<?php

namespace App\Filament\Resources\RedirectRules\Pages;

use App\Filament\Resources\RedirectRules\RedirectRuleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRedirectRule extends ViewRecord
{
    protected static string $resource = RedirectRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}