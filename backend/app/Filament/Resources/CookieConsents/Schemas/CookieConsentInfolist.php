<?php

namespace App\Filament\Resources\CookieConsents\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CookieConsentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Log Zgody Cookies')->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('consent_token')->label('Token zgody'),
                    TextEntry::make('banner_version')->label('Wersja banera'),
                    TextEntry::make('created_at')->label('Zapisano')->dateTime('Y-m-d H:i'),
                    TextEntry::make('user_agent')->label('Przeglądarka (User Agent)')->columnSpanFull(),
                    KeyValueEntry::make('consent_choices')->label('Wybory cookies (Zgody)')->columnSpanFull(),
                ]),
        ]);
    }
}
