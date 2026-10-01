<?php

namespace App\Http\Resources;

use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductReview
 */
class ProductReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'is_verified_purchase' => (bool) $this->is_verified_purchase,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
