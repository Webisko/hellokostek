<?php

namespace App\Filament\Resources\IrydologyCases\Schemas;

use App\Models\IrydologyCase;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IrydologyCaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sprawa irydologii')
                ->columns(2)
                ->schema([
                    TextEntry::make('order.number')->label('Numer zamowienia')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('package_name')->label('Pakiet'),
                    TextEntry::make('customer_email')->label('E-mail klienta'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => IrydologyCase::statusLabel($state))
                        ->color(fn (?string $state): string => IrydologyCase::statusColor($state)),
                    TextEntry::make('instructions_sent_at')->label('Instrukcje wyslane')->dateTime('Y-m-d H:i')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('assets_received_at')->label('Zdjecia otrzymane')->dateTime('Y-m-d H:i')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('analysis_due_at')->label('Termin analizy')->dateTime('Y-m-d H:i')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('completed_at')->label('Zakonczono')->dateTime('Y-m-d H:i')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('notes')->label('Notatki')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')->columnSpanFull()->prose(),
                    KeyValueEntry::make('metadata')->label('Metadata')->columnSpanFull(),
                ]),
        ]);
    }
}