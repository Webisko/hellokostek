<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Domain\Commerce\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dane podstawowe')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Imię i nazwisko')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Adres e-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make('Uprawnienia i zabezpieczenia')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('role')
                            ->label('Rola systemowa')
                            ->options([
                                UserRole::Admin->value => 'Administrator',
                                UserRole::Manager->value => 'Menedżer',
                                UserRole::Employee->value => 'Pracownik',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('password')
                            ->label('Hasło')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
