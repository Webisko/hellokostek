<?php

namespace App\Filament\Resources\RedirectRules\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RedirectRuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Redirect')->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('source_path')->label('Stary adres'),
                    TextEntry::make('target_path')->label('Nowy adres'),
                    TextEntry::make('status_code')->label('Status HTTP'),
                    TextEntry::make('is_active')->label('Aktywny')->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('hit_count')->label('Trafienia'),
                    TextEntry::make('last_hit_at')->label('Ostatnie trafienie')->dateTime('Y-m-d H:i')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    KeyValueEntry::make('metadata')->label('Metadata')->columnSpanFull(),
                ]),
        ]);
    }
}