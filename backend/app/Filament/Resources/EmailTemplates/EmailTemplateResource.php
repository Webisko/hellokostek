<?php

namespace App\Filament\Resources\EmailTemplates;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Filament\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Filament\Resources\EmailTemplates\Schemas\EmailTemplateForm;
use App\Filament\Resources\EmailTemplates\Tables\EmailTemplatesTable;
use App\Models\EmailTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $slug = 'szablony-wiadomosci';
    use HasDynamicNavigation;
    protected static ?string $model = EmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $modelLabel = 'szablon e-mail';

    protected static ?string $pluralModelLabel = 'szablony e-mail';

    protected static ?string $navigationLabel = 'Szablony e-mail';

    protected static \UnitEnum|string|null $navigationGroup = 'System & Ustawienia';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return EmailTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailTemplates::route('/'),
        ];
    }
}

