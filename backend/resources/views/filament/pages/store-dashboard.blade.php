<x-filament-panels::page>
    @php
        $heroStats = $this->heroStats();
        $operationalStats = $this->operationalStats();
        $toneBadge = static fn (string $tone): string => match ($tone) {
            'success' => 'OK',
            'warning' => 'Uwaga',
            'info' => 'Info',
            'danger' => 'Błąd',
            default => 'Status',
        };
        $toneBadgeClass = static fn (string $tone): string => match ($tone) {
            'success' => 'shop-dashboard-badge shop-dashboard-badge--success',
            'warning' => 'shop-dashboard-badge shop-dashboard-badge--warning',
            'info'    => 'shop-dashboard-badge shop-dashboard-badge--info',
            'danger'  => 'shop-dashboard-badge shop-dashboard-badge--danger',
            default   => 'shop-dashboard-badge',
        };
    @endphp

    <div class="shop-dashboard">
        <!-- 1. Główne wskaźniki (Metric Cards) na samej górze -->
        <section class="shop-dashboard-metrics">
            @foreach ($heroStats as $stat)
                <a href="{{ $stat['url'] }}" class="shop-dashboard-metric" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; min-height: 140px; padding: 1.25rem 1.5rem; text-decoration: none; transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shop-shadow-lg)'; this.style.borderColor='var(--shop-accent)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='var(--shop-border)';">
                    <p class="shop-dashboard-metric-label" style="margin: 0; line-height: 1.2; text-decoration: none;">{{ $stat['label'] }}</p>
                    <p class="shop-dashboard-metric-value" style="margin: auto 0; padding: 0.5rem 0; line-height: 1; font-size: 2rem; text-decoration: none;">{{ $stat['value'] }}</p>
                    <p class="shop-dashboard-metric-copy" style="margin: 0; line-height: 1.2; text-decoration: none;">{{ $stat['context'] }}</p>
                </a>
            @endforeach
        </section>

        <!-- 2. Dwu-kolumnowy układ operacyjny -->
        <div class="shop-dashboard-top">
            <!-- LEWA KOLUMNA: Sprzedaż i opinie -->
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                
                <!-- Ostatnie zamówienia -->
                <section class="shop-dashboard-panel shop-dashboard-side">
                    <div class="shop-dashboard-section-head">
                        <div>
                            <h3 class="shop-dashboard-section-title">Ostatnie zamówienia</h3>
                        </div>
                        <a href="/admin/zamowienia" class="shop-dashboard-pill">Wszystkie zamówienia</a>
                    </div>

                    <div class="shop-dashboard-ops-list" style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        @forelse ($this->recentOrders() as $order)
                            <a href="/admin/zamowienia?record={{ $order->id }}" class="shop-analytics-table-row" style="display: grid; grid-template-columns: 1.6fr 2fr 1.5fr 1.5fr 0.4fr; gap: 0.5rem; align-items: center; width: 100%; text-decoration: none; color: inherit;">
                                <!-- Kolumna 1: Numer zamówienia (do lewej) -->
                                <div style="text-align: left; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; width: 100%;">
                                    @php
                                        $displayNumber = strlen($order->number) > 15 ? substr($order->number, 0, 9) . '..' . substr($order->number, -4) : $order->number;
                                    @endphp
                                    <span class="shop-dashboard-op-label" style="font-weight: 700; font-size: 0.8rem; white-space: nowrap; display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; vertical-align: bottom;" title="{{ $order->number }}">
                                        #{{ $displayNumber }}
                                    </span>
                                </div>

                                <!-- Kolumna 2: Imię i nazwisko (wyśrodkowane) -->
                                <div style="text-align: center; justify-self: center; min-width: 0; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <span class="text-sm text-gray-600 truncate" style="display: inline-block; text-align: center; max-width: 100%; overflow: hidden; text-overflow: ellipsis; vertical-align: bottom;">
                                        {{ $order->customer_first_name }} {{ $order->customer_last_name ?: $order->customer_email }}
                                    </span>
                                </div>

                                <!-- Kolumna 3: Status (wyśrodkowane) -->
                                <div style="text-align: center; justify-self: center;">
                                    @php
                                        $statusBadgeClass = match ($order->status) {
                                            'placed' => 'shop-dashboard-badge shop-dashboard-badge--info',
                                            'processing' => 'shop-dashboard-badge shop-dashboard-badge--warning',
                                            'shipped' => 'shop-dashboard-badge shop-dashboard-badge--success',
                                            'completed', 'fulfilled' => 'shop-dashboard-badge shop-dashboard-badge--success',
                                            'cancelled' => 'shop-dashboard-badge shop-dashboard-badge--danger',
                                            default => 'shop-dashboard-badge',
                                        };
                                        $statusLabel = match ($order->status) {
                                            'placed' => 'Złożone',
                                            'processing' => 'W realizacji',
                                            'shipped' => 'Wysłane',
                                            'completed', 'fulfilled' => 'Ukończone',
                                            'cancelled' => 'Anulowane',
                                            default => $order->status,
                                        };
                                    @endphp
                                    <span class="{{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                </div>

                                <!-- Kolumna 4: Kwota (do prawej) -->
                                <div style="text-align: right; justify-self: end;">
                                    <span class="shop-analytics-table-value" style="font-weight: 700;">
                                        {{ number_format($order->total_amount / 100, 2, ',', ' ') }} PLN
                                    </span>
                                </div>

                                <!-- Kolumna 5: Strzałka (do prawej) -->
                                <div style="text-align: right; justify-self: end;">
                                    <span class="shop-dashboard-arrow" aria-hidden="true">→</span>
                                </div>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 py-2">Brak zamówień w systemie.</p>
                        @endforelse
                    </div>
                </section>

                <!-- Najnowsze opinie -->
                <section class="shop-dashboard-panel shop-dashboard-side">
                    <div class="shop-dashboard-section-head">
                        <div>
                            <h3 class="shop-dashboard-section-title">Najnowsze opinie</h3>
                        </div>
                        <a href="/admin/opinie-o-produktach" class="shop-dashboard-pill">Wszystkie opinie</a>
                    </div>

                    <div class="shop-dashboard-ops-list" style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        @forelse ($this->recentReviews() as $review)
                            <a href="/admin/opinie-o-produktach?record={{ $review->id }}" class="shop-analytics-table-row" style="justify-content: space-between; text-decoration: none; color: inherit; width: 100%;">
                                <div style="display: flex; flex-direction: column; min-width: 0; flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                        <span class="font-bold text-xs" style="color: var(--shop-text);">{{ $review->customer_name }}</span>
                                        <span class="text-xs text-gray-500">dla</span>
                                        <span class="text-xs font-semibold truncate" style="max-width: 150px; color: var(--shop-muted-text);" title="{{ $review->product?->name }}">
                                            {{ $review->product?->name ?: 'Produktu' }}
                                        </span>
                                        <div style="display: flex; gap: 0.05rem; color: #f59e0b;">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="w-3.5 h-3.5 fill-current {{ $i <= $review->rating ? 'text-amber-500' : 'text-gray-300' }}" viewBox="0 0 20 20" style="display: inline-block; width: 0.875rem; height: 0.875rem;">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @endfor
                                        </div>
                                        @if (!$review->is_approved)
                                            <span class="shop-dashboard-badge shop-dashboard-badge--warning" style="font-size: 0.55rem; padding: 0.05rem 0.3rem;">
                                                Oczekuje
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-600 truncate" style="margin-top: 0.25rem;">
                                        "{{ $review->comment ?: 'Brak komentarza' }}"
                                    </p>
                                </div>
                                <span class="shop-dashboard-arrow" aria-hidden="true">→</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 py-2">Brak opinii w systemie.</p>
                        @endforelse
                    </div>
                </section>

            </div>

            <!-- PRAWA KOLUMNA: Statusy i diagnostyka -->
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                
                <!-- Puls systemu (Wymaga uwagi) -->
                @if (count($operationalStats) > 0)
                    <section class="shop-dashboard-panel shop-dashboard-side">
                        <div class="shop-dashboard-section-head">
                            <div>
                                <h3 class="shop-dashboard-section-title">Wymaga uwagi</h3>
                            </div>
                        </div>

                        <div style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;">
                            @foreach ($operationalStats as $stat)
                                <a
                                    href="{{ $stat['url'] }}"
                                    class="shop-analytics-table-row"
                                    style="justify-content: space-between; text-decoration: none;"
                                    title="{{ $stat['description'] }}"
                                >
                                    <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0; flex: 1;">
                                        <span class="shop-dashboard-op-label" style="font-weight: 700; text-decoration: none;">
                                            {{ $stat['label'] }}
                                        </span>
                                        <span class="text-gray-400 cursor-help text-xs" style="cursor: help; color: #a1a1aa; font-size: 0.75rem;" title="{{ $stat['description'] }}">ⓘ</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                                        <span class="{{ $toneBadgeClass($stat['tone']) }}">{{ $toneBadge($stat['tone']) }}</span>
                                        <span class="shop-analytics-table-value" style="min-width: 40px; text-align: right; font-weight: 700;">
                                            {{ $stat['value'] }}
                                        </span>
                                        <span class="shop-dashboard-arrow" aria-hidden="true">→</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- Ostatnie zapytania o portrety -->
                <section class="shop-dashboard-panel shop-dashboard-side">
                    <div class="shop-dashboard-section-head">
                        <div>
                            <h3 class="shop-dashboard-section-title">Ostatnie zapytania o portrety</h3>
                        </div>
                        <a href="/admin/zapytania-kontaktowe" class="shop-dashboard-pill">Wszystkie zapytania</a>
                    </div>

                    <div class="shop-dashboard-ops-list" style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        @forelse ($this->recentInquiries() as $inquiry)
                            <a href="/admin/zapytania-kontaktowe?record={{ $inquiry->id }}" class="shop-analytics-table-row" style="justify-content: space-between; text-decoration: none; color: inherit; width: 100%;">
                                <div style="display: flex; flex-direction: column; min-width: 0; flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                        <span class="font-bold text-xs" style="color: var(--shop-text);">{{ $inquiry->name }}</span>
                                        <span class="text-xs text-gray-500">({{ $inquiry->email }})</span>
                                        @php
                                            $inquiryBadgeClass = match ($inquiry->status) {
                                                'new' => 'shop-dashboard-badge shop-dashboard-badge--danger',
                                                'in_progress' => 'shop-dashboard-badge shop-dashboard-badge--warning',
                                                'accepted' => 'shop-dashboard-badge shop-dashboard-badge--info',
                                                'completed' => 'shop-dashboard-badge shop-dashboard-badge--success',
                                                default => 'shop-dashboard-badge',
                                            };
                                            $inquiryStatusLabel = \App\Models\ContactInquiry::getStatuses()[$inquiry->status] ?? $inquiry->status;
                                        @endphp
                                        <span class="{{ $inquiryBadgeClass }}" style="font-size: 0.6rem; padding: 0.1rem 0.4rem;">
                                            {{ $inquiryStatusLabel }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 truncate" style="margin-top: 0.25rem;" title="{{ $inquiry->message }}">
                                        {{ $inquiry->subject ?: Str::limit($inquiry->message, 80) }}
                                    </p>
                                </div>
                                <span class="shop-dashboard-arrow" aria-hidden="true">→</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 py-2">Brak zapytań w systemie.</p>
                        @endforelse
                    </div>
                </section>

            </div>
        </div>
    </div>
</x-filament-panels::page>