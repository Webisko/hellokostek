@php
    $storefrontUrl = app(\App\Support\StoreSettings::class)->storefrontUrl();
@endphp

<div class="fi-topbar-utility-group">
    <a
        href="{{ $storefrontUrl }}"
        target="_blank"
        rel="noopener noreferrer"
        class="fi-topbar-storefront-link"
        title="Otwórz sklep w nowej karcie"
        aria-label="Otwórz sklep w nowej karcie"
    >
        <svg class="fi-topbar-storefront-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
        </svg>
        <span class="fi-topbar-storefront-label">Otwórz sklep</span>
    </a>

    @if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
        <div class="fi-topbar-theme-slot" aria-label="Przełącznik motywu panelu">
            <span class="fi-topbar-theme-slot-label">Tryb panelu</span>
            <x-filament-panels::theme-switcher />
        </div>
    @endif
</div>