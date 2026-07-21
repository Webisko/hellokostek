<?php

namespace App\Filament\Resources\IntegrationLogs\Tables;

use App\Models\IntegrationLog;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IntegrationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('occurred_at')->label('Zdarzenie')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('integration')
                    ->label('Integracja')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => IntegrationLog::integrationLabel($state))
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Zdarzenie techniczne')
                    ->description(fn (IntegrationLog $record): string => $record->event)
                    ->formatStateUsing(fn (?string $state): string => IntegrationLog::eventLabel($state))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('direction')
                    ->label('Kierunek')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => IntegrationLog::directionLabel($state))
                    ->color(fn (?string $state): string => IntegrationLog::directionColor($state))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => IntegrationLog::statusLabel($state))
                    ->color(fn (?string $state): string => IntegrationLog::statusColor($state))
                    ->sortable(),
                TextColumn::make('order.number')->label('Zamówienie')->toggleable(),
                TextColumn::make('external_reference')->label('Referencja')->searchable()->toggleable(),
                TextColumn::make('error_message')->label('Błąd')->limit(100)->wrap()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('health_window')
                    ->label('Zakres widoku kondycji')
                    ->options([
                        'alert' => 'Alerty integracyjne',
                        'stripe_issue' => 'Problemy Stripe',
                        'payment_session_issue' => 'Problemy sesji płatności',
                        'payment_callback_issue' => 'Problemy callbacków płatności',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'alert' => $query
                                ->where('status', '!=', IntegrationLog::STATUS_SUCCESS)
                                ->where(function (Builder $query): void {
                                    $query
                                        ->whereNotIn('integration', ['przelewy24', 'stripe'])
                                        ->orWhere(function (Builder $query): void {
                                            $query
                                                ->where('integration', 'przelewy24')
                                                ->where(function (Builder $query): void {
                                                    $query
                                                        ->where('direction', '!=', IntegrationLog::DIRECTION_OUTGOING)
                                                        ->orWhere('event', 'not like', IntegrationLog::paymentSessionIssueEventPattern());
                                                })
                                                ->where(function (Builder $query): void {
                                                    $query
                                                        ->where('direction', '!=', IntegrationLog::DIRECTION_INCOMING)
                                                        ->orWhereNotIn('event', IntegrationLog::paymentCallbackAlertEvents());
                                                });
                                        });
                                }),
                            'stripe_issue' => $query
                                ->where('integration', 'stripe')
                                ->where('status', '!=', IntegrationLog::STATUS_SUCCESS),
                            'payment_session_issue' => $query
                                ->where('integration', 'przelewy24')
                                ->where('direction', IntegrationLog::DIRECTION_OUTGOING)
                                ->where('status', '!=', IntegrationLog::STATUS_SUCCESS)
                                ->where('event', 'like', IntegrationLog::paymentSessionIssueEventPattern()),
                            'payment_callback_issue' => $query
                                ->where('integration', 'przelewy24')
                                ->where('direction', IntegrationLog::DIRECTION_INCOMING)
                                ->where('status', IntegrationLog::STATUS_WARNING)
                                ->whereIn('event', IntegrationLog::paymentCallbackAlertEvents()),
                            default => $query,
                        };
                    }),
                SelectFilter::make('integration')
                    ->label('Integracja')
                    ->options(fn (): array => IntegrationLog::query()
                        ->orderBy('integration')
                        ->distinct()
                        ->pluck('integration', 'integration')
                        ->mapWithKeys(fn (string $value, string $key): array => [$key => IntegrationLog::integrationLabel($value)])
                        ->all()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn (): array => IntegrationLog::query()
                        ->orderBy('status')
                        ->distinct()
                        ->pluck('status', 'status')
                        ->mapWithKeys(fn (string $value, string $key): array => [$key => IntegrationLog::statusLabel($value)])
                        ->all()),
                SelectFilter::make('direction')
                    ->label('Kierunek')
                    ->options(fn (): array => IntegrationLog::query()
                        ->orderBy('direction')
                        ->distinct()
                        ->pluck('direction', 'direction')
                        ->mapWithKeys(fn (string $value, string $key): array => [$key => IntegrationLog::directionLabel($value)])
                        ->all()),
                SelectFilter::make('event')
                    ->label('Zdarzenie')
                    ->multiple()
                    ->options(fn (): array => IntegrationLog::query()
                        ->orderBy('event')
                        ->distinct()
                        ->pluck('event', 'event')
                        ->mapWithKeys(fn (string $value, string $key): array => [$key => IntegrationLog::eventLabel($value)])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Podgląd')->extraAttributes(['style' => 'display: none !important;'])
                    ->slideOver()
                    ->modalWidth('5xl'),
            ]);
    }
}