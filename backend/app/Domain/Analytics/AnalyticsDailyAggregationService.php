<?php

namespace App\Domain\Analytics;

use App\Models\AnalyticsDailyAggregate;
use App\Models\AnalyticsEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsDailyAggregationService
{
    /**
     * @return array{aggregate_date: string, environments: int, source_events: int, aggregates_written: int}
     */
    public function aggregateDate(Carbon $date): array
    {
        $aggregateDate = $date->copy()->startOfDay();

        $events = AnalyticsEvent::query()
            ->whereDate('occurred_at', $aggregateDate)
            ->orderBy('occurred_at')
            ->get();

        AnalyticsDailyAggregate::query()
            ->whereDate('aggregate_date', $aggregateDate)
            ->delete();

        $rows = $events
            ->groupBy(fn (AnalyticsEvent $event): string => (string) $event->environment)
            ->flatMap(fn (Collection $environmentEvents, string $environment): array => $this->buildEnvironmentRows($aggregateDate, $environment, $environmentEvents))
            ->values()
            ->all();

        if ($rows !== []) {
            AnalyticsDailyAggregate::query()->upsert(
                $rows,
                ['aggregate_date', 'environment', 'report_key', 'dimension', 'dimension_value'],
                ['value', 'updated_at'],
            );
        }

        return [
            'aggregate_date' => $aggregateDate->toDateString(),
            'environments' => $events->groupBy('environment')->count(),
            'source_events' => $events->count(),
            'aggregates_written' => count($rows),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildEnvironmentRows(Carbon $aggregateDate, string $environment, Collection $events): array
    {
        $pageViewEvents = $events->where('event_name', AnalyticsEvent::EVENT_PAGE_VIEW)->values();

        $rows = [
            $this->makeRow($aggregateDate, $environment, AnalyticsDailyAggregate::REPORT_PAGEVIEWS_TOTAL, $pageViewEvents->count()),
            $this->makeRow($aggregateDate, $environment, AnalyticsDailyAggregate::REPORT_VISITS_TOTAL, $this->countDistinctNonEmpty($events, 'visit_id')),
            $this->makeRow($aggregateDate, $environment, AnalyticsDailyAggregate::REPORT_CHECKOUT_STARTS_TOTAL, $events->where('event_name', AnalyticsEvent::EVENT_BEGIN_CHECKOUT)->count()),
            $this->makeRow($aggregateDate, $environment, AnalyticsDailyAggregate::REPORT_PURCHASES_TOTAL, $events->where('event_name', AnalyticsEvent::EVENT_PURCHASE)->count()),
        ];

        $landingPages = $pageViewEvents
            ->filter(fn (AnalyticsEvent $event): bool => filled($event->visit_id) && filled($event->pathname))
            ->groupBy('visit_id')
            ->map(fn (Collection $visitEvents): ?string => optional($visitEvents->sortBy('occurred_at')->first())->pathname)
            ->filter(fn (?string $pathname): bool => filled($pathname))
            ->countBy();

        foreach ($landingPages as $pathname => $value) {
            $rows[] = $this->makeRow($aggregateDate, $environment, AnalyticsDailyAggregate::REPORT_LANDING_PAGE_VIEWS, (int) $value, 'pathname', (string) $pathname);
        }

        $productPageViews = $pageViewEvents
            ->where('page_type', 'product_detail')
            ->filter(fn (AnalyticsEvent $event): bool => filled($event->pathname))
            ->countBy('pathname');

        foreach ($productPageViews as $pathname => $value) {
            $rows[] = $this->makeRow($aggregateDate, $environment, AnalyticsDailyAggregate::REPORT_PRODUCT_PAGE_VIEWS, (int) $value, 'pathname', (string) $pathname);
        }

        $referrerHostViews = $pageViewEvents
            ->filter(fn (AnalyticsEvent $event): bool => filled($event->referrer_host))
            ->countBy('referrer_host');

        foreach ($referrerHostViews as $host => $value) {
            $rows[] = $this->makeRow($aggregateDate, $environment, AnalyticsDailyAggregate::REPORT_REFERRER_HOST_VIEWS, (int) $value, 'referrer_host', (string) $host);
        }

        $utmCampaignViews = $pageViewEvents
            ->filter(fn (AnalyticsEvent $event): bool => filled($event->utm_campaign))
            ->countBy('utm_campaign');

        foreach ($utmCampaignViews as $campaign => $value) {
            $rows[] = $this->makeRow($aggregateDate, $environment, AnalyticsDailyAggregate::REPORT_UTM_CAMPAIGN_VIEWS, (int) $value, 'utm_campaign', (string) $campaign);
        }

        return $rows;
    }

    private function countDistinctNonEmpty(Collection $events, string $key): int
    {
        return $events
            ->map(fn (AnalyticsEvent $event): ?string => filled($event->{$key}) ? (string) $event->{$key} : null)
            ->filter()
            ->unique()
            ->count();
    }



    /**
     * @return array<string, mixed>
     */
    private function makeRow(
        Carbon $aggregateDate,
        string $environment,
        string $reportKey,
        int $value,
        string $dimension = '',
        string $dimensionValue = '',
    ): array {
        return [
            'aggregate_date' => $aggregateDate->toDateString(),
            'environment' => $environment,
            'report_key' => $reportKey,
            'dimension' => $dimension,
            'dimension_value' => $dimensionValue,
            'value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}