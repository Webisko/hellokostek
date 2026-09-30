<?php

namespace App\Filament\Resources\ContactInquiries\Tables;

use App\Models\ContactInquiry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactInquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordAction('edit')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data otrzymania')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Imię i nazwisko')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject')
                    ->label('Temat')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'danger' => 'new',
                        'warning' => 'in_progress',
                        'info' => 'accepted',
                        'success' => 'completed',
                        'gray' => 'archived',
                    ])
                    ->formatStateUsing(fn (string $state): string => ContactInquiry::getStatuses()[$state] ?? $state)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContactInquiry::getStatuses()),
            ])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Obsłuż')->color('violet')
                    ->label('Obsłuż')
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
