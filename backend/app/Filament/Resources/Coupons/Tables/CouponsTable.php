<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Filament\Resources\Coupons\CouponResource;
use App\Models\Coupon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordAction('view')
            ->columns([
                TextColumn::make('code')
                    ->label('Kod')
                    ->badge()
                    ->icon('heroicon-o-ticket')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('discount_type')
                    ->label('Typ rabatu')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CouponResource::formatDiscountTypeLabel($state))
                    ->color(fn ($state): string => CouponResource::discountTypeColor($state))
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Wartość')
                    ->formatStateUsing(fn ($state, Coupon $record): string => CouponResource::formatDiscountValue($record->discount_type, $state, $record->currency)),
                TextColumn::make('minimum_subtotal_amount')
                    ->label('Min. subtotal')
                    ->formatStateUsing(fn ($state, Coupon $record): string => $state !== null ? CouponResource::formatDiscountValue('fixed_cart', $state, $record->currency) : '-')
                    ->toggleable(),
                TextColumn::make('orders_count')
                    ->label('Zamówienia')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktywny')
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->label('Od')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(),
                TextColumn::make('ends_at')
                    ->label('Do')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('discount_type')
                    ->label('Typ rabatu')
                    ->options(CouponResource::discountTypeOptions()),
                TernaryFilter::make('is_active')
                    ->label('Aktywny'),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Podgląd')->extraAttributes(['style' => 'display: none !important;'])
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->extraModalFooterActions([
                        EditAction::make()
                            ->button()
                            ->label('Edytuj')
                            ->slideOver()
                            ->modalWidth('7xl')
                            ->cancelParentActions(),
                    ]),
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')->slideOver()->modalWidth('7xl'),
                DeleteAction::make()->iconButton()->tooltip('Usuń'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}