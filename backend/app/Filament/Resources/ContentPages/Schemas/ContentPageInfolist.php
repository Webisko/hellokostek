<?php

namespace App\Filament\Resources\ContentPages\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentPageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Strona')->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('title')->label('Tytul'),
                    TextEntry::make('slug')->label('Slug'),
                    TextEntry::make('template')->label('Szablon'),
                    TextEntry::make('is_active')
                        ->label('Aktywna')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('published_at')
                        ->label('Publikacja od')
                        ->dateTime('Y-m-d H:i')
                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('excerpt')
                        ->label('Lead')
                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                        ->columnSpanFull(),
                    TextEntry::make('content')
                        ->label('Tresc')
                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                        ->columnSpanFull(),
                ]),
            Section::make('Media')->columnSpanFull()
                ->schema([
                    TextEntry::make('hero_image_path')
                        ->label('Hero / obraz strony')
                        ->formatStateUsing(fn ($state, $record): string => filled($record->heroImageUrl()) ? (string) $record->heroImageUrl() : '-'),
                ]),
            Section::make('SEO i metadata')->columnSpanFull()
                ->schema([
                    TextEntry::make('seo_title')
                        ->label('Tytul SEO')
                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('seo_description')
                        ->label('Opis SEO')
                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    KeyValueEntry::make('metadata')
                        ->label('Metadane'),
                ]),
        ]);
    }
}