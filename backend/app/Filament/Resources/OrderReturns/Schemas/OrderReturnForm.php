<?php

namespace App\Filament\Resources\OrderReturns\Schemas;

use App\Filament\Resources\OrderReturns\OrderReturnResource;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Szczegóły zwrotu')->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('return_number')
                            ->label('Numer zwrotu')
                            ->placeholder('Generowany automatycznie')
                            ->disabled()
                            ->dehydrated(fn ($state) => filled($state)),
                        Select::make('status')
                            ->label('Status')
                            ->options(OrderReturnResource::statusOptions())
                            ->required()
                            ->native(false),
                        
                        // Wybór zamówienia (tylko przy tworzeniu)
                        Select::make('order_id')
                            ->label('Zamówienie')
                            ->relationship('order', 'number')
                            ->required()
                            ->searchable()
                            ->live()
                            ->visible(fn ($context) => $context === 'create')
                            ->afterStateUpdated(function ($state, $set) {
                                $order = \App\Models\Order::find($state);
                                if ($order) {
                                    $set('user_id', $order->user_id);
                                }
                            }),
                        Placeholder::make('order_number')
                            ->label('Zamówienie')
                            ->content(fn ($record) => $record?->order?->number ?? '-')
                            ->visible(fn ($context) => $context !== 'create'),

                        // Wybór klienta (tylko przy tworzeniu)
                        Select::make('user_id')
                            ->label('Klient')
                            ->relationship('user', 'name', fn ($query) => $query->where('role', \App\Domain\Commerce\Enums\UserRole::Customer))
                            ->required()
                            ->searchable()
                            ->visible(fn ($context) => $context === 'create'),
                        Placeholder::make('user_name')
                            ->label('Klient')
                            ->content(fn ($record) => $record?->user?->name ?? '-')
                            ->visible(fn ($context) => $context !== 'create'),

                        TextInput::make('refund_amount')
                            ->label('Kwota zwrotu (PLN)')
                            ->numeric()
                            ->minValue(0)
                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                        TextInput::make('tracking_number')
                            ->label('Numer nadania paczki zwrotnej')
                            ->maxLength(255),
                        Textarea::make('reason')
                            ->label('Powód zwrotu')
                            ->readOnly(fn ($context) => $context !== 'create')
                            ->columnSpanFull(),
                    ]),
                Section::make('Zwracane produkty')->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('Pozycje zwrotu')
                            ->addable(fn ($context) => $context === 'create')
                            ->deletable(fn ($context) => $context === 'create')
                            ->reorderable(fn ($context) => $context === 'create')
                            ->columns(2)
                            ->schema([
                                Select::make('order_item_id')
                                    ->label('Produkt')
                                    ->options(fn ($get) => \App\Models\OrderItem::where('order_id', $get('../../order_id'))->pluck('name', 'id')->toArray())
                                    ->disabled(fn ($context) => $context !== 'create')
                                    ->dehydrated()
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('quantity')
                                    ->label('Ilość')
                                    ->numeric()
                                    ->disabled(fn ($context) => $context !== 'create')
                                    ->dehydrated()
                                    ->required()
                                    ->columnSpan(1),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
