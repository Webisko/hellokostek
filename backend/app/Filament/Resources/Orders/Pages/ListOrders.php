<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

use Livewire\Attributes\Url;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    #[Url]
    public ?string $record = null;

    public function mount(): void
    {
        parent::mount();

        if ($this->record) {
            $this->bootedInteractsWithTable();
            $this->mountTableAction('view', $this->record);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus'),
            Action::make('exportOrders')
                ->label('Eksport CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(route('admin.exports.orders'), shouldOpenInNewTab: true),
        ];
    }
}
