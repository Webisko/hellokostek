<?php

namespace App\Http\Controllers\Api;

use App\Domain\Storefront\GooglePlaceReviewsService;
use App\Http\Controllers\Controller;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;

class GoogleReviewsController extends Controller
{
    public function __construct(
        private readonly GooglePlaceReviewsService $googlePlaceReviewsService,
        private readonly StoreSettings $storeSettings,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        if (! $this->storeSettings->generalReviewsEnabled()) {
            return response()->json([
                'source' => 'none',
                'status' => 'disabled',
                'reviews' => [],
            ]);
        }

        $source = $this->storeSettings->generalReviewsSource();

        // Fetch site reviews if needed
        $siteReviews = [];
        if ($source === 'site' || $source === 'both') {
            $siteReviews = \App\Models\ProductReview::query()
                ->whereNull('product_id')
                ->where('is_approved', true)
                ->latest()
                ->get()
                ->map(fn (\App\Models\ProductReview $review) => [
                    'author_name' => $review->customer_name,
                    'author_url' => null,
                    'profile_photo_url' => null,
                    'rating' => $review->rating,
                    'relative_time_description' => $review->created_at->diffForHumans(),
                    'text' => (string) $review->comment,
                    'language' => 'pl',
                    'published_at' => $review->created_at->toIso8601String(),
                    'is_verified_purchase' => $review->is_verified_purchase,
                    'source' => 'site',
                ])
                ->all();
        }

        if ($source === 'site') {
            $reviewsCount = count($siteReviews);
            $averageRating = $reviewsCount > 0 ? round(collect($siteReviews)->avg('rating'), 1) : 5.0;
            $businessName = $this->storeSettings->storeName();
            return response()->json([
                'source' => 'site',
                'status' => 'ready',
                'business_name' => $businessName,
                'rating' => $averageRating,
                'user_ratings_total' => $reviewsCount,
                'reviews_count' => $reviewsCount,
                'reviews' => $siteReviews,
                'schema_json_ld' => $this->localBusinessSchema($businessName, $averageRating, $reviewsCount),
            ]);
        }

        // Fetch Google Place reviews
        $googleFeed = $this->googlePlaceReviewsService->feed();
        $googleReviews = $googleFeed['reviews'] ?? [];
        foreach ($googleReviews as &$gr) {
            $gr['source'] = 'google';
        }
        unset($gr);

        if ($source === 'google') {
            $gRating = $googleFeed['rating'] ?? 5.0;
            $gTotal = $googleFeed['user_ratings_total'] ?? 0;
            $businessName = $googleFeed['business_name'] ?? $this->storeSettings->storeName();
            $googleFeed['schema_json_ld'] = $this->localBusinessSchema($businessName, $gRating, $gTotal);
            return response()->json($googleFeed);
        }

        // Combine both
        $mergedReviews = array_merge($googleReviews, $siteReviews);
        usort($mergedReviews, function ($a, $b) {
            $dateA = $a['published_at'] ? strtotime($a['published_at']) : 0;
            $dateB = $b['published_at'] ? strtotime($b['published_at']) : 0;
            return $dateB <=> $dateA; // Descending chronologically
        });

        $gRating = $googleFeed['rating'] ?? 5.0;
        $gTotal = $googleFeed['user_ratings_total'] ?? 0;
        
        $sCount = count($siteReviews);
        $sRating = $sCount > 0 ? collect($siteReviews)->avg('rating') : 5.0;

        $totalRatingsCount = $gTotal + $sCount;
        $averageRating = $totalRatingsCount > 0 
            ? round((($gRating * $gTotal) + ($sRating * $sCount)) / $totalRatingsCount, 1)
            : 5.0;

        $businessName = $googleFeed['business_name'] ?? $this->storeSettings->storeName();

        return response()->json([
            'source' => 'both',
            'status' => 'ready',
            'business_name' => $businessName,
            'rating' => $averageRating,
            'user_ratings_total' => $totalRatingsCount,
            'reviews_count' => count($mergedReviews),
            'reviews' => $mergedReviews,
            'schema_json_ld' => $this->localBusinessSchema($businessName, $averageRating, $totalRatingsCount),
        ]);
    }

    private function localBusinessSchema(string $businessName, ?float $rating, int $ratingsCount): array
    {
        $settingsModel = $this->storeSettings->model();
        $napMetadata = $settingsModel->metadata ?? [];
        $baseUrl = rtrim((string) config('services.storefront.url', config('app.url')), '/');
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $businessName,
            'url' => $baseUrl,
        ];

        if (filled($napMetadata['phone'] ?? null)) {
            $schema['telephone'] = $napMetadata['phone'];
        }

        $address = [];
        if (filled($napMetadata['address_street'] ?? null)) {
            $address['streetAddress'] = $napMetadata['address_street'];
        }
        if (filled($napMetadata['address_city'] ?? null)) {
            $address['addressLocality'] = $napMetadata['address_city'];
        }
        if (filled($napMetadata['address_postal_code'] ?? null)) {
            $address['postalCode'] = $napMetadata['address_postal_code'];
        }
        if (filled($napMetadata['address_country'] ?? null)) {
            $address['addressCountry'] = $napMetadata['address_country'];
        }
        
        if (! empty($address)) {
            $address['@type'] = 'PostalAddress';
            $schema['address'] = $address;
        }

        if (filled($napMetadata['latitude'] ?? null) && filled($napMetadata['longitude'] ?? null)) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $napMetadata['latitude'],
                'longitude' => $napMetadata['longitude'],
            ];
        }

        if ($ratingsCount > 0 && $rating !== null) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $rating,
                'reviewCount' => (string) $ratingsCount,
            ];
        }

        return $schema;
    }
}