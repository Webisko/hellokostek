<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Podstawowe')->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nazwa'),
                        TextEntry::make('slug')
                            ->label('Adres URL (slug)'),
                        TextEntry::make('sort_order')
                            ->label('Kolejnosc'),
                        TextEntry::make('is_active')
                            ->label('Aktywna')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                        TextEntry::make('description')
                            ->label('Opis')
                            ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                            ->columnSpanFull(),
                    ]),
                Section::make('SEO i produkty')->columnSpanFull()
                    ->schema([
                        TextEntry::make('seo_title')
                            ->label('Tytul SEO')
                            ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                        TextEntry::make('seo_description')
                            ->label('Opis SEO')
                            ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                        TextEntry::make('products.name')
                            ->label('Produkty')
                            ->listWithLineBreaks()
                            ->badge(),
                        TextEntry::make('attributes.name')
                            ->label('Atrybuty')
                            ->listWithLineBreaks()
                            ->badge(),
                    ]),
            ]);
    }
}