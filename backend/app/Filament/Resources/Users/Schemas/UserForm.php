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
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->withoutTrashed()),
                    ]),
                Section::make('Uprawnienia i zabezpieczenia')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('role')
                            ->label('Rola systemowa')
                            ->options(function (): array {
                                $options = [
                                    UserRole::Manager->value => 'Menedżer',
                                    UserRole::Employee->value => 'Pracownik',
                                ];

                                if (auth()->user()?->isAdmin()) {
                                    $options = [UserRole::Admin->value => 'Administrator'] + $options;
                                }

                                return $options;
                            })
                            ->required()
                            ->native(false),
                        TextInput::make('password')
                            ->label('Hasło')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->extraInputAttributes([
                                'data-1p-ignore' => 'true',
                                'data-lpignore' => 'true',
                                'data-bwignore' => 'true',
                            ])
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'Pozostaw puste, aby nie zmieniać obecnego hasła.'
                                : 'Minimum 8 znaków.')
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
