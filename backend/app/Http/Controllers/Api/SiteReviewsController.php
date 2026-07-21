<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;

class SiteReviewsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $reviews = ProductReview::query()
            ->where(function ($q) {
                $q->where('status', 'publiczny')
                  ->orWhere('is_approved', true);
            })
            ->latest()
            ->get()
            ->map(fn (ProductReview $review) => [
                'id' => $review->id,
                'stars' => $review->rating,
                'text' => (string) $review->comment,
                'author' => $review->customer_name,
                'meta' => $review->meta ?? 'Opinia klienta',
                'emoji' => $review->emoji ?? '✨',
                'created_at' => $review->created_at->toIso8601String(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $reviews,
        ]);
    }
}
