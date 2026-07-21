<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\Products\ProductResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            Section::make('Podstawowe')->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Nazwa')
                                        ->weight('bold')
                                        ->icon('heroicon-o-gift')
                                        ->copyable(),
                                    TextEntry::make('slug')
                                        ->label('Slug')
                                        ->copyable()
                                        ->color('gray'),
                                    TextEntry::make('sku')
                                        ->label('SKU')
                                        ->copyable()
                                        ->icon('heroicon-o-tag')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                    TextEntry::make('type')
                                        ->label('Typ')
                                        ->badge()
                                        ->formatStateUsing(fn ($state): string => ProductResource::formatStateLabel($state))
                                        ->color(fn ($state): string => ProductResource::typeColor($state))
                                        ->icon(fn ($state): ?string => match ($state) {
                                            'physical' => 'heroicon-o-archive-box',
                                            'digital' => 'heroicon-o-arrow-down-tray',
                                            'service' => 'heroicon-o-wrench-screwdriver',
                                            default => null,
                                        }),
                                    TextEntry::make('categories.name')
                                        ->label('Kategorie')
                                        ->listWithLineBreaks()
                                        ->badge(),
                                    TextEntry::make('published_at')
                                        ->label('Publikacja od')
                                        ->dateTime('Y-m-d H:i')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                    TextEntry::make('short_description')
                                        ->label('Krótki opis')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                                        ->columnSpanFull(),
                                    TextEntry::make('description')
                                        ->label('Opis')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Cena i storefront')->columnSpanFull()
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('regular_price_amount')
                                        ->label('Cena regularna')
                                        ->money(fn ($record): string => $record->currency ?: 'PLN', divideBy: 100)
                                        ->weight('bold'),
                                    TextEntry::make('sale_price_amount')
                                        ->label('Cena promocyjna')
                                        ->money(fn ($record): string => $record->currency ?: 'PLN', divideBy: 100)
                                        ->color('success')
                                        ->weight('bold')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                    TextEntry::make('currency')
                                        ->label('Waluta'),
                                    TextEntry::make('vat_rate')
                                        ->label('Stawka VAT')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? $state . '%' : '23%'),
                                    TextEntry::make('manages_stock')
                                        ->label('Zarządzanie magazynem')
                                        ->badge()
                                        ->color(fn ($state): string => $state ? 'success' : 'gray')
                                        ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                                    TextEntry::make('stock_quantity')
                                        ->label('Stan magazynowy')
                                        ->badge()
                                        ->color(fn ($record): string => $record->manages_stock 
                                            ? ($record->stock_quantity <= 0 ? 'danger' : ($record->stock_quantity <= 5 ? 'warning' : 'success'))
                                            : 'gray'
                                        )
                                        ->formatStateUsing(fn ($state, $record): string => $record->manages_stock ? (string) ($state ?? 0) : 'N/A'),
                                    TextEntry::make('is_active')
                                        ->label('Aktywny')
                                        ->badge()
                                        ->color(fn ($state): string => $state ? 'success' : 'gray')
                                        ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                                    TextEntry::make('is_visible')
                                        ->label('Widoczny')
                                        ->badge()
                                        ->color(fn ($state): string => $state ? 'success' : 'gray')
                                        ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                                    TextEntry::make('is_purchasable')
                                        ->label('Kupowalny')
                                        ->badge()
                                        ->color(fn ($state): string => $state ? 'success' : 'gray')
                                        ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                                ]),
                            Section::make('SEO i metadata')->columnSpanFull()
                                ->columns(1)
                                ->schema([
                                    TextEntry::make('seo_title')
                                        ->label('Tytuł SEO')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                    TextEntry::make('seo_description')
                                        ->label('Opis SEO')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                    KeyValueEntry::make('metadata')
                                        ->label('Metadane'),
                                    TextEntry::make('manual_tags')
                                        ->label('Ręczne tagi')
                                        ->listWithLineBreaks()
                                        ->badge(),
                                ]),
                        ])->columnSpan(2),

                        Group::make([
                            Section::make('Media')->columnSpanFull()
                                ->schema([
                                    TextEntry::make('featured_image_html')
                                        ->label('Główny obraz produktu')
                                        ->html()
                                        ->state(fn ($record): string => filled($record->featuredImageUrl()) 
                                            ? sprintf('<img src="%s" class="w-full h-auto object-cover rounded-lg shadow-sm border border-gray-200 dark:border-gray-800" />', e($record->featuredImageUrl())) 
                                            : '-'
                                        ),
                                    TextEntry::make('gallery_image_paths_html')
                                        ->label('Galeria zdjęć')
                                        ->html()
                                        ->state(function ($record): string {
                                            $urls = $record->galleryImageUrls() ?? [];
                                            if (empty($urls)) return '-';
                                            $html = '<div class="flex flex-wrap gap-2">';
                                            foreach ($urls as $url) {
                                                $html .= sprintf('<img src="%s" class="w-16 h-16 object-cover rounded border border-gray-200 dark:border-gray-800 shadow-xs" />', e($url));
                                            }
                                            $html .= '</div>';
                                            return $html;
                                        }),
                                ]),
                            Section::make('Organizacja')->columnSpanFull()
                                ->schema([
                                    TextEntry::make('attributeValuesSummary')
                                        ->label('Atrybuty')
                                        ->listWithLineBreaks()
                                        ->badge(),
                                    TextEntry::make('marketingBadgeLabels')
                                        ->label('Oznaczenia marketingowe')
                                        ->listWithLineBreaks()
                                        ->badge(),
                                    TextEntry::make('homepagePlacementLabels')
                                        ->label('Sekcje strony głównej')
                                        ->listWithLineBreaks()
                                        ->badge(),
                                ]),
                            Section::make('Wymiary i waga')->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('weight')
                                        ->label('Waga')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? $state . ' kg' : '-'),
                                    TextEntry::make('metadata.width')
                                        ->label('Szerokość')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? $state . ' cm' : '-'),
                                    TextEntry::make('metadata.height')
                                        ->label('Wysokość')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? $state . ' cm' : '-'),
                                    TextEntry::make('metadata.depth')
                                        ->label('Głębokość')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? $state . ' cm' : '-'),
                                ]),
                        ])->columnSpan(1),
                    ])
            ]);
    }
}