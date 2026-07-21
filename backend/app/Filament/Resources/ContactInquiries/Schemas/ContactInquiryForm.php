<?php

namespace App\Filament\Resources\ContactInquiries\Schemas;

use App\Models\ContactInquiry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactInquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Szczegóły wiadomości')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Imię i nazwisko')
                        ->disabled(),
                    TextInput::make('email')
                        ->label('Adres E-mail')
                        ->disabled(),
                    TextInput::make('phone')
                        ->label('Numer telefonu')
                        ->disabled(),
                    TextInput::make('subject')
                        ->label('Temat')
                        ->disabled(),
                    Textarea::make('message')
                        ->label('Treść wiadomości')
                        ->rows(6)
                        ->disabled()
                        ->columnSpanFull(),
                ]),
            Section::make('Dodatkowe dane (Formularz wielokrokowy / Brief)')->columnSpanFull()
                ->schema([
                    KeyValue::make('payload')
                        ->label('Przesłane parametry')
                        ->valueLabel('Wartość')
                        ->keyLabel('Klucz')
                        ->disabled()
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => !empty($record?->payload)),
            Section::make('Zarządzanie zapytaniem')->columnSpanFull()
                ->schema([
                    Select::make('status')
                        ->label('Status zgłoszenia')
                        ->options(ContactInquiry::getStatuses())
                        ->required()
                        ->native(false),
                    Textarea::make('admin_notes')
                        ->label('Notatki administratora')
                        ->placeholder('Wpisz wewnętrzne notatki dotyczące kontaktu z tym klientem...')
                        ->rows(4),
                ]),
            Section::make('Metadane połączenia')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('ip_address')
                        ->label('Adres IP')
                        ->disabled(),
                    TextInput::make('user_agent')
                        ->label('Przeglądarka (User Agent)')
                        ->disabled(),
                ])
                ->collapsed(),
        ]);
    }
}
