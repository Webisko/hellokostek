<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Szczegóły szablonu')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nazwa szablonu')
                        ->disabled()
                        ->columnSpan(1),
                    TextInput::make('key')
                        ->label('Klucz systemowy')
                        ->disabled()
                        ->columnSpan(1),
                    TextInput::make('subject')
                        ->label('Temat wiadomości')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    RichEditor::make('body_html')
                        ->label('Treść HTML')
                        ->required()
                        ->columnSpanFull(),
                ]),
            Section::make('Dostępne zmienne (Placeholders)')->columnSpanFull()
                ->description('Użyj poniższych kluczy w temacie lub treści wiadomości, a zostaną one automatycznie zastąpione odpowiednimi wartościami.')
                ->schema([
                    KeyValue::make('placeholders')
                        ->label('Dostępne zmienne')
                        ->keyLabel('Zmienna')
                        ->valueLabel('Opis')
                        ->disabled()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
