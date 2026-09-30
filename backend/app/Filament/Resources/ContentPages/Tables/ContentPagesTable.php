<?php

namespace App\Filament\Resources\ContentPages\Tables;

use App\Models\ContentPage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContentPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(fn (\Filament\Actions\Action $action) => $action->tooltip('Zmień kolejność stron (przeciągnij i upuść)'))
            ->recordAction('edit')
            ->columns([
                TextColumn::make('title')
                    ->label('Tytuł strony')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Adres URL (slug)')
                    ->searchable()
                    ->prefix('/'),
                TextColumn::make('template')
                    ->label('Szablon')
                    ->formatStateUsing(fn (?string $state): string => ContentPage::templateOptions()[$state] ?? (string) $state)
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktywna')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Ostatnia zmiana')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')
                    ->slideOver()
                    ->modalWidth('3xl'),
                DeleteAction::make()->iconButton()->tooltip('Usuń'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}