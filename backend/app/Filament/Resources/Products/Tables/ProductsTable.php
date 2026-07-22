<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->recordAction('view')
            ->columns([
                ImageColumn::make('featured_image_path')
                    ->label('Zdjęcie')
                    ->rounded()
                    ->disk('public'),
                TextColumn::make('name')
                    ->label('Nazwa')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Adres URL (slug)')
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->icon('heroicon-o-tag')
                    ->iconColor('gray')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ProductResource::formatStateLabel($state))
                    ->color(fn ($state): string => ProductResource::typeColor($state))
                    ->icon(fn ($state): ?string => match ($state) {
                        'physical' => 'heroicon-o-archive-box',
                        'digital' => 'heroicon-o-arrow-down-tray',
                        'service' => 'heroicon-o-wrench-screwdriver',
                        default => null,
                    })
                    ->sortable(),
                TextColumn::make('categories.name')
                    ->label('Kategorie')
                    ->listWithLineBreaks()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('attribute_values_count')
                    ->label('Atrybuty')
                    ->counts('attributeValues')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('marketingBadgeLabels')
                    ->label('Oznaczenia')
                    ->listWithLineBreaks()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('current_price_amount')
                    ->label('Cena (Wydruk / Oryginał)')
                    ->html()
                    ->state(function (Product $record): string {
                        $record->loadMissing('variants');
                        $printVariant = $record->variants->firstWhere(fn ($v) => str_ends_with($v->sku ?? '', '-PR'));
                        $origVariant = $record->variants->firstWhere(fn ($v) => str_ends_with($v->sku ?? '', '-OR'));

                        $printPrice = $printVariant ? ($printVariant->regular_price_amount / 100) : ($record->regular_price_amount / 100);
                        $html = '<div><span class="font-bold text-gray-800 dark:text-gray-200">Wydruk: ' . number_format($printPrice, 2, ',', ' ') . ' ' . $record->currency . '</span></div>';

                        if ($origVariant && $origVariant->is_active) {
                            if ($origVariant->stock_quantity > 0) {
                                $origPrice = $origVariant->regular_price_amount / 100;
                                $html .= '<div><span class="text-xs font-semibold text-amber-600 dark:text-amber-400">Oryginał: ' . number_format($origPrice, 2, ',', ' ') . ' ' . $record->currency . '</span></div>';
                            } else {
                                $html .= '<div><span class="text-xs text-gray-400 line-through">Oryginał: Sprzedany</span></div>';
                            }
                        }

                        return $html;
                    })
                    ->sortable(query: fn ($query, string $direction) => $query->orderByRaw('COALESCE(sale_price_amount, regular_price_amount) ' . $direction)),
                TextColumn::make('stock_quantity')
                    ->label('Stan')
                    ->badge()
                    ->color(fn (Product $record): string => $record->manages_stock 
                        ? ($record->stock_quantity <= 0 ? 'danger' : ($record->stock_quantity <= 5 ? 'warning' : 'success'))
                        : 'gray'
                    )
                    ->icon(fn (Product $record): ?string => $record->manages_stock 
                        ? ($record->stock_quantity <= 0 ? 'heroicon-o-x-circle' : ($record->stock_quantity <= 5 ? 'heroicon-o-exclamation-circle' : 'heroicon-o-check-circle'))
                        : null
                    )
                    ->state(fn (Product $record): string => $record->manages_stock ? (string) ($record->stock_quantity ?? 0) : 'N/A')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktywny')
                    ->boolean(),
                IconColumn::make('is_visible')
                    ->label('Widoczny')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Publikacja')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Typ produktu')
                    ->options(ProductResource::typeOptions()),
                SelectFilter::make('categories')
                    ->label('Kategoria')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Aktywny'),
                TernaryFilter::make('show_on_homepage')
                    ->label('Na stronie głównej'),
                TernaryFilter::make('is_bestseller')
                    ->label('Bestseller'),
                TernaryFilter::make('is_new')
                    ->label('Nowość'),
                TernaryFilter::make('is_recommended')
                    ->label('Polecany'),
                TernaryFilter::make('is_visible')
                    ->label('Widoczny'),
                \Filament\Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Podgląd')->extraAttributes(['style' => 'display: none !important;'])
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->extraModalFooterActions([
                        EditAction::make()
                            ->button()
                            ->label('Edytuj')
                            ->slideOver()
                            ->modalWidth('7xl')
                            ->after(fn (Product $record, array $data) => Product::syncVariantsFromData($record, $data))
                            ->cancelParentActions(),
                    ]),
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->after(fn (Product $record, array $data) => Product::syncVariantsFromData($record, $data)),
                DeleteAction::make()->iconButton()->tooltip('Usuń'),
                \Filament\Actions\RestoreAction::make()->iconButton()->tooltip('Przywróć'),
                \Filament\Actions\ForceDeleteAction::make()->iconButton()->tooltip('Usuń trwale'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}