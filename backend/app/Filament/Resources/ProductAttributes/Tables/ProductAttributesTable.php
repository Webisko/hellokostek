<?php

namespace App\Filament\Resources\ProductAttributes\Tables;

use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductAttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->recordAction('view')
            ->columns([
                TextColumn::make('name')
                    ->label('Nazwa')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('value_type')
                    ->label('Typ wartości')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ProductAttributeResource::valueTypeOptions()[$state] ?? (string) $state)
                    ->icon(fn (?string $state): ?string => match ($state) {
                        'text' => 'heroicon-o-pencil-square',
                        'number' => 'heroicon-o-hashtag',
                        'select' => 'heroicon-o-list-bullet',
                        'boolean' => 'heroicon-o-check',
                        'color' => 'heroicon-o-swatch',
                        default => null,
                    }),
                TextColumn::make('sort_order')
                    ->label('Kolejność')
                    ->sortable(),
                TextColumn::make('categories.name')
                    ->label('Kategorie')
                    ->listWithLineBreaks()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('product_values_count')
                    ->label('Przypisania')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktywny')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('value_type')
                    ->label('Typ wartości')
                    ->options(ProductAttributeResource::valueTypeOptions()),
                TernaryFilter::make('is_active')
                    ->label('Aktywny'),
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
                            ->cancelParentActions(),
                    ]),
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')
                    ->slideOver()
                    ->modalWidth('7xl'),
                DeleteAction::make()->iconButton()->tooltip('Usuń'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}