<?php

namespace App\Domain\Storefront;

use App\Support\StoreSettings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class GooglePlaceReviewsService
{
    public function __construct(
        private readonly StoreSettings $storeSettings,
    ) {
    }

    public function feed(): array
    {
        $config = $this->config();
        $snapshotPayload = $this->snapshotPayload($config);

        if (! $config['enabled'] || blank($config['api_key']) || blank($config['business_name'])) {
            return $snapshotPayload ?? $this->basePayload($config, 'unconfigured');
        }

        $cacheTtl = max(5, (int) $config['cache_ttl_minutes']);
        $cacheKey = sprintf(
            'storefront:google-reviews:%s',
            sha1(implode('|', [
                (string) $config['business_name'],
                (string) $config['place_id'],
                (string) $config['language'],
            ])),
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes($cacheTtl),
            fn (): array => $this->fetchFeed($config, $snapshotPayload),
        );
    }

    private function fetchFeed(array $config, ?array $snapshotPayload = null): array
    {
        try {
            $placeId = filled($config['place_id'])
                ? (string) $config['place_id']
                : $this->resolvePlaceId($config);

            if (blank($placeId)) {
                return $snapshotPayload ?? $this->basePayload($config, 'not_found');
            }

            $response = Http::acceptJson()->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'place_id,name,rating,user_ratings_total,url,reviews',
                'language' => $config['language'],
                'reviews_no_translations' => 'true',
                'reviews_sort' => 'newest',
                'key' => $config['api_key'],
            ]);

            $response->throw();

            if ($response->json('status') !== 'OK') {
                return $snapshotPayload ?? $this->basePayload($config, 'error', [
                    'place_id' => $placeId,
                ]);
            }

            $result = (array) $response->json('result', []);
            $reviews = collect((array) Arr::get($result, 'reviews', []))
                ->filter(fn (array $review): bool => (int) Arr::get($review, 'rating', 0) === 5)
                ->map(fn (array $review): array => [
                    'author_name' => (string) Arr::get($review, 'author_name', 'Klient Google'),
                    'author_url' => Arr::get($review, 'author_url'),
                    'profile_photo_url' => Arr::get($review, 'profile_photo_url'),
                    'rating' => (int) Arr::get($review, 'rating', 0),
                    'relative_time_description' => (string) Arr::get($review, 'relative_time_description', ''),
                    'text' => (string) Arr::get($review, 'text', ''),
                    'language' => Arr::get($review, 'language'),
                    'published_at' => Arr::get($review, 'time')
                        ? now()->setTimestamp((int) Arr::get($review, 'time'))->toIso8601String()
                        : null,
                ])
                ->filter(fn (array $review): bool => filled($review['text']))
                ->values()
                ->all();

            return $this->basePayload($config, 'ready', [
                'business_name' => (string) Arr::get($result, 'name', $config['business_name']),
                'place_id' => $placeId,
                'rating' => Arr::get($result, 'rating'),
                'user_ratings_total' => Arr::get($result, 'user_ratings_total'),
                'listing_url' => Arr::get($result, 'url'),
                'reviews_count' => count($reviews),
                'reviews' => $reviews,
                'fetched_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $snapshotPayload ?? $this->basePayload($config, 'error');
        }
    }

    private function resolvePlaceId(array $config): ?string
    {
        $response = Http::acceptJson()->get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
            'input' => $config['business_name'],
            'inputtype' => 'textquery',
            'fields' => 'place_id,name',
            'language' => $config['language'],
            'key' => $config['api_key'],
        ]);

        $response->throw();

        if ($response->json('status') !== 'OK') {
            return null;
        }

        return Arr::get($response->json('candidates.0'), 'place_id');
    }

    private function config(): array
    {
        $integrations = $this->storeSettings->integrations();
        $reviewSettings = (array) Arr::get($integrations, 'reviews', []);
        $googleSettings = (array) Arr::get($reviewSettings, 'google', []);

        return [
            'enabled' => (bool) Arr::get($reviewSettings, 'enabled', false),
            'business_name' => (string) (Arr::get($googleSettings, 'business_name') ?: config('services.google_places.business_name')),
            'place_id' => Arr::get($googleSettings, 'place_id') ?: config('services.google_places.place_id'),
            'api_key' => (string) config('services.google_places.api_key'),
            'cache_ttl_minutes' => (int) config('services.google_places.cache_ttl_minutes', 60),
            'language' => (string) config('services.google_places.language', 'pl'),
            'snapshot' => Arr::get($googleSettings, 'snapshot'),
        ];
    }

    private function snapshotPayload(array $config): ?array
    {
        $snapshot = Arr::get($config, 'snapshot');

        if (! is_array($snapshot)) {
            return null;
        }

        $reviews = collect((array) Arr::get($snapshot, 'reviews', []))
            ->map(fn (mixed $review): array => [
                'author_name' => (string) Arr::get((array) $review, 'author_name', 'Klient Google'),
                'author_url' => Arr::get((array) $review, 'author_url'),
                'profile_photo_url' => Arr::get((array) $review, 'profile_photo_url'),
                'rating' => (int) Arr::get((array) $review, 'rating', 0),
                'relative_time_description' => (string) Arr::get((array) $review, 'relative_time_description', ''),
                'text' => (string) Arr::get((array) $review, 'text', ''),
                'language' => Arr::get((array) $review, 'language'),
                'published_at' => Arr::get((array) $review, 'published_at'),
            ])
            ->filter(fn (array $review): bool => $review['rating'] === 5 && filled($review['text']))
            ->values()
            ->all();

        if ($reviews === []) {
            return null;
        }

        return $this->basePayload($config, 'ready', [
            'business_name' => (string) Arr::get($snapshot, 'business_name', $config['business_name']),
            'place_id' => Arr::get($snapshot, 'place_id', $config['place_id']),
            'rating' => Arr::get($snapshot, 'rating'),
            'user_ratings_total' => Arr::get($snapshot, 'user_ratings_total'),
            'listing_url' => Arr::get($snapshot, 'listing_url'),
            'reviews_count' => count($reviews),
            'reviews' => $reviews,
            'fetched_at' => Arr::get($snapshot, 'fetched_at'),
            'delivery_mode' => 'snapshot',
        ]);
    }

    private function basePayload(array $config, string $status, array $overrides = []): array
    {
        return array_merge([
            'source' => 'google',
            'status' => $status,
            'business_name' => $config['business_name'],
            'place_id' => $config['place_id'],
            'rating' => null,
            'user_ratings_total' => null,
            'listing_url' => null,
            'reviews_count' => 0,
            'reviews' => [],
            'fetched_at' => null,
        ], $overrides);
    }
}