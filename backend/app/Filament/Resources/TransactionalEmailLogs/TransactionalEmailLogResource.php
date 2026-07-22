<?php

namespace App\Filament\Resources\TransactionalEmailLogs;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\TransactionalEmailLogs\Pages\ListTransactionalEmailLogs;
use App\Filament\Resources\TransactionalEmailLogs\Pages\ViewTransactionalEmailLog;
use App\Filament\Resources\TransactionalEmailLogs\Schemas\TransactionalEmailLogInfolist;
use App\Filament\Resources\TransactionalEmailLogs\Tables\TransactionalEmailLogsTable;
use App\Models\TransactionalEmailLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransactionalEmailLogResource extends Resource
{
    protected static ?string $slug = 'wyslane-emaile';
    use HasDynamicNavigation;
    protected static ?string $model = TransactionalEmailLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $modelLabel = 'mail transakcyjny';

    protected static ?string $pluralModelLabel = 'maile transakcyjne';

    protected static ?string $navigationLabel = 'Maile transakcyjne';

    protected static \UnitEnum|string|null $navigationGroup = 'System & ustawienia';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return TransactionalEmailLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionalEmailLogsTable::configure($table);
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
            'index' => ListTransactionalEmailLogs::route('/'),
        ];
    }
}
