<?php

namespace App\Filament\Resources\ProductReviews\Pages;

use App\Filament\Resources\ProductReviews\ProductReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

use Livewire\Attributes\Url;

class ListProductReviews extends ListRecords
{
    protected static string $resource = ProductReviewResource::class;

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
            CreateAction::make()->icon('heroicon-o-plus')->slideOver(),
        ];
    }
}
