<?php

namespace App\Filament\Resources\TransactionalEmailLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionalEmailLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email_type')->label('Typ')->badge()->sortable(),
                TextColumn::make('recipient')->label('Adresat')->searchable(),
                TextColumn::make('subject')->label('Temat')->searchable()->toggleable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('order.number')->label('Zamówienie')->toggleable(),
                TextColumn::make('sent_at')->label('Wysłany')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'pending',
                        'sent' => 'sent',
                        'failed' => 'failed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Podgląd')->extraAttributes(['style' => 'display: none !important;'])
                    ->slideOver()
                    ->modalWidth('5xl'),
            ]);
    }
}