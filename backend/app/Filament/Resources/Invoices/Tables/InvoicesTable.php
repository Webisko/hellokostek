<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Numer faktury')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.number')
                    ->label('Numer zamówienia')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record): ?string => $record->order_id ? route('filament.admin.resources.orders.view', ['record' => $record->order_id]) : null),
                TextColumn::make('order.customer_email')
                    ->label('Email klienta')
                    ->searchable(),
                TextColumn::make('issue_date')
                    ->label('Data wystawienia')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Termin płatności')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_amount')
                    ->label('Suma brutto')
                    ->money(fn ($record) => $record->order?->currency ?? 'PLN', divideBy: 100)
                    ->sortable(),
                TextColumn::make('tax_amount')
                    ->label('Kwota VAT')
                    ->money(fn ($record) => $record->order?->currency ?? 'PLN', divideBy: 100)
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Pobierz PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Pobierz plik faktury')
                    ->action(function (Invoice $record) {
                        if ($record->pdf_path && Storage::disk('local')->exists($record->pdf_path)) {
                            return Storage::disk('local')->download($record->pdf_path, 'Faktura_' . str_replace('/', '_', $record->number) . '.pdf');
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Błąd')
                            ->body('Plik PDF faktury nie został odnaleziony na serwerze.')
                            ->danger()
                            ->send();
                    })
            ]);
    }
}
