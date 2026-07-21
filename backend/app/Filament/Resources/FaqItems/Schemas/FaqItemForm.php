<?php

namespace App\Filament\Resources\FaqItems\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('FAQ')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('question')
                        ->label('Pytanie')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('answer')
                        ->label('Odpowiedz')
                        ->required()
                        ->rows(8)
                        ->columnSpanFull(),
                    TextInput::make('group_name')
                        ->label('Grupa')
                        ->maxLength(255),
                    TextInput::make('sort_order')
                        ->label('Kolejnosc')
                        ->numeric()
                        ->required()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Aktywne')
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