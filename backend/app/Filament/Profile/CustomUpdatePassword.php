<?php

namespace App\Filament\Profile;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword;

class CustomUpdatePassword extends UpdatePassword
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label(__('filament-breezy::default.password_confirm.current_password'))
                    ->required()
                    ->password()
                    ->revealable()
                    ->autocomplete('current-password')
                    ->rule('current_password')
                    ->visible(filament('filament-breezy')->getPasswordUpdateRequiresCurrent()),
                TextInput::make('new_password')
                    ->label(__('filament-breezy::default.fields.new_password'))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->helperText('Minimum 8 znaków.')
                    ->rules(filament('filament-breezy')->getPasswordUpdateRules())
                    ->required(),
                TextInput::make('new_password_confirmation')
                    ->label(__('filament-breezy::default.fields.new_password_confirmation'))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->same('new_password')
                    ->required(),
            ])
            ->statePath('data');
    }
}
