<?php

namespace App\Filament\Resources\ProductReviews\Tables;

use App\Models\ProductReview;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(fn (\Filament\Actions\Action $action) => $action->tooltip('Zmień kolejność opinii (przeciągnij i upuść)'))
            ->recordAction('edit')
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
                    ->label('Opis / kontekst')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('comment')
                    ->label('Treść opinii')
                    ->limit(60)
                    ->searchable(),
                ToggleColumn::make('is_approved')
                    ->label('Opublikowana')
                    ->getStateUsing(fn (ProductReview $record): bool => $record->status === 'publiczny' || (bool) $record->is_approved)
                    ->updateStateUsing(function (ProductReview $record, bool $state): bool {
                        $record->forceFill([
                            'is_approved' => $state,
                            'status' => $state ? 'publiczny' : 'szkic',
                        ])->save();

                        return $state;
                    }),
                TextColumn::make('created_at')
                    ->label('Data dodania')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label('Widoczność')
                    ->placeholder('Wszystkie opinie')
                    ->trueLabel('Opublikowane')
                    ->falseLabel('Szkice (oczekujące)')
                    ->queries(
                        true: fn ($query) => $query->where(fn ($q) => $q->where('is_approved', true)->orWhere('status', 'publiczny')),
                        false: fn ($query) => $query->where(fn ($q) => $q->where('is_approved', false)->where('status', '!=', 'publiczny')),
                    ),
            ])
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
