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
    use HasDynamicNavigation {
        shouldRegisterNavigation as protected traitShouldRegisterNavigation;
    }

    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Pliki & media pomocnicze';

    protected static ?string $modelLabel = 'plik pomocniczy';

    protected static ?string $pluralModelLabel = 'pliki pomocnicze';

    protected static ?string $slug = 'multimedia';

    protected static \UnitEnum|string|null $navigationGroup = 'Strona i galeria';

    protected static ?int $navigationSort = 50;

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! auth()->user()?->isAdmin()) {
            return false;
        }

        return static::traitShouldRegisterNavigation();
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
            'create' => Pages\CreateMedia::route('/utworz'),
            'edit' => Pages\EditMedia::route('/{record}/edycja'),
        ];
    }
}
