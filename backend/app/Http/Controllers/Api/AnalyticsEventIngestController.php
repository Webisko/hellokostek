<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AnalyticsEventIngestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:120', Rule::in(AnalyticsEvent::supportedEventNames())],
            'event_id' => ['required', 'string', 'max:191'],
            'occurred_at' => ['nullable', 'date'],
            'environment' => ['required', 'string', 'max:32'],
            'hostname' => ['required', 'string', 'max:191'],
            'pathname' => ['required', 'string', 'max:255'],
            'page_type' => ['required', 'string', 'max:120'],
            'referrer_host' => ['nullable', 'string', 'max:191'],
            'utm_source' => ['nullable', 'string', 'max:191'],
            'utm_medium' => ['nullable', 'string', 'max:191'],
            'utm_campaign' => ['nullable', 'string', 'max:191'],
            'utm_content' => ['nullable', 'string', 'max:191'],
            'utm_term' => ['nullable', 'string', 'max:191'],
            'visit_id' => ['nullable', 'string', 'max:191'],
            'pageview_id' => ['nullable', 'string', 'max:191'],
            'currency' => ['nullable', 'string', 'max:8'],
            'value' => ['nullable', 'numeric', 'between:-99999999.99,99999999.99'],
            'properties' => ['nullable', 'array'],
        ]);

        $environment = mb_strtolower(trim((string) $validated['environment']));

        if (! $this->analyticsEnabledForEnvironment($environment)) {
            return response()->json([
                'data' => [
                    'accepted' => false,
                    'ignored' => true,
                    'reason' => 'analytics_disabled_for_environment',
                ],
            ], 202);
        }

        $deduplicationKey = AnalyticsEvent::deduplicationKeyFor(
            $environment,
            (string) $validated['event_name'],
            (string) $validated['event_id'],
            $validated['properties'] ?? [],
        );

        $attributes = [
            'event_name' => $validated['event_name'],
            'event_id' => $validated['event_id'],
            'deduplication_key' => $deduplicationKey,
            'occurred_at' => filled($validated['occurred_at'] ?? null) ? Carbon::parse($validated['occurred_at']) : now(),
            'environment' => $environment,
            'hostname' => $validated['hostname'],
            'pathname' => $validated['pathname'],
            'page_type' => $validated['page_type'],
            'referrer_host' => $validated['referrer_host'] ?? null,
            'utm_source' => $validated['utm_source'] ?? null,
            'utm_medium' => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
            'utm_content' => $validated['utm_content'] ?? null,
            'utm_term' => $validated['utm_term'] ?? null,
            'visit_id' => $validated['visit_id'] ?? null,
            'pageview_id' => $validated['pageview_id'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'value' => $validated['value'] ?? null,
            'properties' => $validated['properties'] ?? null,
        ];

        \App\Jobs\ProcessAnalyticsEventJob::dispatch($attributes, $deduplicationKey);
 
        return response()->json([
            'data' => [
                'accepted' => true,
                'ignored' => false,
                'queued' => true,
            ],
        ], 202);
    }

    private function analyticsEnabledForEnvironment(string $environment): bool
    {
        if (! config('services.analytics.enabled', false)) {
            return false;
        }

        $acceptedEnvironments = array_values(array_filter(array_map(
            static fn ($value): string => mb_strtolower(trim((string) $value)),
            (array) config('services.analytics.accepted_environments', []),
        )));

        if ($acceptedEnvironments === [] || in_array('*', $acceptedEnvironments, true)) {
            return true;
        }

        return in_array($environment, $acceptedEnvironments, true);
    }
}