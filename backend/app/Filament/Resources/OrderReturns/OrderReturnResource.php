<?php

namespace App\Filament\Resources\OrderReturns;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\OrderReturns\Pages\CreateOrderReturn;
use App\Filament\Resources\OrderReturns\Pages\EditOrderReturn;
use App\Filament\Resources\OrderReturns\Pages\ListOrderReturns;
use App\Filament\Resources\OrderReturns\Pages\ViewOrderReturn;
use App\Filament\Resources\OrderReturns\Schemas\OrderReturnForm;
use App\Filament\Resources\OrderReturns\Tables\OrderReturnsTable;
use App\Models\OrderReturn;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderReturnResource extends Resource
{
    protected static ?string $slug = 'zwroty';
    use HasDynamicNavigation;
    protected static ?string $model = OrderReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static ?string $modelLabel = 'zwrot';

    protected static ?string $pluralModelLabel = 'zwroty';

    protected static ?string $navigationLabel = 'Zwroty';

    protected static \UnitEnum|string|null $navigationGroup = 'Sprzedaż & zapytania';

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return OrderReturnForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderReturnsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['order', 'user', 'items.orderItem']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderReturns::route('/'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'pending' => 'Oczekujący',
            'approved' => 'Zatwierdzony',
            'received' => 'Odebrany',
            'rejected' => 'Odrzucony',
        ];
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'approved' => 'info',
            'received' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }
}

