<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLog extends Model
{
    use HasFactory;

    public const STATUS_SUCCESS = 'success';

    public const STATUS_WARNING = 'warning';

    public const STATUS_ERROR = 'error';

    public const DIRECTION_OUTGOING = 'outgoing';

    public const DIRECTION_INCOMING = 'incoming';

    /**
     * @return array<int, string>
     */
    public static function paymentCallbackAlertEvents(): array
    {
        return [
            'payment_callback_unauthorized',
            'payment_callback_rejected_transaction_not_found',
            'payment_callback_rejected_order_mismatch',
            'payment_callback_rejected_conflicting_status',
            'payment_callback_verification_failed',
        ];
    }

    public static function paymentSessionIssueEventPattern(): string
    {
        return 'payment_session_%';
    }



    protected $fillable = [
        'order_id',
        'integration',
        'event',
        'direction',
        'status',
        'external_reference',
        'error_message',
        'request_payload',
        'response_payload',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function integrationLabel(?string $integration): string
    {
        return match ($integration) {
            'przelewy24' => 'Przelewy24',
            'stripe' => 'Stripe',
            default => filled($integration) ? (string) $integration : '-',
        };
    }

    public static function eventLabel(?string $event): string
    {
        return match ($event) {
            'stripe_webhook_received' => 'Odebrano webhook Stripe',
            'stripe_webhook_signature_invalid' => 'Niepoprawny podpis webhooka Stripe',
            'stripe_payment_session_initiated' => 'Sesja płatności Stripe utworzona',
            'stripe_payment_session_failed' => 'Inicjalizacja płatności Stripe nie powiodła się',
            'payment_session_initiated' => 'Sesja płatności utworzona',
            'payment_session_configuration_required' => 'Brak konfiguracji sesji płatności',
            'payment_session_registration_failed' => 'Rejestracja sesji płatności nie powiodła się',
            'payment_session_reused' => 'Użyto istniejącej sesji płatności',
            'payment_session_rejected_invalid_order_status' => 'Odrzucono próbę dla niepoprawnego statusu zamówienia',
            'payment_session_rejected_provider_mismatch' => 'Odrzucono próbę dla niezgodnego operatora płatności',
            'payment_callback_confirmed' => 'Powiadomienie zwrotne potwierdziło płatność',
            'payment_callback_failed' => 'Powiadomienie zwrotne oznaczyło płatność jako nieudaną',
            'payment_callback_replayed' => 'Odebrano ponowne powiadomienie zwrotne dla tej samej transakcji',
            'payment_callback_unauthorized' => 'Odrzucono nieautoryzowane powiadomienie zwrotne płatności',
            'payment_callback_rejected_transaction_not_found' => 'Odrzucono powiadomienie zwrotne bez znalezionej transakcji',
            'payment_callback_rejected_order_mismatch' => 'Odrzucono powiadomienie zwrotne z niezgodnym numerem zamówienia',
            'payment_callback_rejected_conflicting_status' => 'Odrzucono powiadomienie zwrotne ze sprzecznym statusem transakcji',
            'payment_callback_verification_failed' => 'Weryfikacja powiadomienia zwrotnego płatności nie powiodła się',
            default => filled($event) ? (string) $event : '-',
        };
    }

    public static function directionLabel(?string $direction): string
    {
        return match ($direction) {
            self::DIRECTION_OUTGOING => 'Wychodzący',
            self::DIRECTION_INCOMING => 'Przychodzący',
            default => filled($direction) ? (string) $direction : '-',
        };
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_SUCCESS => 'Sukces',
            self::STATUS_WARNING => 'Ostrzeżenie',
            self::STATUS_ERROR => 'Błąd',
            default => filled($status) ? (string) $status : '-',
        };
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            self::STATUS_SUCCESS => 'success',
            self::STATUS_WARNING => 'warning',
            self::STATUS_ERROR => 'danger',
            default => 'gray',
        };
    }

    public static function directionColor(?string $direction): string
    {
        return match ($direction) {
            self::DIRECTION_OUTGOING => 'info',
            self::DIRECTION_INCOMING => 'warning',
            default => 'gray',
        };
    }
}