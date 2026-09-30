<?php

namespace App\Filament\Resources\StoreSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoreSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction('edit')
            ->columns([
                TextColumn::make('store_name')->label('Sklep')->sortable(),
                TextColumn::make('currency')->label('Waluta'),
                TextColumn::make('free_shipping_threshold')->label('Próg darmowej dostawy'),
                TextColumn::make('updated_at')->label('Aktualizacja')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')
                    ->slideOver()
                    ->modalWidth('7xl'),
            ]);
    }
}