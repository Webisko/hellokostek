<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IrydologyCase extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_PHOTOS = 'awaiting_photos';

    public const STATUS_PHOTOS_RECEIVED = 'photos_received';

    public const STATUS_ANALYSIS_IN_PROGRESS = 'analysis_in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'status',
        'customer_email',
        'package_name',
        'instructions_sent_at',
        'assets_received_at',
        'analysis_due_at',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'instructions_sent_at' => 'datetime',
            'assets_received_at' => 'datetime',
            'analysis_due_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_AWAITING_PHOTOS => 'Oczekuje na zdjecia',
            self::STATUS_PHOTOS_RECEIVED => 'Zdjecia otrzymane',
            self::STATUS_ANALYSIS_IN_PROGRESS => 'Analiza w toku',
            self::STATUS_COMPLETED => 'Zakonczona',
            self::STATUS_CANCELLED => 'Anulowana',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        if (blank($status)) {
            return '-';
        }

        return self::statusOptions()[$status] ?? (string) $status;
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            self::STATUS_AWAITING_PHOTOS => 'warning',
            self::STATUS_PHOTOS_RECEIVED => 'info',
            self::STATUS_ANALYSIS_IN_PROGRESS => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    public function isOverdue(): bool
    {
        return $this->analysis_due_at !== null
            && $this->completed_at === null
            && $this->analysis_due_at->isPast();
    }
}