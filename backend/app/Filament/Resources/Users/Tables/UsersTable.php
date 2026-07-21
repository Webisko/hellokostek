<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Imię i nazwisko')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Adres e-mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Rola systemowa')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state?->value) {
                        'admin' => 'Administrator',
                        'manager' => 'Menedżer',
                        'employee' => 'Pracownik',
                        default => $state?->value,
                    })
                    ->color(fn ($state) => match ($state?->value) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'employee' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()->color('violet'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
