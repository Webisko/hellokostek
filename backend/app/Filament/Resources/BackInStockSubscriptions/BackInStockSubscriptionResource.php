<?php

namespace App\Filament\Resources\BackInStockSubscriptions;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\BackInStockSubscriptions\Pages\ListBackInStockSubscriptions;
use App\Models\BackInStockSubscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class BackInStockSubscriptionResource extends Resource
{
    protected static ?string $slug = 'powiadomienia-o-dostepnosci';
    use HasDynamicNavigation;
    protected static ?string $model = BackInStockSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $modelLabel = 'powiadomienie o dostępności';

    protected static ?string $pluralModelLabel = 'powiadomienia o dostępności';

    protected static ?string $navigationLabel = 'Powiadomienia o dostępności';

    protected static \UnitEnum|string|null $navigationGroup = 'Oferta & Galeria';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->label('E-mail')
                    ->icon('heroicon-o-envelope')
                    ->iconColor('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produkt')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => is_array($record->product?->name) ? ($record->product?->name['pl'] ?? reset($record->product?->name)) : $record->product?->name),
                TextColumn::make('productVariant')
                    ->label('Wariant')
                    ->getStateUsing(fn ($record) => $record->productVariant ? $record->productVariant->optionValues->pluck('value')->join(', ') : '-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'notified' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'notified' => 'heroicon-o-check-circle',
                        default => null,
                    })
                    ->sortable(),
                TextColumn::make('notified_at')
                    ->label('Powiadomiono o')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Zapisano o')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Oczekujący',
                        'notified' => 'Powiadomiono',
                    ]),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBackInStockSubscriptions::route('/'),
        ];
    }
}

