<?php

namespace App\Filament\Resources\ProductAttributes;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\ProductAttributes\Pages\CreateProductAttribute;
use App\Filament\Resources\ProductAttributes\Pages\EditProductAttribute;
use App\Filament\Resources\ProductAttributes\Pages\ListProductAttributes;
use App\Filament\Resources\ProductAttributes\Pages\ViewProductAttribute;
use App\Filament\Resources\ProductAttributes\Schemas\ProductAttributeForm;
use App\Filament\Resources\ProductAttributes\Schemas\ProductAttributeInfolist;
use App\Filament\Resources\ProductAttributes\Tables\ProductAttributesTable;
use App\Models\ProductAttribute;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductAttributeResource extends Resource
{
    protected static ?string $slug = 'atrybuty-produktow';
    use HasDynamicNavigation;
    protected static ?string $model = ProductAttribute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $modelLabel = 'atrybut produktu';

    protected static ?string $pluralModelLabel = 'atrybuty produktów';

    protected static ?string $navigationLabel = 'Atrybuty produktów';

    protected static \UnitEnum|string|null $navigationGroup = 'Oferta & galeria';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return \App\Filament\Resources\ProductAttributes\Schemas\ProductAttributeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return \App\Filament\Resources\ProductAttributes\Schemas\ProductAttributeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\ProductAttributes\Tables\ProductAttributesTable::configure($table);
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
            ->with(['categories'])
            ->withCount('productValues');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ProductAttributes\Pages\ListProductAttributes::route('/'),
        ];
    }

    public static function valueTypeOptions(): array
    {
        return [
            'text' => 'Tekst',
            'number' => 'Liczba',
            'boolean' => 'Tak / nie',
            'select' => 'Lista wyboru',
        ];
    }
}
