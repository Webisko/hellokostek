<?php

namespace App\Filament\Resources\AdminActivityLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdminActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Data')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('actor.name')->label('Operator')->searchable(),
                TextColumn::make('event')->label('Zdarzenie')->badge()->sortable(),
                TextColumn::make('subject_type')->label('Typ obiektu')->toggleable(),
                TextColumn::make('summary')->label('Podsumowanie')->searchable()->limit(100),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Zdarzenie')
                    ->options([
                        'order_updated' => 'order_updated',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Podgląd')->extraAttributes(['style' => 'display: none !important;'])
                    ->slideOver()
                    ->modalWidth('5xl'),
            ]);
    }
}