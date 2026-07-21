<?php

namespace App\Filament\Resources\OrderReturns\Tables;

use App\Filament\Resources\OrderReturns\OrderReturnResource;
use App\Models\OrderReturn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordAction('view')
            ->columns([
                TextColumn::make('return_number')
                    ->label('Numer zwrotu')
                    ->badge()
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.number')
                    ->label('Numer zamówienia')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Klient')
                    ->icon('heroicon-o-user')
                    ->iconColor('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OrderReturnResource::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => OrderReturnResource::statusColor($state))
                    ->icon(fn (string $state): ?string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'approved' => 'heroicon-o-check-circle',
                        'received' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        default => null,
                    })
                    ->sortable(),
                TextColumn::make('refund_amount')
                    ->label('Kwota zwrotu')
                    ->formatStateUsing(fn ($state, OrderReturn $record): string => $state !== null ? number_format($state / 100, 2, ',', ' ') . ' ' . ($record->order->currency ?? 'PLN') : '-')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Data zgłoszenia')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(OrderReturnResource::statusOptions()),
            ])
            ->recordActions([
                ViewAction::make()->extraAttributes(['style' => 'display: none !important;'])
                    ->iconButton()
                    ->tooltip('Podgląd')
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->mutateRecordDataUsing(function (array $data, OrderReturn $record): array {
                        $data['items'] = $record->items->map(fn ($item) => [
                            'order_item_id' => $item->order_item_id,
                            'quantity' => $item->quantity,
                        ])->toArray();
                        return $data;
                    })
                    ->extraModalFooterActions([
                        EditAction::make()
                            ->button()
                            ->label('Edytuj')
                            ->slideOver()
                            ->modalWidth('7xl')
                            ->mutateRecordDataUsing(function (array $data, OrderReturn $record): array {
                                $data['items'] = $record->items->map(fn ($item) => [
                                    'order_item_id' => $item->order_item_id,
                                    'quantity' => $item->quantity,
                                ])->toArray();
                                return $data;
                            })
                            ->cancelParentActions(),
                    ]),
                EditAction::make()->color('violet')
                    ->iconButton()
                    ->tooltip('Edytuj')
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->mutateRecordDataUsing(function (array $data, OrderReturn $record): array {
                        $data['items'] = $record->items->map(fn ($item) => [
                            'order_item_id' => $item->order_item_id,
                            'quantity' => $item->quantity,
                        ])->toArray();
                        return $data;
                    }),
                DeleteAction::make()->iconButton()->tooltip('Usuń'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
