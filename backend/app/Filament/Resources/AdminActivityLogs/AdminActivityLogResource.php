<?php

namespace App\Filament\Resources\AdminActivityLogs;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\AdminActivityLogs\Pages\ListAdminActivityLogs;
use App\Filament\Resources\AdminActivityLogs\Pages\ViewAdminActivityLog;
use App\Filament\Resources\AdminActivityLogs\Schemas\AdminActivityLogInfolist;
use App\Filament\Resources\AdminActivityLogs\Tables\AdminActivityLogsTable;
use App\Models\AdminActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdminActivityLogResource extends Resource
{
    protected static ?string $slug = 'dziennik-aktywnosci';
    use HasDynamicNavigation;
    protected static ?string $model = AdminActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'log aktywnosci';

    protected static ?string $pluralModelLabel = 'dziennik zmian';

    protected static ?string $navigationLabel = 'Dziennik zmian';

    protected static \UnitEnum|string|null $navigationGroup = 'System & ustawienia';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return AdminActivityLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminActivityLogsTable::configure($table);
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
            'index' => ListAdminActivityLogs::route('/'),
        ];
    }
}
