<?php

namespace App\Filament\Resources\FailedJobs\Tables;

use App\Domain\Operations\FailedJobRetryService;
use App\Models\FailedJob;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FailedJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('failed_at', 'desc')
            ->columns([
                TextColumn::make('failed_at')->label('Nieudany od')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('job_display_name')
                    ->label('Job')
                    ->state(fn (FailedJob $record): string => $record->jobDisplayName() ?? '-')
                    ->wrap(),
                TextColumn::make('queue')->label('Kolejka')->searchable()->sortable(),
                TextColumn::make('connection')->label('Połączenie')->searchable()->toggleable(),
                TextColumn::make('payload_attempts')
                    ->label('Próby')
                    ->state(fn (FailedJob $record): int => $record->payloadAttempts())
                    ->toggleable(),
                TextColumn::make('retry_readiness')
                    ->label('Gotowość retry')
                    ->state(fn (FailedJob $record): string => $record->retryReadiness())
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => FailedJob::retryReadinessLabel($state))
                    ->color(fn (?string $state): string => FailedJob::retryReadinessColor($state))
                    ->toggleable(),
                TextColumn::make('uuid')->label('UUID')->searchable()->toggleable(),
                TextColumn::make('exception')->label('Wyjątek')->limit(100)->wrap(),
            ])
            ->filters([
                SelectFilter::make('queue')
                    ->label('Kolejka')
                    ->options(fn (): array => FailedJobTableOptions::queueOptions()),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label('Ponów')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Ponowić job?')
                    ->modalDescription('Zadanie zostanie ponownie dodane do kolejki, a obecny wpis z nieudanych zadań zostanie usunięty po powodzeniu operacji.')
                    ->action(fn (FailedJob $record) => app(FailedJobRetryService::class)->retry($record))
                    ->successNotificationTitle('Job został ponownie dodany do kolejki.'),
                ViewAction::make()->iconButton()->tooltip('Podgląd')->extraAttributes(['style' => 'display: none !important;'])
                    ->slideOver()
                    ->modalWidth('5xl'),
            ]);
    }
}