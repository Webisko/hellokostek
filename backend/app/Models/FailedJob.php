<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FailedJob extends Model
{
    use HasFactory;

    public const RETRY_READINESS_READY = 'ready';

    public const RETRY_READINESS_MAX_TRIES_REACHED = 'max_tries_reached';

    public const RETRY_READINESS_EXPIRED = 'expired';

    public const RETRY_READINESS_UNKNOWN = 'unknown';

    protected $table = 'failed_jobs';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'connection',
        'queue',
        'payload',
        'exception',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
        ];
    }

    public function payloadData(): array
    {
        $payload = json_decode($this->payload ?? '', true);

        return is_array($payload) ? $payload : [];
    }

    public function jobDisplayName(): ?string
    {
        $payload = $this->payloadData();

        return $payload['displayName']
            ?? $payload['data']['commandName']
            ?? $payload['job']
            ?? null;
    }

    public function payloadAttempts(): int
    {
        return (int) ($this->payloadData()['attempts'] ?? 0);
    }

    public function payloadMaxTries(): ?int
    {
        $maxTries = $this->payloadData()['maxTries'] ?? null;

        return is_numeric($maxTries) ? (int) $maxTries : null;
    }

    public function payloadTimeout(): ?int
    {
        $timeout = $this->payloadData()['timeout'] ?? null;

        return is_numeric($timeout) ? (int) $timeout : null;
    }

    public function payloadBackoff(): ?string
    {
        $backoff = $this->payloadData()['backoff'] ?? null;

        if (is_array($backoff)) {
            $values = array_values(array_filter($backoff, static fn (mixed $value): bool => filled($value)));

            return $values === [] ? null : implode(', ', array_map(static fn (mixed $value): string => (string) $value, $values));
        }

        return filled($backoff) ? (string) $backoff : null;
    }

    public function payloadRetryUntil(): ?Carbon
    {
        $retryUntil = $this->payloadData()['retryUntil'] ?? null;

        return is_numeric($retryUntil) ? Carbon::createFromTimestamp((int) $retryUntil) : null;
    }

    public function payloadJobUuid(): ?string
    {
        $jobUuid = $this->payloadData()['uuid'] ?? null;

        return filled($jobUuid) ? (string) $jobUuid : null;
    }

    public function retryReadiness(): string
    {
        $maxTries = $this->payloadMaxTries();
        $attempts = $this->payloadAttempts();

        if ($maxTries !== null && $attempts >= $maxTries) {
            return self::RETRY_READINESS_MAX_TRIES_REACHED;
        }

        $retryUntil = $this->payloadRetryUntil();
        if ($retryUntil !== null && $retryUntil->isPast()) {
            return self::RETRY_READINESS_EXPIRED;
        }

        if ($maxTries !== null || $retryUntil !== null) {
            return self::RETRY_READINESS_READY;
        }

        return self::RETRY_READINESS_UNKNOWN;
    }

    public static function retryReadinessOptions(): array
    {
        return [
            self::RETRY_READINESS_READY => 'Gotowy do ponowienia',
            self::RETRY_READINESS_MAX_TRIES_REACHED => 'Limit prob osiagniety',
            self::RETRY_READINESS_EXPIRED => 'Okno retry wygaslo',
            self::RETRY_READINESS_UNKNOWN => 'Brak danych retry',
        ];
    }

    public static function retryReadinessLabel(?string $state): string
    {
        if (blank($state)) {
            return '-';
        }

        return self::retryReadinessOptions()[$state] ?? (string) $state;
    }

    public static function retryReadinessColor(?string $state): string
    {
        return match ($state) {
            self::RETRY_READINESS_READY => 'success',
            self::RETRY_READINESS_MAX_TRIES_REACHED, self::RETRY_READINESS_EXPIRED => 'danger',
            self::RETRY_READINESS_UNKNOWN => 'warning',
            default => 'gray',
        };
    }
}