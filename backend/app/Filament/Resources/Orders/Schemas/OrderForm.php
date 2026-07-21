<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Wymagane działanie - Faktura Korygująca')->columnSpanFull()
                    ->visible(fn ($record): bool => $record && ($record->status === 'cancelled' || $record->payment_status === 'refunded'))
                    ->schema([
                        Placeholder::make('corrective_invoice_notice')
                            ->label('')
                            ->content('Status tego zamówienia to Anulowane lub płatność została zwrócona. W świetle polskiego prawa wymagane jest wystawienie Faktury Korygującej (korekty). Korektę należy wystawić bezpośrednio w swoim połączonym systemie księgowym (np. Fakturownia, iFirma, inFakt, wFirma), do którego dane z tego zamówienia zostały przesłane automatycznie.'),
                    ]),
                Section::make('Obsluga zamowienia')->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('number')
                            ->label('Numer')
                            ->placeholder('Generowany automatycznie')
                            ->disabled()
                            ->dehydrated(fn ($state) => filled($state)),
                        Select::make('user_id')
                            ->label('Klient z bazy (opcjonalnie)')
                            ->relationship('customer', 'name', fn ($query) => $query->where('role', \App\Domain\Commerce\Enums\UserRole::Customer))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $user = \App\Models\User::find($state);
                                if ($user) {
                                    $set('customer_email', $user->email);
                                    $parts = explode(' ', $user->name);
                                    $set('customer_first_name', $parts[0] ?? '');
                                    $set('customer_last_name', $parts[1] ?? '');
                                    $set('customer_phone', $user->customerProfile?->phone ?? '');
                                }
                            }),
                        TextInput::make('customer_first_name')
                            ->label('Imię klienta')
                            ->required(),
                        TextInput::make('customer_last_name')
                            ->label('Nazwisko klienta')
                            ->required(),
                        TextInput::make('customer_email')
                            ->label('E-mail klienta')
                            ->email()
                            ->required(),
                        TextInput::make('customer_phone')
                            ->label('Telefon klienta'),
                        Toggle::make('is_privileged_entrepreneur')
                            ->label('Przedsiębiorca uprzywilejowany')
                            ->default(false),
                        Select::make('status')
                            ->label('Status zamowienia')
                            ->options(OrderResource::statusOptions())
                            ->required()
                            ->native(false),
                        Select::make('payment_status')
                            ->label('Status platnosci')
                            ->options(OrderResource::paymentStatusOptions())
                            ->required()
                            ->native(false),
                        Select::make('fulfillment_status')
                            ->label('Status realizacji')
                            ->options(OrderResource::fulfillmentStatusOptions())
                            ->required()
                            ->native(false),
                        DateTimePicker::make('placed_at')
                            ->label('Data zlozenia')
                            ->default(now()),
                        Textarea::make('notes')
                            ->label('Notatki operacyjne')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('Pozycje zamówienia')->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('Pozycje')
                            ->columns(['md' => 7])
                            ->schema([
                                Placeholder::make('product_image')
                                    ->label('Zdjęcie')
                                    ->content(function ($get) {
                                        $productId = $get('product_id');
                                        if (! $productId) {
                                            return '-';
                                        }
                                        $product = \App\Models\Product::find($productId);
                                        if (! $product) {
                                            return '-';
                                        }
                                        $url = $product->featuredImageUrl();
                                        if (! $url) {
                                            return 'Brak';
                                        }
                                        return new \Illuminate\Support\HtmlString("<img src='{$url}' class='w-10 h-10 object-cover rounded shadow-sm border border-gray-200 dark:border-gray-800' />");
                                    })
                                    ->columnSpan(['md' => 1]),
                                Select::make('product_id')
                                    ->label('Produkt')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $product = \App\Models\Product::find($state);
                                        if ($product) {
                                            $set('name', $product->name);
                                            $set('sku', $product->sku);
                                            $set('product_type', $product->type->value ?? $product->type);
                                            $set('unit_price_amount', $product->price_amount / 100);
                                            $set('regular_unit_price_amount', $product->price_amount / 100);
                                            $set('total_amount', ($product->price_amount / 100) * 1);
                                        }
                                    })
                                    ->columnSpan(['md' => 3]),
                                TextInput::make('quantity')
                                    ->label('Ilość')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $price = $get('unit_price_amount') ?? 0;
                                        $set('total_amount', $price * $state);
                                    })
                                    ->columnSpan(['md' => 1]),
                                TextInput::make('unit_price_amount')
                                    ->label('Cena jedn. (PLN)')
                                    ->numeric()
                                    ->required()
                                    ->live()
                                    ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                    ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $qty = $get('quantity') ?? 1;
                                        $set('total_amount', $state * $qty);
                                    })
                                    ->columnSpan(['md' => 1]),
                                TextInput::make('total_amount')
                                    ->label('Suma (PLN)')
                                    ->numeric()
                                    ->required()
                                    ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                    ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null)
                                    ->columnSpan(['md' => 1]),
                                TextInput::make('name')
                                    ->required()
                                    ->hidden(),
                                TextInput::make('sku')
                                    ->hidden(),
                                TextInput::make('product_type')
                                    ->required()
                                    ->hidden(),
                            ])
                            ->defaultItems(0)
                            ->addable(true)
                            ->deletable(true)
                            ->reorderable(true)
                            ->columnSpanFull(),
                    ]),
                Section::make('Koszty zamówienia')->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('shipping_amount')
                            ->label('Koszt wysyłki (PLN)')
                            ->numeric()
                            ->default(0)
                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                        TextInput::make('discount_amount')
                            ->label('Kwota rabatu (PLN)')
                            ->numeric()
                            ->default(0)
                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                    ]),
                Section::make('Wysyłka i Śledzenie')->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('carrier')
                            ->label('Przewoźnik (np. InPost, DPD, DHL)')
                            ->maxLength(255),
                        TextInput::make('tracking_number')
                            ->label('Numer śledzenia paczki')
                            ->maxLength(255),
                        DateTimePicker::make('shipped_at')
                            ->label('Data wysłania paczki'),
                    ]),
                Section::make('Akcje fulfillmentu')->columnSpanFull()
                    ->schema([
                        Repeater::make('fulfillmentActions')
                            ->relationship()
                            ->label('Akcje')
                            ->columns(2)
                            ->schema([
                                Select::make('action_type')
                                    ->label('Typ')
                                    ->options(OrderResource::fulfillmentActionOptions())
                                    ->required()
                                    ->native(false),
                                Select::make('status')
                                    ->label('Status')
                                    ->options(OrderResource::fulfillmentStatusOptions())
                                    ->required()
                                    ->native(false),
                                TextInput::make('title')
                                    ->label('Tytul')
                                    ->required()
                                    ->maxLength(255),
                                DateTimePicker::make('due_at')
                                    ->label('Termin'),
                                DateTimePicker::make('completed_at')
                                    ->label('Zakonczono'),
                                Textarea::make('instructions')
                                    ->label('Instrukcje')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}