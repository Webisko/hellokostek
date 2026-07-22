<?php

namespace App\Filament\Resources\GalleryArtworks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GalleryArtworkTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Zdjęcie')
                    ->rounded()
                    ->disk('public'),

                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategoria')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('year')
                    ->label('Rok')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktywny'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktywny'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edytuj')
                    ->color('violet')
                    ->slideOver()
                    ->modalWidth('3xl'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Usuń'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
