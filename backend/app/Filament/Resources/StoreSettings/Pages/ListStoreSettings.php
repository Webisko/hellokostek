<?php

namespace App\Filament\Resources\StoreSettings\Pages;

use App\Filament\Resources\StoreSettings\StoreSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreSettings extends ListRecords
{
    protected static string $resource = StoreSettingResource::class;

    public function mount(): void
    {
        parent::mount();

        $record = \App\Models\StoreSetting::firstOrCreate();

        $this->redirect(StoreSettingResource::getUrl('edit', ['record' => $record]));
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => StoreSettingResource::canCreate())
                ->slideOver()
                ->modalWidth('7xl'),
        ];
    }
}