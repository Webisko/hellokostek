<?php

namespace App\Filament\Resources\FailedJobs\Schemas;

use App\Models\FailedJob;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FailedJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Failed job')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('failed_at')->label('Nieudany od')->dateTime('Y-m-d H:i'),
                    TextEntry::make('queue')->label('Kolejka'),
                    TextEntry::make('connection')->label('Polaczenie'),
                    TextEntry::make('uuid')->label('UUID')->copyable(),
                    TextEntry::make('payload')->label('Payload')->columnSpanFull()->prose(),
                    TextEntry::make('exception')->label('Wyjatek')->columnSpanFull()->prose(),
                ]),
            Section::make('Retry context')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('job_display_name')
                        ->label('Job')
                        ->state(fn (FailedJob $record): ?string => $record->jobDisplayName()),
                    TextEntry::make('payload_attempts')
                        ->label('Liczba prob')
                        ->state(fn (FailedJob $record): int => $record->payloadAttempts()),
                    TextEntry::make('retry_readiness')
                        ->label('Gotowosc retry')
                        ->state(fn (FailedJob $record): string => $record->retryReadiness())
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => FailedJob::retryReadinessLabel($state))
                        ->color(fn (?string $state): string => FailedJob::retryReadinessColor($state)),
                    TextEntry::make('payload_max_tries')
                        ->label('Maks. prob')
                        ->state(fn (FailedJob $record): ?int => $record->payloadMaxTries()),
                    TextEntry::make('payload_timeout')
                        ->label('Timeout (s)')
                        ->state(fn (FailedJob $record): ?int => $record->payloadTimeout()),
                    TextEntry::make('payload_backoff')
                        ->label('Backoff (s)')
                        ->state(fn (FailedJob $record): ?string => $record->payloadBackoff()),
                    TextEntry::make('payload_retry_until')
                        ->label('Retry until')
                        ->state(fn (FailedJob $record): ?string => $record->payloadRetryUntil()?->format('Y-m-d H:i:s')),
                    TextEntry::make('payload_job_uuid')
                        ->label('UUID w payloadzie')
                        ->state(fn (FailedJob $record): ?string => $record->payloadJobUuid())
                        ->copyable(),
                ]),
        ]);
    }
}