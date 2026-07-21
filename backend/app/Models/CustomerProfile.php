<?php

namespace App\Models;

use App\Domain\Commerce\Enums\CustomerSegment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'segment',
        'phone',
        'completed_orders_count',
        'marketing_consent_at',
        'last_order_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'segment' => CustomerSegment::class,
            'marketing_consent_at' => 'datetime',
            'last_order_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}