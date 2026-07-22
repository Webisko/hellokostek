<?php

namespace App\Filament\Resources\FailedJobs;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\FailedJobs\Pages\ListFailedJobs;
use App\Filament\Resources\FailedJobs\Pages\ViewFailedJob;
use App\Filament\Resources\FailedJobs\Schemas\FailedJobInfolist;
use App\Filament\Resources\FailedJobs\Tables\FailedJobsTable;
use App\Models\FailedJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FailedJobResource extends Resource
{
    protected static ?string $slug = 'nieudane-zadania';
    use HasDynamicNavigation;
    protected static ?string $model = FailedJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $modelLabel = 'nieudane zadanie';

    protected static ?string $pluralModelLabel = 'nieudane zadania';

    protected static ?string $navigationLabel = 'Nieudane zadania';

    protected static \UnitEnum|string|null $navigationGroup = 'System & ustawienia';

    protected static ?int $navigationSort = 5;

    public static function infolist(Schema $schema): Schema
    {
        return FailedJobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FailedJobsTable::configure($table);
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
            'index' => ListFailedJobs::route('/'),
        ];
    }
}
