<?php

namespace App\Filament\Resources\ContentPages;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Filament\Resources\ContentPages\Pages\ViewContentPage;
use App\Filament\Resources\ContentPages\Schemas\ContentPageForm;
use App\Filament\Resources\ContentPages\Schemas\ContentPageInfolist;
use App\Filament\Resources\ContentPages\Tables\ContentPagesTable;
use App\Models\ContentPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContentPageResource extends Resource
{
    use HasDynamicNavigation;
    protected static ?string $model = ContentPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'strona';

    protected static ?string $pluralModelLabel = 'strony';

    protected static ?string $navigationLabel = 'Strony';

    protected static ?string $slug = 'strony-statyczne';

    protected static \UnitEnum|string|null $navigationGroup = 'Strona & wygląd';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return ContentPageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContentPageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentPagesTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentPages::route('/'),
        ];
    }
}
