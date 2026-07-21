<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'customer_email',
        'customer_name',
        'meta',
        'emoji',
        'rating',
        'comment',
        'status',
        'is_verified_purchase',
        'is_approved',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductReview $review): void {
            if ($review->isDirty('status')) {
                $review->is_approved = ($review->status === 'publiczny');
            } elseif ($review->isDirty('is_approved')) {
                $review->status = $review->is_approved ? 'publiczny' : 'szkic';
            }
        });
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
