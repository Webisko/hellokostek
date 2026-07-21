<?php

namespace App\Filament\Resources\EmailTemplates\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nazwa szablonu')->sortable()->searchable(),
                TextColumn::make('key')->label('Klucz systemowy')->sortable()->searchable(),
                TextColumn::make('subject')->label('Temat wiadomości')->searchable(),
                TextColumn::make('updated_at')->label('Aktualizacja')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')
                    ->slideOver()
                    ->modalWidth('5xl'),
            ])
            ->actions([
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')
                    ->slideOver()
                    ->modalWidth('5xl'),
            ]);
    }
}
