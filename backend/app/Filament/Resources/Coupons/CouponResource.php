<?php

namespace App\Filament\Resources\Coupons;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Coupons\Pages\ViewCoupon;
use App\Filament\Resources\Coupons\Schemas\CouponForm;
use App\Filament\Resources\Coupons\Schemas\CouponInfolist;
use App\Filament\Resources\Coupons\Tables\CouponsTable;
use App\Models\Coupon;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    use HasDynamicNavigation;
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $modelLabel = 'kupon';

    protected static ?string $pluralModelLabel = 'kupony';

    protected static ?string $navigationLabel = 'Kupony';

    protected static ?string $slug = 'kupony';

    protected static \UnitEnum|string|null $navigationGroup = 'Sprzedaż & zapytania';

    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return CouponForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CouponInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('orders');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
        ];
    }

    public static function discountTypeOptions(): array
    {
        return [
            'percentage' => 'Procentowy',
            'fixed_product' => 'Kwotowy na produkt',
            'fixed_cart' => 'Kwotowy na koszyk',
        ];
    }

    public static function formatDiscountTypeLabel(?string $state): string
    {
        if (blank($state)) {
            return '-';
        }

        return static::discountTypeOptions()[$state] ?? Str::headline(str_replace('_', ' ', $state));
    }

    public static function discountTypeColor(?string $state): string
    {
        return match ($state) {
            'percentage' => 'info',
            'fixed_product' => 'warning',
            'fixed_cart' => 'success',
            default => 'gray',
        };
    }

    public static function formatDiscountValue(?string $discountType, ?int $value, ?string $currency): string
    {
        if ($value === null) {
            return '-';
        }

        if ($discountType === 'percentage') {
            return $value . '%';
        }

        return number_format($value / 100, 2, ',', ' ') . ' ' . ($currency ?: 'PLN');
    }
}
