<?php

namespace App\Filament\Resources\ProductReviews\Tables;

use App\Models\ProductReview;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('emoji')
                    ->label('Emoji')
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Autor')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('meta')
                    ->label('Opis / Kontekst')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('comment')
                    ->label('Treść opinii')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state, ProductReview $record) => $state === 'publiczny' || $record->is_approved ? 'Publiczny' : 'Szkic')
                    ->color(fn ($state, ProductReview $record) => $state === 'publiczny' || $record->is_approved ? 'emerald' : 'gray')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Data dodania')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')
                    ->slideOver()
                    ->modalWidth('2xl'),
                DeleteAction::make()->iconButton()->tooltip('Usuń'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
