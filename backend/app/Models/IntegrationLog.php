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
            'payment_session_initiated' => 'Sesja platnosci utworzona',
            'payment_session_configuration_required' => 'Brak konfiguracji sesji platnosci',
            'payment_session_registration_failed' => 'Rejestracja sesji platnosci nie powiodla sie',
            'payment_session_reused' => 'Uzyto istniejacej sesji platnosci',
            'payment_session_rejected_invalid_order_status' => 'Odrzucono probe dla niepoprawnego statusu zamowienia',
            'payment_session_rejected_provider_mismatch' => 'Odrzucono probe dla niezgodnego providera platnosci',
            'payment_callback_confirmed' => 'Callback potwierdzil platnosc',
            'payment_callback_failed' => 'Callback oznaczyl platnosc jako nieudana',
            'payment_callback_replayed' => 'Odebrano ponowny callback dla tej samej transakcji',
            'payment_callback_unauthorized' => 'Odrzucono nieautoryzowany callback platnosci',
            'payment_callback_rejected_transaction_not_found' => 'Odrzucono callback bez znalezionej transakcji',
            'payment_callback_rejected_order_mismatch' => 'Odrzucono callback z niezgodnym numerem zamowienia',
            'payment_callback_rejected_conflicting_status' => 'Odrzucono callback ze sprzecznym statusem transakcji',
            'payment_callback_verification_failed' => 'Weryfikacja callbacku platnosci nie powiodla sie',
            default => filled($event) ? (string) $event : '-',
        };
    }

    public static function directionLabel(?string $direction): string
    {
        return match ($direction) {
            self::DIRECTION_OUTGOING => 'Wychodzacy',
            self::DIRECTION_INCOMING => 'Przychodzacy',
            default => filled($direction) ? (string) $direction : '-',
        };
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_SUCCESS => 'Sukces',
            self::STATUS_WARNING => 'Ostrzezenie',
            self::STATUS_ERROR => 'Blad',
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