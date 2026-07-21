<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dane podstawowe')->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Imie i nazwisko')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Hasło')
                            ->password()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ]),
                Section::make('Profil klienta')->columnSpanFull()
                    ->relationship('customerProfile')
                    ->columns(2)
                    ->schema([
                        Select::make('segment')
                            ->label('Segment')
                            ->options(CustomerResource::segmentOptions())
                            ->required()
                            ->default('regular')
                            ->native(false),
                        TextInput::make('phone')
                            ->label('Telefon')
                            ->maxLength(50),
                        TextInput::make('completed_orders_count')
                            ->label('Zakonczone zamowienia')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0),
                        DateTimePicker::make('last_order_at')
                            ->label('Ostatnie zamowienie'),
                        DateTimePicker::make('marketing_consent_at')
                            ->label('Zgoda marketingowa od')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}