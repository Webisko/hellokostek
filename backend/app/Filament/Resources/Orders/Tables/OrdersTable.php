<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class OrdersTable
{
    private static array $beforeSaveSnapshot = [];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->recordAction('view')
            ->columns([
                TextColumn::make('number')
                    ->label('Numer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->prefix('#'),
                TextColumn::make('customer_name')
                    ->label('Imię i nazwisko')
                    ->state(fn (Order $record): string => trim(($record->customer_first_name ?? '') . ' ' . ($record->customer_last_name ?? '')))
                    ->searchable(['customer_first_name', 'customer_last_name'])
                    ->sortable(query: fn (\Illuminate\Database\Eloquent\Builder $query, string $direction): \Illuminate\Database\Eloquent\Builder => $query->orderBy('customer_first_name', $direction)->orderBy('customer_last_name', $direction)),
                TextColumn::make('customer_email')
                    ->label('E-mail')
                    ->icon('heroicon-o-envelope')
                    ->iconColor('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Order $record): string => OrderResource::displayStatusLabel($record))
                    ->color(fn (Order $record): string => OrderResource::displayStatusColor($record))
                    ->icon(fn (Order $record): string => OrderResource::displayStatusIcon($record))
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Suma')
                    ->money(fn (Order $record): string => $record->currency ?: 'PLN', divideBy: 100)
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('placed_at')
                    ->label('Złożone')
                    ->dateTime('H:i d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status zamówienia')
                    ->options(OrderResource::statusOptions()),
                SelectFilter::make('payment_status')
                    ->label('Status płatności')
                    ->options(OrderResource::paymentStatusOptions()),
                SelectFilter::make('fulfillment_status')
                    ->label('Status realizacji')
                    ->options(OrderResource::fulfillmentStatusOptions()),
                \Filament\Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Podgląd')->extraAttributes(['style' => 'display: none !important;'])
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->extraModalFooterActions([
                        \Filament\Actions\Action::make('generate_invoice')
                            ->label('Generuj fakturę')
                            ->icon('heroicon-o-document-plus')
                            ->color('info')
                            ->visible(fn (Order $record): bool => $record->invoices()->count() === 0)
                            ->action(function (Order $record): void {
                                try {
                                    $driver = app(\App\Domain\Commerce\Accounting\Drivers\BuiltInInvoiceDriver::class);
                                    $driver->sendOrder($record);

                                    Notification::make()
                                        ->title('Sukces')
                                        ->body('Faktura została wygenerowana.')
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Błąd generowania faktury')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),

                        \Filament\Actions\Action::make('download_invoice')
                            ->label('Pobierz fakturę')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('info')
                            ->visible(fn (Order $record): bool => $record->invoices()->count() > 0)
                            ->action(function (Order $record) {
                                $invoice = $record->invoices()->latest()->first();
                                if ($invoice && $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
                                    return Storage::disk('local')->download($invoice->pdf_path, 'Faktura_' . str_replace('/', '_', $invoice->number) . '.pdf');
                                }
                                Notification::make()
                                    ->title('Błąd')
                                    ->body('Plik faktury nie został odnaleziony na serwerze.')
                                    ->danger()
                                    ->send();
                            }),

                        \Filament\Actions\Action::make('generate_inpost_label')
                            ->label('Generuj etykietę InPost')
                            ->icon('heroicon-o-truck')
                            ->color('warning')
                            ->visible(fn (Order $record): bool => empty($record->tracking_number) && !str_contains(strtolower($record->shipping_method_code ?? ''), 'orlen'))
                            ->form([
                                \Filament\Forms\Components\Select::make('package_size')
                                    ->label('Gabaryt paczki')
                                    ->options([
                                        'A' => 'Gabaryt A (mała paczka)',
                                        'B' => 'Gabaryt B (średnia paczka)',
                                        'C' => 'Gabaryt C (duża paczka)',
                                    ])
                                    ->default('B')
                                    ->required()
                                    ->native(false),
                            ])
                            ->action(function (Order $record, array $data): void {
                                try {
                                    $inpostService = app(\App\Domain\Commerce\Logistics\InPostService::class);
                                    $result = $inpostService->generateLabel($record, $data['package_size']);

                                    $record->forceFill([
                                        'tracking_number' => $result['tracking_number'],
                                        'carrier' => 'InPost',
                                        'status' => 'shipped',
                                    ])->save();

                                    Notification::make()
                                        ->title('Sukces')
                                        ->body('Paczka została wygenerowana w InPost. Status zamówienia zmieniono na Wysłane.')
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Błąd generowania etykiety')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),

                        \Filament\Actions\Action::make('download_inpost_label')
                            ->label('Pobierz etykietę InPost')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('warning')
                            ->visible(fn (Order $record): bool => !empty($record->tracking_number) && $record->carrier === 'InPost')
                            ->url(fn (Order $record): string => route('admin.orders.inpost-label', ['number' => $record->number]))
                            ->openUrlInNewTab(),

                        \Filament\Actions\Action::make('generate_orlen_paczka_label')
                            ->label('Generuj etykietę ORLEN Paczka')
                            ->icon('heroicon-o-truck')
                            ->color('warning')
                            ->visible(fn (Order $record): bool => empty($record->tracking_number) && str_contains(strtolower($record->shipping_method_code ?? ''), 'orlen'))
                            ->form([
                                \Filament\Forms\Components\Select::make('package_size')
                                    ->label('Gabaryt paczki')
                                    ->options([
                                        'S' => 'Gabaryt S (mała paczka)',
                                        'M' => 'Gabaryt M (średnia paczka)',
                                        'L' => 'Gabaryt L (duża paczka)',
                                    ])
                                    ->default('M')
                                    ->required()
                                    ->native(false),
                            ])
                            ->action(function (Order $record, array $data): void {
                                try {
                                    $orlenService = app(\App\Domain\Commerce\Logistics\OrlenPaczkaService::class);
                                    $result = $orlenService->generateLabel($record, $data['package_size']);

                                    $record->forceFill([
                                        'tracking_number' => $result['tracking_number'],
                                        'carrier' => 'OrlenPaczka',
                                        'status' => 'shipped',
                                    ])->save();

                                    Notification::make()
                                        ->title('Sukces')
                                        ->body('Paczka została wygenerowana w ORLEN Paczka. Status zamówienia zmieniono na Wysłane.')
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Błąd generowania etykiety')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),

                        \Filament\Actions\Action::make('download_orlen_paczka_label')
                            ->label('Pobierz etykietę ORLEN Paczka')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('warning')
                            ->visible(fn (Order $record): bool => !empty($record->tracking_number) && $record->carrier === 'OrlenPaczka')
                            ->url(fn (Order $record): string => route('admin.orders.orlen-label', ['number' => $record->number]))
                            ->openUrlInNewTab(),
                        Action::make('mark_as_paid')
                            ->label('Oznacz jako opłacone')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->visible(fn (Order $record): bool => $record->payment_status !== 'paid' && ! in_array($record->status, ['cancelled', 'draft'], true))
                            ->requiresConfirmation()
                            ->modalHeading('Oznaczyć zamówienie jako opłacone?')
                            ->modalDescription('Zmiana statusu płatności na „Opłacone” automatycznie zaktualizuje stan magazynowy, wygeneruje fakturę (jeśli fakturowanie jest włączone) oraz wyśle klientowi e-mail z potwierdzeniem płatności.')
                            ->action(function (Order $record): void {
                                $record->update(['payment_status' => 'paid']);

                                Notification::make()
                                    ->title('Zamówienie oznaczono jako opłacone')
                                    ->success()
                                    ->send();
                            }),

                        Action::make('mark_as_shipped')
                            ->label('Oznacz jako wysłane')
                            ->icon('heroicon-o-paper-airplane')
                            ->color('primary')
                            ->visible(fn (Order $record): bool => ! in_array($record->status, ['shipped', 'completed', 'cancelled', 'draft'], true))
                            ->modalHeading('Oznacz zamówienie jako wysłane')
                            ->modalDescription('Status zamówienia zmieni się na „Wysłane”, a klient otrzyma e-mail z powiadomieniem o wysyłce.')
                            ->form([
                                TextInput::make('carrier')
                                    ->label('Przewoźnik (opcjonalnie)')
                                    ->datalist(['InPost', 'ORLEN Paczka', 'DPD', 'Poczta Polska', 'Odbiór osobisty'])
                                    ->default(fn (Order $record): ?string => $record->carrier)
                                    ->maxLength(100),
                                TextInput::make('tracking_number')
                                    ->label('Numer przesyłki (opcjonalnie)')
                                    ->default(fn (Order $record): ?string => $record->tracking_number)
                                    ->maxLength(100),
                            ])
                            ->action(function (Order $record, array $data): void {
                                $updates = [
                                    'status' => 'shipped',
                                    'fulfillment_status' => 'fulfilled',
                                    'shipped_at' => $record->shipped_at ?? now(),
                                ];
                                if (filled($data['carrier'] ?? null)) {
                                    $updates['carrier'] = trim((string) $data['carrier']);
                                }
                                if (filled($data['tracking_number'] ?? null)) {
                                    $updates['tracking_number'] = trim((string) $data['tracking_number']);
                                }

                                $record->update($updates);

                                Notification::make()
                                    ->title('Zamówienie oznaczono jako wysłane')
                                    ->success()
                                    ->send();
                            }),

                        EditAction::make()
                            ->button()
                            ->label('Edytuj')
                            ->slideOver()
                            ->modalWidth('7xl')
                            ->cancelParentActions(),
                    ]),
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')
                    ->slideOver()
                    ->modalWidth('7xl'),
                \Filament\Actions\DeleteAction::make()->iconButton()->tooltip('Usuń'),
                \Filament\Actions\RestoreAction::make()->iconButton()->tooltip('Przywróć'),
                \Filament\Actions\ForceDeleteAction::make()->iconButton()->tooltip('Usuń trwale'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
