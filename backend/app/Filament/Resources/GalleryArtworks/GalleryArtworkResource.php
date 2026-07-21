<?php

namespace App\Filament\Resources\GalleryArtworks;

use App\Traits\HasDynamicNavigation;
use App\Filament\Resources\GalleryArtworks\Pages\ManageGalleryArtworks;
use App\Filament\Resources\GalleryArtworks\Schemas\GalleryArtworkForm;
use App\Filament\Resources\GalleryArtworks\Tables\GalleryArtworkTable;
use App\Models\GalleryArtwork;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GalleryArtworkResource extends Resource
{
    use HasDynamicNavigation;

    protected static ?string $model = GalleryArtwork::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $modelLabel = 'dzieło sztuki';

    protected static ?string $pluralModelLabel = 'galeria';

    protected static ?string $navigationLabel = 'Galeria';

    protected static ?string $slug = 'galeria';

    protected static \UnitEnum|string|null $navigationGroup = 'Oferta & Galeria';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return GalleryArtworkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GalleryArtworkTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGalleryArtworks::route('/'),
        ];
    }
}
