<?php

namespace App\Filament\Resources\GalleryArtworks\Pages;

use App\Filament\Resources\GalleryArtworks\GalleryArtworkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Livewire\Attributes\Url;

class ManageGalleryArtworks extends ManageRecords
{
    protected static string $resource = GalleryArtworkResource::class;

    #[Url]
    public ?string $action = null;

    public function mount(): void
    {
        parent::mount();

        if ($this->action === 'create') {
            $this->mountAction('create');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth('7xl'),
        ];
    }
}
