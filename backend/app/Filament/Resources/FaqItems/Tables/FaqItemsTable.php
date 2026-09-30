<?php

namespace App\Filament\Resources\FaqItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FaqItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(fn (\Filament\Actions\Action $action) => $action->tooltip('Zmień kolejność pytań (przeciągnij i upuść)'))
            ->recordAction('edit')
            ->columns([
                TextColumn::make('question')->label('Pytanie')->searchable(),
                TextColumn::make('group_name')->label('Grupa')->toggleable(),
                IconColumn::make('is_active')->label('Aktywne')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktywne'),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')->slideOver(),
                DeleteAction::make()->iconButton()->tooltip('Usuń'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}