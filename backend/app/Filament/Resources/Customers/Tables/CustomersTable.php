<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordAction('view')
            ->columns([
                TextColumn::make('name')
                    ->label('Imię i nazwisko')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->icon('heroicon-o-envelope')
                    ->iconColor('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customerProfile.segment')
                    ->label('Segment')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CustomerResource::formatStateLabel($state))
                    ->color(fn ($state): string => CustomerResource::segmentColor($state))
                    ->icon(fn ($state): ?string => match ($state instanceof \BackedEnum ? $state->value : (string) $state) {
                        'loyal_five' => 'heroicon-o-sparkles',
                        'loyal_eight' => 'heroicon-o-trophy',
                        'wholesale_thirty' => 'heroicon-o-briefcase',
                        'regular' => 'heroicon-o-user',
                        default => null,
                    }),
                TextColumn::make('orders_sum_total_amount')
                    ->label('Suma wydana')
                    ->money('PLN', divideBy: 100)
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customerProfile.completed_orders_count')
                    ->label('Zakończone zamówienia')
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->label('Wszystkie zamówienia')
                    ->sortable(),
                TextColumn::make('customerProfile.phone')
                    ->label('Telefon')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('customerProfile.marketing_consent_at')
                    ->label('Zgoda marketingowa')
                    ->boolean()
                    ->state(fn ($record): bool => filled($record->customerProfile?->marketing_consent_at))
                    ->toggleable(),
                TextColumn::make('customerProfile.last_order_at')
                    ->label('Ostatnie zamówienie')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('customer_profiles', 'customer_profiles.user_id', '=', 'users.id')
                            ->orderBy('customer_profiles.last_order_at', $direction)
                            ->select('users.*');
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('segment')
                    ->label('Segment')
                    ->options(CustomerResource::segmentOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('customerProfile', fn (Builder $profileQuery): Builder => $profileQuery->where('segment', $data['value']));
                    }),
                TernaryFilter::make('marketing_consent')
                    ->label('Zgoda marketingowa')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('customerProfile', fn (Builder $profileQuery): Builder => $profileQuery->whereNotNull('marketing_consent_at')),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $nestedQuery): Builder {
                            return $nestedQuery
                                ->whereDoesntHave('customerProfile')
                                ->orWhereHas('customerProfile', fn (Builder $profileQuery): Builder => $profileQuery->whereNull('marketing_consent_at'));
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                \Filament\Tables\Filters\TrashedFilter::make(),
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
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')
                    ->slideOver()
                    ->modalWidth('7xl'),
                \Filament\Actions\DeleteAction::make()->iconButton()->tooltip('Usuń'),
                \Filament\Actions\RestoreAction::make()->iconButton()->tooltip('Przywróć'),
                \Filament\Actions\ForceDeleteAction::make()->iconButton()->tooltip('Usuń trwale'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}