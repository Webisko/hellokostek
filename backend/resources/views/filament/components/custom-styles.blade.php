@vite(['resources/css/app.css', 'resources/css/filament/admin/theme.css'])

@php
    $loginBg = app(\App\Support\StoreSettings::class)->adminLoginBackgroundUrl();
@endphp

<style>
    /* Table toolbar: search input on left, toolbar action icons (reorder, toggle columns, filters) on right */
    .fi-ta-header-toolbar {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
    }
    .fi-ta-header-toolbar > div:has(.fi-ta-search-field),
    .fi-ta-header-toolbar > .fi-ta-search-field-wrp {
        order: 1 !important;
        margin-right: auto !important;
        margin-left: 0 !important;
    }
    .fi-ta-header-toolbar > div:not(:has(.fi-ta-search-field)):not(.fi-ta-search-field-wrp) {
        order: 2 !important;
        margin-left: auto !important;
    }
</style>

@if($loginBg)
<style>
    body.fi-body.fi-simple-page {
        background-image: url('{{ $loginBg }}') !important;
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-attachment: fixed !important;
    }
    body.fi-body.fi-simple-page::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(9, 9, 11, 0.4); /* subtle dark overlay */
        z-index: -1;
    }
</style>
@endif

<script>
    (function () {
        try {
            const isOpen = localStorage.getItem('_x_isOpenDesktop');
            if (isOpen === 'false') {
                document.documentElement.classList.add('fi-sidebar-collapsed');
            }
        } catch (e) {}
    })();

    document.addEventListener('alpine:init', () => {
        window.Alpine.effect(() => {
            try {
                const isOpen = window.Alpine.store('sidebar').isOpenDesktop;
                if (isOpen === false) {
                    document.documentElement.classList.add('fi-sidebar-collapsed');
                } else {
                    document.documentElement.classList.remove('fi-sidebar-collapsed');
                }
            } catch (e) {}
        });
    });

    function initLiquidAuthButtons() {
        const simplePage = document.querySelector('body.fi-simple-page, .fi-simple-layout');
        if (!simplePage) return;

        if (!document.getElementById('goo-filter-svg')) {
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('id', 'goo-filter-svg');
            svg.style.display = 'none';
            svg.innerHTML = `
                <defs>
                    <filter id="goo">
                        <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur" />
                        <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7" result="goo" />
                        <feBlend in="SourceGraphic" in2="goo" />
                    </filter>
                </defs>
            `;
            document.body.appendChild(svg);
        }

        const submitBtns = simplePage.querySelectorAll('form button[type="submit"]');
        submitBtns.forEach(btn => {
            if (!btn.querySelector('.button__blobs')) {
                const blobs = document.createElement('div');
                blobs.className = 'button__blobs';
                blobs.innerHTML = '<div></div><div></div><div></div>';
                btn.appendChild(blobs);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLiquidAuthButtons);
    } else {
        initLiquidAuthButtons();
    }
    document.addEventListener('livewire:navigated', initLiquidAuthButtons);
    setTimeout(initLiquidAuthButtons, 300);
</script>

