<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Filament\Resources\Coupons\CouponResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Podstawowe')->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kod')
                            ->badge(),
                        TextEntry::make('name')
                            ->label('Nazwa')
                            ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                        TextEntry::make('discount_type')
                            ->label('Typ rabatu')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => CouponResource::formatDiscountTypeLabel($state))
                            ->color(fn ($state): string => CouponResource::discountTypeColor($state)),
                        TextEntry::make('value')
                            ->label('Wartość')
                            ->formatStateUsing(fn ($state, $record): string => CouponResource::formatDiscountValue($record->discount_type, $state, $record->currency)),
                        TextEntry::make('currency')
                            ->label('Waluta'),
                        TextEntry::make('is_active')
                            ->label('Aktywny')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                    ]),
                Section::make('Ograniczenia i wykorzystanie')->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('minimum_subtotal_amount')
                            ->label('Minimalna kwota koszyka')
                            ->formatStateUsing(fn ($state, $record): string => $state !== null ? CouponResource::formatDiscountValue('fixed_cart', $state, $record->currency) : '-'),
                        TextEntry::make('usage_limit')
                            ->label('Limit użyć')
                            ->formatStateUsing(fn ($state): string => $state !== null ? (string) $state : '-'),
                        TextEntry::make('usage_limit_per_customer')
                            ->label('Limit na klienta')
                            ->formatStateUsing(fn ($state): string => $state !== null ? (string) $state : '-'),
                        TextEntry::make('starts_at')
                            ->label('Aktywny od')
                            ->dateTime('Y-m-d H:i')
                            ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                        TextEntry::make('ends_at')
                            ->label('Aktywny do')
                            ->dateTime('Y-m-d H:i')
                            ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                        TextEntry::make('orders_count')
                            ->label('Zastosowany w zamówieniach'),
                    ]),
                Section::make('Metadane')->columnSpanFull()
                    ->schema([
                        KeyValueEntry::make('metadata')
                            ->label('Metadane'),
                    ]),
            ]);
    }
}