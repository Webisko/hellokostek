<x-filament-panels::page>
    <div style="display: grid; gap: 0.75rem; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
        @foreach ($this->stats() as $stat)
            @php
                $badgeClass = match ($stat['tone']) {
                    'danger'  => 'shop-dashboard-badge shop-dashboard-badge--danger',
                    'warning' => 'shop-dashboard-badge shop-dashboard-badge--warning',
                    'info'    => 'shop-dashboard-badge shop-dashboard-badge--info',
                    'success' => 'shop-dashboard-badge shop-dashboard-badge--success',
                    default   => 'shop-dashboard-badge',
                };
                $toneLabel = match ($stat['tone']) {
                    'danger'  => 'Krytyczny',
                    'warning' => 'Uwaga',
                    'info'    => 'Info',
                    'success' => 'OK',
                    default   => 'Status',
                };
            @endphp
            <a
                href="{{ $stat['url'] }}"
                class="shop-dashboard-metric"
                style="text-decoration: none;"
                title="{{ $stat['description'] }}"
                aria-label="{{ $stat['label'] }}: {{ $stat['value'] }}. Status: {{ strtoupper($stat['tone']) }}. {{ $stat['description'] }} {{ $stat['context'] }}"
            >
                <div style="display: flex; align-items: start; justify-content: space-between; gap: 1rem;">
                    <div style="flex: 1 1 0; min-width: 0;">
                        <p class="shop-dashboard-metric-label" style="display: flex; align-items: center; gap: 0.25rem;">
                            {{ $stat['label'] }}
                            <span class="text-gray-400 cursor-help text-xs" style="cursor: help; color: #a1a1aa; font-size: 0.75rem;" title="{{ $stat['description'] }}">ⓘ</span>
                        </p>
                        <p class="shop-dashboard-metric-value">{{ $stat['value'] }}</p>
                    </div>
                    <span class="{{ $badgeClass }}">{{ $toneLabel }}</span>
                </div>
                @if (!empty($stat['context']))
                    <p class="shop-dashboard-metric-label" style="margin-top: 0.75rem;">{{ $stat['context'] }}</p>
                @endif
            </a>
        @endforeach
    </div>

    <div class="shop-page-scope">
        <p class="shop-page-scope-title">Zakres strony</p>
        <p class="shop-page-scope-text">
            Ten widok zbiera w jednym miejscu najważniejsze sygnały operatorskie z już wdrożonych obszarów: kolejki,
            logów integracji oraz maili transakcyjnych.
        </p>
    </div>
</x-filament-panels::page>