<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\IntegrationLog;
use App\Models\Order;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(3)
                    ->schema([
                        Group::make([
                            Section::make('Wymagane działanie - Faktura Korygująca')->columnSpanFull()
                                ->visible(fn (Order $record): bool => $record->status === 'cancelled' || $record->payment_status === 'refunded')
                                ->schema([
                                    TextEntry::make('corrective_invoice_notice')
                                        ->label('')
                                        ->state(fn () => 'Status tego zamówienia to Anulowane lub płatność została zwrócona. W świetle polskiego prawa wymagane jest wystawienie Faktury Korygującej (korekty). Korektę należy wystawić bezpośrednio w swoim połączonym systemie księgowym (np. Fakturownia, iFirma, inFakt, wFirma), do którego dane z tego zamówienia zostały przesłane automatycznie.'),
                                ]),
                            Section::make('Podsumowanie')->columnSpanFull()
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('number')
                                        ->label('Numer')
                                        ->copyable()
                                        ->icon('heroicon-o-document-text'),
                                    TextEntry::make('status')
                                        ->label('Status zamówienia')
                                        ->badge()
                                        ->formatStateUsing(fn (?string $state): string => OrderResource::formatStateLabel($state, OrderResource::statusOptions()))
                                        ->color(fn (?string $state): string => OrderResource::orderStatusColor($state))
                                        ->icon(fn (?string $state): ?string => match ($state) {
                                            'draft' => 'heroicon-o-pencil',
                                            'placed' => 'heroicon-o-clock',
                                            'shipped' => 'heroicon-o-truck',
                                            'completed' => 'heroicon-o-check-circle',
                                            'cancelled' => 'heroicon-o-x-circle',
                                            default => null,
                                        }),
                                    TextEntry::make('payment_status')
                                        ->label('Status płatności')
                                        ->badge()
                                        ->formatStateUsing(fn (?string $state): string => OrderResource::formatStateLabel($state, OrderResource::paymentStatusOptions()))
                                        ->color(fn (?string $state): string => OrderResource::paymentStatusColor($state))
                                        ->icon(fn (?string $state): ?string => match ($state) {
                                            'paid' => 'heroicon-o-check-circle',
                                            'pending', 'awaiting_payment', 'initiated', 'pending_gateway', 'pending_collection' => 'heroicon-o-clock',
                                            'failed', 'configuration_required' => 'heroicon-o-exclamation-circle',
                                            default => null,
                                        }),
                                    TextEntry::make('fulfillment_status')
                                        ->label('Status realizacji')
                                        ->badge()
                                        ->formatStateUsing(fn (?string $state): string => OrderResource::formatStateLabel($state, OrderResource::fulfillmentStatusOptions()))
                                        ->color(fn (?string $state): string => OrderResource::fulfillmentStatusColor($state))
                                        ->icon(fn (?string $state): ?string => match ($state) {
                                            'fulfilled' => 'heroicon-o-check-circle',
                                            'pending' => 'heroicon-o-clock',
                                            default => null,
                                        }),
                                    TextEntry::make('total_amount')
                                        ->label('Kwota łączna')
                                        ->money(fn ($record): string => $record->currency ?: 'PLN', divideBy: 100)
                                        ->weight('bold'),
                                    TextEntry::make('placed_at')
                                        ->label('Złożone')
                                        ->dateTime('Y-m-d H:i'),
                                    TextEntry::make('shipping_method_name')
                                        ->label('Metoda dostawy'),
                                    TextEntry::make('customer_segment')
                                        ->label('Segment klienta')
                                        ->formatStateUsing(fn ($state): string => OrderResource::formatStateLabel($state)),
                                    TextEntry::make('notes')
                                        ->label('Uwagi')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')->columnSpanFull(),
                                ]),
                            Section::make('Zamówione produkty')->columnSpanFull()
                                ->schema([
                                    RepeatableEntry::make('items')
                                        ->hiddenLabel()
                                        ->contained(false)
                                        ->table([
                                            TableColumn::make('Produkt'),
                                            TableColumn::make('SKU'),
                                            TableColumn::make('Typ'),
                                            TableColumn::make('Ilość'),
                                            TableColumn::make('Suma'),
                                        ])
                                        ->schema([
                                            TextEntry::make('name')
                                                ->label('Produkt')
                                                ->weight('bold'),
                                            TextEntry::make('sku')
                                                ->label('SKU')
                                                ->copyable()
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('product_type')
                                                ->label('Typ')
                                                ->badge()
                                                ->formatStateUsing(fn ($state): string => OrderResource::formatStateLabel($state)),
                                            TextEntry::make('quantity')
                                                ->label('Ilość')
                                                ->numeric(decimalPlaces: 0)
                                                ->weight('bold'),
                                            TextEntry::make('total_amount')
                                                ->label('Suma')
                                                ->money('PLN', divideBy: 100)
                                                ->weight('bold'),
                                        ]),
                                ]),
                            Section::make('Dostawa')->columnSpanFull()
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextEntry::make('shipping_method_name')
                                                ->label('Metoda dostawy')
                                                ->icon('heroicon-o-truck')
                                                ->copyable()
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('carrier')
                                                ->label('Przewoźnik')
                                                ->icon('heroicon-o-building-office')
                                                ->copyable()
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('tracking_number')
                                                ->label('Numer śledzenia')
                                                ->icon('heroicon-o-qr-code')
                                                ->copyable()
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('shipping_address_html')
                                                ->label('Adres dostawy')
                                                ->html()
                                                ->state(function (Order $record): string {
                                                    $addr = $record->shipping_address;
                                                    if (empty($addr) || !is_array($addr)) return '-';
                                                    return sprintf(
                                                        '<strong>%s %s</strong><br>%s %s<br>%s %s<br>%s',
                                                        e($addr['first_name'] ?? ''),
                                                        e($addr['last_name'] ?? ''),
                                                        e($addr['street'] ?? ''),
                                                        e($addr['house_number'] ?? ''),
                                                        e($addr['postal_code'] ?? ''),
                                                        e($addr['city'] ?? ''),
                                                        e($addr['country'] ?? 'PL')
                                                    );
                                                })
                                                ->copyable()
                                                ->copyableState(function (Order $record): string {
                                                    $addr = $record->shipping_address;
                                                    if (empty($addr) || !is_array($addr)) return '';
                                                    return sprintf(
                                                        "%s %s\n%s %s\n%s %s\n%s",
                                                        $addr['first_name'] ?? '',
                                                        $addr['last_name'] ?? '',
                                                        $addr['street'] ?? '',
                                                        $addr['house_number'] ?? '',
                                                        $addr['postal_code'] ?? '',
                                                        $addr['city'] ?? '',
                                                        $addr['country'] ?? 'PL'
                                                    );
                                                })
                                                ->visible(fn (Order $record): bool => !self::hasDeliveryPoint($record)),
                                            TextEntry::make('delivery_point_id')
                                                ->label('ID punktu odbioru')
                                                ->icon('heroicon-o-identification')
                                                ->state(fn (Order $record): string => self::deliveryPointId($record))
                                                ->copyable()
                                                ->visible(fn (Order $record): bool => self::hasDeliveryPoint($record)),
                                            TextEntry::make('delivery_point_address')
                                                ->label('Adres punktu odbioru')
                                                ->icon('heroicon-o-map-pin')
                                                ->state(fn (Order $record): string => self::deliveryPointAddress($record))
                                                ->copyable()
                                                ->visible(fn (Order $record): bool => self::hasDeliveryPoint($record)),
                                        ]),
                                    RepeatableEntry::make('fulfillmentActions')
                                        ->label('Akcje realizacji')
                                        ->contained(false)
                                        ->table([
                                            TableColumn::make('Typ'),
                                            TableColumn::make('Status'),
                                            TableColumn::make('Tytuł'),
                                        ])
                                        ->schema([
                                            TextEntry::make('action_type')
                                                ->label('Typ')
                                                ->badge()
                                                ->formatStateUsing(fn (?string $state): string => OrderResource::formatStateLabel($state, OrderResource::fulfillmentActionOptions()))
                                                ->color(fn (?string $state): string => OrderResource::fulfillmentActionColor($state)),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->formatStateUsing(fn (?string $state): string => OrderResource::formatStateLabel($state, OrderResource::fulfillmentStatusOptions()))
                                                ->color(fn (?string $state): string => OrderResource::fulfillmentStatusColor($state)),
                                            TextEntry::make('title')
                                                ->label('Tytuł')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('orderItem.name')
                                                ->label('Pozycja')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('due_at')
                                                ->label('Termin')
                                                ->dateTime('Y-m-d H:i')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('instructions')
                                                ->label('Instrukcje')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                            Section::make('Transakcje płatnicze')->columnSpanFull()
                                ->schema([
                                    RepeatableEntry::make('paymentTransactions')
                                        ->hiddenLabel()
                                        ->contained(false)
                                        ->table([
                                            TableColumn::make('Dostawca'),
                                            TableColumn::make('Status'),
                                            TableColumn::make('Kwota'),
                                            TableColumn::make('Sesja'),
                                            TableColumn::make('Zainicjowano'),
                                        ])
                                        ->schema([
                                            TextEntry::make('provider')
                                                ->label('Dostawca')
                                                ->weight('bold'),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->formatStateUsing(fn (?string $state): string => OrderResource::formatStateLabel($state, OrderResource::paymentStatusOptions()))
                                                ->color(fn (?string $state): string => OrderResource::paymentStatusColor($state)),
                                            TextEntry::make('amount')
                                                ->label('Kwota')
                                                ->money('PLN', divideBy: 100)
                                                ->weight('bold'),
                                            TextEntry::make('external_session_id')
                                                ->label('Sesja')
                                                ->copyable()
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('initiated_at')
                                                ->label('Zainicjowano')
                                                ->dateTime('Y-m-d H:i')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('error_message')
                                                ->label('Błąd')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                            Section::make('Historia zmian admina')->columnSpanFull()
                                ->schema([
                                    RepeatableEntry::make('adminActivityLogs')
                                        ->hiddenLabel()
                                        ->contained(false)
                                        ->table([
                                            TableColumn::make('Kiedy'),
                                            TableColumn::make('Operator'),
                                            TableColumn::make('Zdarzenie'),
                                            TableColumn::make('Podsumowanie'),
                                        ])
                                        ->schema([
                                            TextEntry::make('created_at')
                                                ->label('Kiedy')
                                                ->dateTime('Y-m-d H:i'),
                                            TextEntry::make('actor.name')
                                                ->label('Operator')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('event')
                                                ->label('Zdarzenie')
                                                ->badge()
                                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                    'created' => 'Utworzono',
                                                    'updated' => 'Zaktualizowano',
                                                    'deleted' => 'Usunięto',
                                                    'restored' => 'Przywrócono',
                                                    default => $state ?? '-',
                                                })
                                                ->color(fn (?string $state): string => match ($state) {
                                                    'created' => 'success',
                                                    'updated' => 'warning',
                                                    'deleted' => 'danger',
                                                    default => 'gray',
                                                }),
                                            TextEntry::make('summary')
                                                ->label('Podsumowanie')
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                        ])->columnSpan(2),
 
                        Group::make([
                            Section::make('Klient')->columnSpanFull()
                                ->columns(1)
                                ->schema([
                                    TextEntry::make('customer_name')
                                        ->label('Imię i nazwisko')
                                        ->state(fn (Order $record): string => trim(($record->customer_first_name ?? '') . ' ' . ($record->customer_last_name ?? '')))
                                        ->icon('heroicon-o-user')
                                        ->weight('bold'),
                                    TextEntry::make('customer_email')
                                        ->label('E-mail')
                                        ->icon('heroicon-o-envelope')
                                        ->copyable(),
                                    TextEntry::make('customer_phone')
                                        ->label('Telefon')
                                        ->icon('heroicon-o-phone')
                                        ->copyable()
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                    TextEntry::make('wants_invoice')
                                        ->label('Chce fakturę')
                                        ->badge()
                                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie')
                                        ->color(fn ($state): string => $state ? 'success' : 'gray'),
                                    TextEntry::make('billing_company_name')
                                        ->label('Nazwa firmy')
                                        ->icon('heroicon-o-building-office')
                                        ->visible(fn ($record): bool => (bool) $record->wants_invoice),
                                    TextEntry::make('billing_nip')
                                        ->label('NIP')
                                        ->icon('heroicon-o-identification')
                                        ->copyable()
                                        ->visible(fn ($record): bool => (bool) $record->wants_invoice),
                                    TextEntry::make('is_privileged_entrepreneur')
                                        ->label('Przedsiębiorca uprzywilejowany')
                                        ->badge()
                                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie')
                                        ->color(fn ($state): string => $state ? 'success' : 'gray'),
                                    TextEntry::make('vies_status')
                                        ->label('Weryfikacja VIES')
                                        ->badge()
                                        ->state(fn (Order $record): ?string => data_get($record->metadata, 'vies_status'))
                                        ->color(fn ($state) => match ($state) {
                                            'valid' => 'success',
                                            'vies_disabled' => 'gray',
                                            'vies_down_fallback' => 'warning',
                                            default => 'danger'
                                        })
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'valid' => 'Zweryfikowany (Aktywny)',
                                            'vies_disabled' => 'Pominięto (Wyłączona walidacja)',
                                            'vies_down_fallback' => 'Zaakceptowano (Brak serwera VIES)',
                                            default => 'Nieaktywny / Błąd'
                                        })
                                        ->visible(fn (Order $record): bool => !empty(data_get($record->metadata, 'vies_status'))),
                                    TextEntry::make('vies_trader_name')
                                        ->label('Nazwa firmy (VIES)')
                                        ->state(fn (Order $record): ?string => data_get($record->metadata, 'vies_trader_name'))
                                        ->visible(fn (Order $record): bool => !empty(data_get($record->metadata, 'vies_trader_name'))),
                                    TextEntry::make('vies_trader_address')
                                        ->label('Adres firmy (VIES)')
                                        ->state(fn (Order $record): ?string => data_get($record->metadata, 'vies_trader_address'))
                                        ->visible(fn (Order $record): bool => !empty(data_get($record->metadata, 'vies_trader_address'))),
                                 ]),
                            Section::make('Adres rozliczeniowy')->columnSpanFull()
                                ->columns(1)
                                ->schema([
                                    TextEntry::make('billing_address_html')
                                        ->label('Adres rozliczeniowy')
                                        ->html()
                                        ->state(function (Order $record): string {
                                            $addr = $record->billing_address;
                                            if (empty($addr) || !is_array($addr)) return '-';
                                            return sprintf(
                                                '<strong>%s</strong><br>%s %s<br>%s %s<br>%s',
                                                e($addr['company_name'] ?? $record->customer_first_name . ' ' . $record->customer_last_name),
                                                e($addr['street'] ?? ''),
                                                e($addr['house_number'] ?? ''),
                                                e($addr['postal_code'] ?? ''),
                                                e($addr['city'] ?? ''),
                                                e($addr['country'] ?? 'PL')
                                            );
                                        }),
                                 ]),
                            Section::make('Połączenia z zewnętrznymi systemami')
                                ->description('Rejestr automatycznej wymiany informacji z zewnętrznymi usługami (np. generowanie faktury w Fakturowni czy zlecenie wysyłki kurierskiej).')
                                ->columnSpanFull()
                                ->schema([
                                    RepeatableEntry::make('integrationLogs')
                                        ->hiddenLabel()
                                        ->contained(false)
                                        ->extraAttributes([
                                            'class' => '!mt-0 !pt-0 !pb-0 !mb-0',
                                            'style' => 'margin-top: 0px !important; padding-top: 0px !important; padding-bottom: 0px !important; margin-bottom: 0px !important;',
                                        ])
                                        ->schema([
                                            TextEntry::make('occurred_at')
                                                ->label('Kiedy')
                                                ->dateTime('Y-m-d H:i')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('integration')
                                                ->label('Integracja')
                                                ->badge()
                                                ->formatStateUsing(fn (?string $state): string => IntegrationLog::integrationLabel($state)),
                                            TextEntry::make('event')
                                                ->label('Zdarzenie')
                                                ->formatStateUsing(fn (?string $state): string => IntegrationLog::eventLabel($state))
                                                ->helperText(fn (IntegrationLog $record): string => 'Kod techniczny: ' . $record->event),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->formatStateUsing(fn (?string $state): string => IntegrationLog::statusLabel($state))
                                                ->color(fn (?string $state): string => IntegrationLog::statusColor($state)),
                                            TextEntry::make('external_reference')
                                                ->label('Referencja')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                            TextEntry::make('error_message')
                                                ->label('Błąd')
                                                ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                        ])->columnSpan(1),
                    ])
            ]);
    }

    private static function hasDeliveryPoint(Order $record): bool
    {
        $deliveryPoint = (array) data_get($record->metadata, 'delivery_point', []);
        return filled(data_get($deliveryPoint, 'id')) || filled(data_get($deliveryPoint, 'name'));
    }

    private static function deliveryPointId(Order $record): string
    {
        $deliveryPoint = (array) data_get($record->metadata, 'delivery_point', []);
        $id = data_get($deliveryPoint, 'id');
        $name = data_get($deliveryPoint, 'name');
        return (string) ($id ?: $name ?: '-');
    }

    private static function deliveryPointAddress(Order $record): string
    {
        $deliveryPoint = (array) data_get($record->metadata, 'delivery_point', []);

        $parts = array_filter([
            data_get($deliveryPoint, 'address'),
            trim(implode(' ', array_filter([
                data_get($deliveryPoint, 'postal_code'),
                data_get($deliveryPoint, 'city'),
            ]))),
        ], fn ($value): bool => filled($value));

        return $parts === [] ? '-' : implode(', ', $parts);
    }
}
