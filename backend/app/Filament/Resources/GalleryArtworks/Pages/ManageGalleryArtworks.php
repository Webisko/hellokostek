<?php

namespace App\Filament\Resources\GalleryArtworks\Pages;

use App\Filament\Resources\GalleryArtworks\GalleryArtworkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGalleryArtworks extends ManageRecords
{
    protected static string $resource = GalleryArtworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth('3xl'),
        ];
    }
}
