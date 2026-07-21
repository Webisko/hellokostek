<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index(string $slug): JsonResponse
    {
        if (! app(\App\Support\StoreSettings::class)->productReviewsEnabled()) {
            return response()->json([
                'message' => 'Opinie o produktach są wyłączone.',
                'data' => [
                    'average_rating' => 0,
                    'reviews_count' => 0,
                    'reviews' => [],
                ]
            ]);
        }

        $product = Product::query()
            ->publicCatalog()
            ->where('slug', $slug)
            ->firstOrFail();

        $reviews = $product->reviews()
            ->where('is_approved', true)
            ->latest()
            ->get();

        return response()->json([
            'data' => [
                'average_rating' => $product->averageRating(),
                'reviews_count' => $product->approvedReviewsCount(),
                'reviews' => $reviews->map(fn (ProductReview $review) => [
                    'id' => $review->id,
                    'customer_name' => $review->customer_name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'is_verified_purchase' => $review->is_verified_purchase,
                    'created_at' => $review->created_at->toIso8601String(),
                ])->all(),
            ],
        ]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        if (! app(\App\Support\StoreSettings::class)->productReviewsEnabled()) {
            return response()->json([
                'message' => 'Dodawanie opinii o produktach jest wyłączone.'
            ], 403);
        }

        $product = Product::query()
            ->publicCatalog()
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email:rfc'],
        ]);

        // Determine if verified purchase
        $isVerified = \App\Models\Order::query()
            ->where('customer_email', $validated['customer_email'])
            ->whereIn('status', ['placed', 'completed'])
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->exists();

        $review = ProductReview::query()->forceCreate([
            'product_id' => $product->id,
            'customer_email' => $validated['customer_email'],
            'customer_name' => strip_tags($validated['customer_name']),
            'rating' => $validated['rating'],
            'comment' => isset($validated['comment']) ? strip_tags($validated['comment']) : null,
            'is_verified_purchase' => $isVerified,
            'is_approved' => false, // Requires manual approval in Filament admin panel
        ]);

        return response()->json([
            'message' => 'Opinia zostala dodana i oczekuje na zatwierdzenie przez moderatora.',
            'data' => [
                'id' => $review->id,
                'customer_name' => $review->customer_name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'is_verified_purchase' => $review->is_verified_purchase,
                'is_approved' => $review->is_approved,
                'created_at' => $review->created_at->toIso8601String(),
            ]
        ], 201);
    }
}
