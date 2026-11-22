<div class="fi-brand-lockup">
    @if (! empty($logoUrl))
        <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="fi-brand-logo-img" />
    @endif
    <span class="fi-brand-name">{{ $brandName }}</span>
</div>
