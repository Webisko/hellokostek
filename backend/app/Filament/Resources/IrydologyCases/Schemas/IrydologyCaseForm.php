<?php

namespace App\Filament\Resources\IrydologyCases\Schemas;

use App\Models\IrydologyCase;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IrydologyCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sprawa irydologii')
                ->columns(2)
                ->schema([
                    TextInput::make('package_name')->label('Pakiet')->disabled()->dehydrated(false),
                    TextInput::make('customer_email')->label('E-mail klienta')->disabled()->dehydrated(false),
                    Select::make('status')
                        ->label('Status')
                        ->options(IrydologyCase::statusOptions())
                        ->required()
                        ->native(false)
                        ->helperText('Przy zmianie statusu panel moze automatycznie uzupelnic brakujace daty procesu.'),
                    DateTimePicker::make('instructions_sent_at')->label('Instrukcje wyslane'),
                    DateTimePicker::make('assets_received_at')->label('Zdjecia otrzymane'),
                    DateTimePicker::make('analysis_due_at')->label('Termin analizy'),
                    DateTimePicker::make('completed_at')->label('Zakonczono'),
                    Textarea::make('notes')->label('Notatki')->rows(5)->columnSpanFull(),
                    KeyValue::make('metadata')->label('Metadata')->columnSpanFull(),
                ]),
        ]);
    }
}