<?php

namespace App\Filament\Resources\RedirectRules\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RedirectRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Redirect')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('source_path')
                        ->label('Stary adres')
                        ->required()
                        ->maxLength(2048)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? '/' . ltrim(trim($state), '/') : null),
                    TextInput::make('target_path')
                        ->label('Nowy adres')
                        ->required()
                        ->maxLength(2048),
                    Select::make('status_code')
                        ->label('Status HTTP')
                        ->options([
                            301 => '301 Permanent',
                            302 => '302 Temporary',
                        ])
                        ->required()
                        ->default(301)
                        ->native(false),
                    Toggle::make('is_active')
                        ->label('Aktywny')
                        ->default(true),
                    KeyValue::make('metadata')
                        ->label('Metadata')
                        ->keyLabel('Klucz')
                        ->valueLabel('Wartosc')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}