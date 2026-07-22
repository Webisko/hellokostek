<?php

namespace App\Filament\Resources\ProductAttributes\Schemas;

use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductAttributeInfolist
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
                        TextEntry::make('value_type')
                            ->label('Typ wartosci')
                            ->formatStateUsing(fn (?string $state): string => ProductAttributeResource::valueTypeOptions()[$state] ?? (string) $state),
                        TextEntry::make('sort_order')
                            ->label('Kolejnosc'),
                        TextEntry::make('is_active')
                            ->label('Aktywny')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                        TextEntry::make('product_values_count')
                            ->label('Przypisania do produktow'),
                    ]),
                Section::make('Powiazania')->columnSpanFull()
                    ->schema([
                        TextEntry::make('categories.name')
                            ->label('Kategorie')
                            ->listWithLineBreaks()
                            ->badge(),
                    ]),
            ]);
    }
}