<?php

namespace App\Filament\Resources\FaqItems;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\FaqItems\Pages\CreateFaqItem;
use App\Filament\Resources\FaqItems\Pages\EditFaqItem;
use App\Filament\Resources\FaqItems\Pages\ListFaqItems;
use App\Filament\Resources\FaqItems\Pages\ViewFaqItem;
use App\Filament\Resources\FaqItems\Schemas\FaqItemForm;
use App\Filament\Resources\FaqItems\Schemas\FaqItemInfolist;
use App\Filament\Resources\FaqItems\Tables\FaqItemsTable;
use App\Models\FaqItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FaqItemResource extends Resource
{
    use HasDynamicNavigation;
    protected static ?string $model = FaqItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $modelLabel = 'FAQ';

    protected static ?string $pluralModelLabel = 'FAQ';

    protected static ?string $navigationLabel = 'FAQ';

    protected static ?string $slug = 'faq';

    protected static bool $shouldRegisterNavigation = false;

    protected static \UnitEnum|string|null $navigationGroup = 'Oferta & Galeria';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return FaqItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FaqItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqItems::route('/'),
        ];
    }
}
