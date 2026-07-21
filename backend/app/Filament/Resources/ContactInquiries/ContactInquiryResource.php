<?php

namespace App\Filament\Resources\ContactInquiries;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\ContactInquiries\Pages\ListContactInquiries;
use App\Filament\Resources\ContactInquiries\Schemas\ContactInquiryForm;
use App\Filament\Resources\ContactInquiries\Tables\ContactInquiriesTable;
use App\Models\ContactInquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContactInquiryResource extends Resource
{
    protected static ?string $slug = 'zapytania-kontaktowe';
    use HasDynamicNavigation;
    protected static ?string $model = ContactInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $modelLabel = 'zapytanie o portret';

    protected static ?string $pluralModelLabel = 'zapytania o portrety';

    protected static ?string $navigationLabel = 'Zapytania o portrety';

    protected static \UnitEnum|string|null $navigationGroup = 'Sprzedaż & Zapytania';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ContactInquiryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactInquiriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactInquiries::route('/'),
        ];
    }
}

