<?php

namespace App\Filament\Resources\FaqItems\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('FAQ')->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('question')->label('Pytanie')->columnSpanFull(),
                    TextEntry::make('group_name')->label('Grupa')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('sort_order')->label('Kolejnosc'),
                    TextEntry::make('is_active')->label('Aktywne')->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('answer')->label('Odpowiedz')->columnSpanFull(),
                    KeyValueEntry::make('metadata')->label('Metadata')->columnSpanFull(),
                ]),
        ]);
    }
}