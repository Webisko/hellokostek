<?php

namespace App\Filament\Resources\IrydologyCases;

use App\Filament\Resources\IrydologyCases\Pages\EditIrydologyCase;
use App\Filament\Resources\IrydologyCases\Pages\ListIrydologyCases;
use App\Filament\Resources\IrydologyCases\Pages\ViewIrydologyCase;
use App\Filament\Resources\IrydologyCases\Schemas\IrydologyCaseForm;
use App\Filament\Resources\IrydologyCases\Schemas\IrydologyCaseInfolist;
use App\Filament\Resources\IrydologyCases\Tables\IrydologyCasesTable;
use App\Models\IrydologyCase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IrydologyCaseResource extends Resource
{
    protected static ?string $model = IrydologyCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static ?string $modelLabel = 'sprawa irydologii';

    protected static ?string $pluralModelLabel = 'sprawy irydologii';

    protected static ?string $navigationLabel = 'Irydologia';

    protected static ?int $navigationSort = 45;

    public static function form(Schema $schema): Schema
    {
        return IrydologyCaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IrydologyCaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IrydologyCasesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['order', 'orderItem'])->latest('created_at');
    }

    public static function canCreate(): bool
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
            'index' => ListIrydologyCases::route('/'),
            'view' => ViewIrydologyCase::route('/{record}'),
            'edit' => EditIrydologyCase::route('/{record}/edit'),
        ];
    }
}