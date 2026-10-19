<?php

namespace App\Filament\Resources\TransactionalEmailLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionalEmailLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Wiadomość e-mail')->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('email_type')->label('Typ'),
                    TextEntry::make('recipient')->label('Adresat'),
                    TextEntry::make('subject')->label('Temat'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('sent_at')->label('Wysłany')->dateTime('Y-m-d H:i')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('order.number')->label('Zamówienie')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('error_message')->label('Błąd')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')->columnSpanFull(),
                    KeyValueEntry::make('payload')->label('Dane wiadomości')->columnSpanFull(),
                    KeyValueEntry::make('metadata')->label('Metadane')->columnSpanFull(),
                ]),
        ]);
    }
}