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
                    TextEntry::make('title')->label('Tytuł'),
                    TextEntry::make('slug')->label('Adres URL (slug)'),
                    TextEntry::make('template')->label('Szablon'),
                    TextEntry::make('is_active')
                        ->label('Aktywna')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('published_at')
                        ->label('Publikacja od')
                        ->dateTime('Y-m-d H:i')
                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('excerpt')
                        ->label('Krótki opis / zajawka')
                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                        ->columnSpanFull(),
                    TextEntry::make('content')
                        ->label('Treść')
                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                        ->columnSpanFull(),
                ]),
            Section::make('Multimedia')->columnSpanFull()
                ->schema([
                    TextEntry::make('hero_image_path')
                        ->label('Obraz wyróżniający strony')
                        ->formatStateUsing(fn ($state, $record): string => filled($record->heroImageUrl()) ? (string) $record->heroImageUrl() : '-'),
                ]),
            Section::make('SEO i metadane')->columnSpanFull()
                ->schema([
                    TextEntry::make('seo_title')
                        ->label('Tytuł SEO')
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