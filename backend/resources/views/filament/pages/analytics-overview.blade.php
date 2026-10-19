<x-filament-panels::page>
    {{-- KPI METRICS --}}
    <div class="shop-dashboard-metrics">
        @foreach ($this->overviewStats() as $stat)
            <div class="shop-dashboard-metric">
                <p class="shop-dashboard-metric-label">{{ $stat['label'] }}</p>
                <p class="shop-dashboard-metric-value">{{ $stat['value'] }}</p>
                <p class="shop-dashboard-metric-copy">{{ $stat['description'] }}</p>
                @if (!empty($stat['context']))
                    <p class="shop-dashboard-metric-label" style="margin-top: 0.75rem;">{{ $stat['context'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- CONVERSION METRICS --}}
    <div style="display: grid; gap: 0.75rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-top: 1.25rem;">
        @foreach ($this->conversionStats() as $stat)
            <div class="shop-dashboard-metric">
                <p class="shop-dashboard-metric-label">{{ $stat['label'] }}</p>
                <p class="shop-dashboard-metric-value">{{ $stat['value'] }}</p>
                <p class="shop-dashboard-metric-copy">{{ $stat['description'] }}</p>
                @if (!empty($stat['context']))
                    <p class="shop-dashboard-metric-label" style="margin-top: 0.75rem;">{{ $stat['context'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ANALYTICS TABLES: 2-column grid --}}
    <div style="display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-top: 1.25rem;">

        <div class="shop-dashboard-panel shop-dashboard-side">
            <h3 class="shop-section-title">Najczęściej odwiedzane strony wejściowe</h3>
            <div style="display: grid; gap: 0.5rem;">
                @forelse ($this->topLandingPages() as $row)
                    <div class="shop-analytics-table-row">
                        <span class="shop-analytics-table-label">{{ $row['label'] }}</span>
                        <span class="shop-analytics-table-value">{{ $row['value'] }}</span>
                    </div>
                @empty
                    <p class="shop-dashboard-metric-copy">Brak zagregowanych stron wejściowych.</p>
                @endforelse
            </div>
        </div>

        <div class="shop-dashboard-panel shop-dashboard-side">
            <h3 class="shop-section-title">Najczęściej odwiedzane strony produktowe</h3>
            <div style="display: grid; gap: 0.5rem;">
                @forelse ($this->topProductPages() as $row)
                    <div class="shop-analytics-table-row">
                        <span class="shop-analytics-table-label">{{ $row['label'] }}</span>
                        <span class="shop-analytics-table-value">{{ $row['value'] }}</span>
                    </div>
                @empty
                    <p class="shop-dashboard-metric-copy">Brak zagregowanych stron produktowych.</p>
                @endforelse
            </div>
        </div>

        <div class="shop-dashboard-panel shop-dashboard-side">
            <h3 class="shop-section-title">Najważniejsze źródła odesłań</h3>
            <div style="display: grid; gap: 0.5rem;">
                @forelse ($this->topReferrerHosts() as $row)
                    <div class="shop-analytics-table-row">
                        <span class="shop-analytics-table-label">{{ $row['label'] }}</span>
                        <span class="shop-analytics-table-value">{{ $row['value'] }}</span>
                    </div>
                @empty
                    <p class="shop-dashboard-metric-copy">Brak zagregowanych źródeł odesłań.</p>
                @endforelse
            </div>
        </div>

        <div class="shop-dashboard-panel shop-dashboard-side">
            <h3 class="shop-section-title">Najskuteczniejsze kampanie UTM</h3>
            <div style="display: grid; gap: 0.5rem;">
                @forelse ($this->topCampaigns() as $row)
                    <div class="shop-analytics-table-row">
                        <span class="shop-analytics-table-label">{{ $row['label'] }}</span>
                        <span class="shop-analytics-table-value">{{ $row['value'] }}</span>
                    </div>
                @empty
                    <p class="shop-dashboard-metric-copy">Brak zagregowanych kampanii.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- PAGE SCOPE --}}
    <div class="shop-page-scope">
        <p class="shop-page-scope-title">Zakres strony</p>
        <p class="shop-page-scope-text">
            Ten widok prezentuje statystyki ruchu w sklepie na podstawie dziennych agregatów: odsłony, wizyty, konwersje zakupowe,
            najczęściej odwiedzane podstrony, źródła ruchu oraz kampanie UTM.
        </p>
    </div>
</x-filament-panels::page>