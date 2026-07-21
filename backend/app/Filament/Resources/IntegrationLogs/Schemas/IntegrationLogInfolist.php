<?php

namespace App\Filament\Resources\IntegrationLogs\Schemas;

use App\Models\IntegrationLog;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IntegrationLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Log integracji')->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('integration')
                        ->label('Integracja')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => IntegrationLog::integrationLabel($state)),
                    TextEntry::make('event')
                        ->label('Zdarzenie')
                        ->formatStateUsing(fn (?string $state): string => IntegrationLog::eventLabel($state))
                        ->helperText(fn (IntegrationLog $record): string => 'Kod techniczny: ' . $record->event),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => IntegrationLog::statusLabel($state))
                        ->color(fn (?string $state): string => IntegrationLog::statusColor($state)),
                    TextEntry::make('direction')
                        ->label('Kierunek')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => IntegrationLog::directionLabel($state))
                        ->color(fn (?string $state): string => IntegrationLog::directionColor($state)),
                    TextEntry::make('order.number')->label('Zamowienie')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('external_reference')->label('Referencja')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('occurred_at')->label('Zdarzenie')->dateTime('Y-m-d H:i')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('error_message')->label('Blad')->columnSpanFull()->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    KeyValueEntry::make('request_payload')->label('Request payload')->columnSpanFull(),
                    KeyValueEntry::make('response_payload')->label('Response payload')->columnSpanFull(),
                    KeyValueEntry::make('metadata')->label('Metadata')->columnSpanFull(),
                ]),
        ]);
    }
}