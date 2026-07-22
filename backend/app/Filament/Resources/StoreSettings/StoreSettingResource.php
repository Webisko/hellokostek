<?php

namespace App\Filament\Resources\StoreSettings;

use App\Filament\Resources\StoreSettings\Pages\CreateStoreSetting;
use App\Filament\Resources\StoreSettings\Pages\EditStoreSetting;
use App\Filament\Resources\StoreSettings\Pages\ListStoreSettings;
use App\Filament\Resources\StoreSettings\Pages\ViewStoreSetting;
use App\Filament\Resources\StoreSettings\Schemas\StoreSettingForm;
use App\Filament\Resources\StoreSettings\Schemas\StoreSettingInfolist;
use App\Filament\Resources\StoreSettings\Tables\StoreSettingsTable;
use App\Models\StoreSetting;
use App\Traits\HasDynamicNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StoreSettingResource extends Resource
{
    use HasDynamicNavigation;
    protected static ?string $slug = 'ustawienia';
    public static function getModelLabel(): string
    {
        return 'ustawienia';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ustawienia';
    }

    public static function getNavigationLabel(): string
    {
        return 'Ustawienia';
    }

    protected static ?string $model = StoreSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    

    

    

    protected static \UnitEnum|string|null $navigationGroup = 'Analityka & system';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return StoreSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StoreSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreSettingsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return StoreSetting::query()->count() === 0;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStoreSettings::route('/'),
            'edit' => EditStoreSetting::route('/{record}/edit'),
        ];
    }
}