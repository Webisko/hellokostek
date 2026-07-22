<?php

namespace App\Filament\Pages;

use App\Models\AnalyticsDailyAggregate;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AnalyticsOverview extends Page
{
    protected static ?string $slug = 'analityka';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Analityka sklepu';

    protected static ?string $title = 'Analityka sklepu';

    protected static \UnitEnum|string|null $navigationGroup = 'Analityka & system';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.analytics-overview';

    /**
     * @return array<int, array{label: string, value: string, description: string, context: string}>
     */
    public function overviewStats(): array
    {
        return [
            $this->statCard('Odslony 7 dni', $this->sumReport(AnalyticsDailyAggregate::REPORT_PAGEVIEWS_TOTAL), 'Laczna liczba odslon w ostatnich 7 dniach.', $this->aggregationContext()),
            $this->statCard('Wizyty 7 dni', $this->sumReport(AnalyticsDailyAggregate::REPORT_VISITS_TOTAL), 'Unikalne wizyty liczone po visit_id.', $this->aggregationContext()),
            $this->statCard('Zakupy 7 dni', $this->sumReport(AnalyticsDailyAggregate::REPORT_PURCHASES_TOTAL), 'Zdarzenia purchase z nowego storefrontu.', $this->aggregationContext()),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, description: string, context: string}>
     */
    public function conversionStats(): array
    {
        $checkoutStarts = $this->sumReport(AnalyticsDailyAggregate::REPORT_CHECKOUT_STARTS_TOTAL);
        $purchases = $this->sumReport(AnalyticsDailyAggregate::REPORT_PURCHASES_TOTAL);

        return [
            $this->statCard('Kasa -> Zakup', $this->formatRate($purchases, $checkoutStarts), 'Konwersja rozpoczętych płatności do sfinalizowanych zakupów.', sprintf('%s / %s', number_format($purchases, 0, ',', ' '), number_format($checkoutStarts, 0, ',', ' '))),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function topLandingPages(): array
    {
        return $this->topRows(AnalyticsDailyAggregate::REPORT_LANDING_PAGE_VIEWS);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function topProductPages(): array
    {
        return $this->topRows(AnalyticsDailyAggregate::REPORT_PRODUCT_PAGE_VIEWS);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function topReferrerHosts(): array
    {
        return $this->topRows(AnalyticsDailyAggregate::REPORT_REFERRER_HOST_VIEWS);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function topCampaigns(): array
    {
        return $this->topRows(AnalyticsDailyAggregate::REPORT_UTM_CAMPAIGN_VIEWS);
    }

    public function aggregationContext(): string
    {
        $lastAggregateDate = $this->latestAggregateDate();

        if ($lastAggregateDate === null) {
            return 'Brak zagregowanych danych.';
        }

        return 'Zakres: ostatnie 7 dni | Ostatnia agregacja: ' . $lastAggregateDate->format('Y-m-d');
    }

    private function sumReport(string $reportKey): int
    {
        return (int) $this->baseAggregateQuery()
            ->where('report_key', $reportKey)
            ->sum('value');
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function topRows(string $reportKey, int $limit = 5): array
    {
        return $this->baseAggregateQuery()
            ->where('report_key', $reportKey)
            ->selectRaw('dimension_value, SUM(value) as total_value')
            ->groupBy('dimension_value')
            ->orderByDesc('total_value')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->dimension_value,
                'value' => number_format((int) $row->total_value, 0, ',', ' '),
            ])
            ->all();
    }

    private function baseAggregateQuery(): Builder
    {
        return AnalyticsDailyAggregate::query()
            ->whereDate('aggregate_date', '>=', $this->windowStart()->toDateString())
            ->whereDate('aggregate_date', '<=', $this->windowEnd()->toDateString());
    }

    private function windowStart(): Carbon
    {
        return $this->latestAggregateDate()?->copy()->subDays(6)->startOfDay()
            ?? now()->subDays(6)->startOfDay();
    }

    private function windowEnd(): Carbon
    {
        return $this->latestAggregateDate()?->copy()->endOfDay()
            ?? now()->endOfDay();
    }

    private function latestAggregateDate(): ?Carbon
    {
        $value = AnalyticsDailyAggregate::query()->max('aggregate_date');

        return filled($value) ? Carbon::parse($value) : null;
    }

    /**
     * @return array{label: string, value: string, description: string, context: string}
     */
    private function statCard(string $label, int|float|string $value, string $description, string $context): array
    {
        return [
            'label' => $label,
            'value' => is_numeric($value) ? number_format((float) $value, 0, ',', ' ') : (string) $value,
            'description' => $description,
            'context' => $context,
        ];
    }

    private function formatRate(int $converted, int $base): string
    {
        if ($base <= 0) {
            return '0%';
        }

        return number_format(($converted / $base) * 100, 1, ',', ' ') . '%';
    }
}