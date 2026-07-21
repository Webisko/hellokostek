@if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
    <div class="fi-topbar-theme-slot" aria-label="Przelacznik motywu panelu">
        <span class="fi-topbar-theme-slot-label">Tryb panelu</span>
        <x-filament-panels::theme-switcher />
    </div>
@endif