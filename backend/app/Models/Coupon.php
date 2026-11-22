<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'discount_type',
        'value',
        'currency',
        'minimum_subtotal_amount',
        'usage_limit',
        'usage_limit_per_customer',
        'starts_at',
        'ends_at',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon): void {
            if ($coupon->exists && $coupon->isDirty('metadata')) {
                $original = is_array($coupon->getOriginal('metadata')) ? $coupon->getOriginal('metadata') : [];
                $new = is_array($coupon->metadata) ? $coupon->metadata : [];
                $coupon->metadata = array_replace($original, $new);
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}