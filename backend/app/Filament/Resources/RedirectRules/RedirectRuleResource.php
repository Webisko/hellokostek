<?php

namespace App\Filament\Resources\RedirectRules;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\RedirectRules\Pages\CreateRedirectRule;
use App\Filament\Resources\RedirectRules\Pages\EditRedirectRule;
use App\Filament\Resources\RedirectRules\Pages\ListRedirectRules;
use App\Filament\Resources\RedirectRules\Pages\ViewRedirectRule;
use App\Filament\Resources\RedirectRules\Schemas\RedirectRuleForm;
use App\Filament\Resources\RedirectRules\Schemas\RedirectRuleInfolist;
use App\Filament\Resources\RedirectRules\Tables\RedirectRulesTable;
use App\Models\RedirectRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RedirectRuleResource extends Resource
{
    protected static ?string $slug = 'przekierowania';
    use HasDynamicNavigation;
    protected static ?string $model = RedirectRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static ?string $modelLabel = 'przekierowanie';

    protected static ?string $pluralModelLabel = 'przekierowania';

    protected static ?string $navigationLabel = 'Przekierowania';

    protected static \UnitEnum|string|null $navigationGroup = 'System & ustawienia';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return RedirectRuleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RedirectRuleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RedirectRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRedirectRules::route('/'),
        ];
    }
}
