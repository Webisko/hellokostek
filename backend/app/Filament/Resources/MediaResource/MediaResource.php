<?php

namespace App\Filament\Resources\MediaResource;

use App\Filament\Resources\MediaResource\Pages;
use App\Filament\Resources\MediaResource\Schemas\MediaForm;
use App\Filament\Resources\MediaResource\Tables\MediaTable;
use App\Models\Media;
use App\Traits\HasDynamicNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MediaResource extends Resource
{
    use HasDynamicNavigation;

    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Pliki & media pomocnicze';

    protected static ?string $modelLabel = 'plik pomocniczy';

    protected static ?string $pluralModelLabel = 'pliki pomocnicze';

    protected static \UnitEnum|string|null $navigationGroup = 'Strona & wygląd';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return MediaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}
