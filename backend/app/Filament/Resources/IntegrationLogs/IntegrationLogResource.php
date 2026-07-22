<?php

namespace App\Filament\Resources\IntegrationLogs;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\IntegrationLogs\Pages\ListIntegrationLogs;
use App\Filament\Resources\IntegrationLogs\Pages\ViewIntegrationLog;
use App\Filament\Resources\IntegrationLogs\Schemas\IntegrationLogInfolist;
use App\Filament\Resources\IntegrationLogs\Tables\IntegrationLogsTable;
use App\Models\IntegrationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationLogResource extends Resource
{
    protected static ?string $slug = 'dziennik-integracji';
    use HasDynamicNavigation;
    protected static ?string $model = IntegrationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $modelLabel = 'log integracji';

    protected static ?string $pluralModelLabel = 'integracje i webhooki';

    protected static ?string $navigationLabel = 'Integracje i webhooki';

    protected static \UnitEnum|string|null $navigationGroup = 'System & ustawienia';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return IntegrationLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegrationLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIntegrationLogs::route('/'),
        ];
    }
}
