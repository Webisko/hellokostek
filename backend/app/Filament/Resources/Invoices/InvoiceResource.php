<?php

namespace App\Filament\Resources\Invoices;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $slug = 'faktury';
    use HasDynamicNavigation;
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'faktura';

    protected static ?string $pluralModelLabel = 'faktury';

    protected static ?string $navigationLabel = 'Faktury';

    protected static \UnitEnum|string|null $navigationGroup = 'Sprzedaż & zapytania';

    protected static ?int $navigationSort = 50;

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
        ];
    }
}

