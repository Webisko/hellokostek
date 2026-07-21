<?php

namespace App\Filament\Resources\Products;

use App\Traits\HasDynamicNavigation;

use App\Domain\Commerce\Enums\ProductType;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    use HasDynamicNavigation;
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $modelLabel = 'produkt';

    protected static ?string $pluralModelLabel = 'produkty';

    protected static ?string $navigationLabel = 'Produkty i Prace';

    protected static ?string $slug = 'produkty';

    protected static \UnitEnum|string|null $navigationGroup = 'Oferta & Galeria';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'slug'];
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ])
            ->with([
                'categories',
                'attributeValues.attribute',
            ])
            ->withCount('attributeValues');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
        ];
    }

    public static function typeOptions(): array
    {
        return collect(ProductType::cases())
            ->mapWithKeys(fn (ProductType $type): array => [$type->value => static::formatStateLabel($type)])
            ->all();
    }

    public static function formatStateLabel(mixed $state): string
    {
        if (blank($state)) {
            return '-';
        }

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        $labels = [
            'physical' => 'Fizyczny',
            'digital' => 'Cyfrowy',
            'service' => 'Usługa',
            'bundle' => 'Pakiet',
        ];

        return $labels[$state] ?? Str::headline(str_replace(['-', '_'], ' ', (string) $state));
    }

    public static function typeColor(mixed $state): string
    {
        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        return match ($state) {
            ProductType::Physical->value => 'info',
            ProductType::Digital->value => 'success',
            ProductType::Service->value => 'warning',
            ProductType::Bundle->value => 'primary',
            default => 'gray',
        };
    }
}
