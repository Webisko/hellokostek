<?php

namespace App\Filament\Resources\AbandonedCarts;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\AbandonedCarts\Pages\ListAbandonedCarts;
use App\Filament\Resources\AbandonedCarts\Tables\AbandonedCartsTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AbandonedCartResource extends Resource
{
    use HasDynamicNavigation;
    protected static ?string $slug = 'porzucone-koszyki';
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $modelLabel = 'porzucony koszyk';

    protected static ?string $pluralModelLabel = 'porzucone koszyki';

    protected static ?string $navigationLabel = 'Porzucone koszyki';

    protected static \UnitEnum|string|null $navigationGroup = 'Sprzedaż & zapytania';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', 'draft')
            ->whereHas('items');
    }

    public static function table(Table $table): Table
    {
        return AbandonedCartsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbandonedCarts::route('/'),
        ];
    }
}
