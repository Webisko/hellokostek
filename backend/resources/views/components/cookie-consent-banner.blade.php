@props([
    'version' => '1.0.0',
    'privacyPolicyUrl' => '/polityka-prywatnosci'
])

<div id="cookie-consent-banner" class="fixed bottom-6 right-6 left-6 md:left-auto md:max-w-md bg-white/95 dark:bg-[#161615]/95 backdrop-blur-md border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl p-6 z-[9999] transform translate-y-20 opacity-0 transition-all duration-500 ease-out pointer-events-none">
    <!-- Widok główny (Podstawowy) -->
    <div id="cookie-main-view" class="space-y-4">
        <div class="flex items-start gap-3">
            <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded-xl text-zinc-700 dark:text-zinc-300">
                <!-- Ciasteczko ikona SVG -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm0 0"/><path d="M11.5 15.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm0 0"/><path d="M16.5 13.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm0 0"/><path d="M6 12a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 0"/><path d="M14.5 18.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm0 0"/></svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Dbamy o Twoją prywatność</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 leading-relaxed">
                    Używamy plików cookies, aby ułatwić Ci korzystanie z naszego serwisu oraz do celów statystycznych i marketingowych. Szczegóły znajdziesz w naszej 
                    <a href="{{ $privacyPolicyUrl }}" class="text-zinc-900 dark:text-white underline hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Polityce Prywatności</a>.
                </p>
            </div>
        </div>

        <!-- Symetryczne przyciski akceptacji/odrzucenia -->
        <div class="grid grid-cols-2 gap-3 pt-2">
            <button id="btn-cookie-reject" type="button" class="w-full py-2.5 px-4 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white text-xs font-medium rounded-xl transition-all duration-200 border border-transparent active:scale-[0.98]">
                Odrzuć wszystkie
            </button>
            <button id="btn-cookie-accept" type="button" class="w-full py-2.5 px-4 bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 text-xs font-medium rounded-xl transition-all duration-200 border border-transparent active:scale-[0.98] shadow-sm shadow-black/10">
                Akceptuj wszystkie
            </button>
        </div>

        <div class="text-center pt-1">
            <button id="btn-cookie-settings-toggle" type="button" class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">
                Ustawienia zaawansowane
            </button>
        </div>
    </div>

    <!-- Widok szczegółowy (Zgody granularne) -->
    <div id="cookie-settings-view" class="hidden space-y-4">
        <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">Ustawienia prywatności</h4>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
            Dostosuj swoje preferencje dotyczące plików cookies. Ciasteczka niezbędne są wymagane do poprawnego działania witryny.
        </p>

        <!-- Lista checkboxów -->
        <div class="space-y-3 py-2 border-y border-zinc-100 dark:border-zinc-800">
            <!-- Niezbędne (Zawsze ON) -->
            <div class="flex items-start justify-between gap-3">
                <div>
                    <label class="text-xs font-semibold text-zinc-900 dark:text-white block">Niezbędne</label>
                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block leading-tight mt-0.5">Wymagane do działania sesji, koszyka i bezpieczeństwa strony.</span>
                </div>
                <div class="relative inline-flex items-center">
                    <input type="checkbox" checked disabled class="sr-only peer">
                    <div class="w-8 h-4 bg-zinc-300 dark:bg-zinc-700 rounded-full peer-checked:bg-zinc-500 dark:peer-checked:bg-zinc-600 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-full"></div>
                </div>
            </div>

            <!-- Funkcjonalne -->
            <div class="flex items-start justify-between gap-3">
                <div>
                    <label for="cookie-opt-functional" class="text-xs font-semibold text-zinc-900 dark:text-white block cursor-pointer">Funkcjonalne</label>
                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block leading-tight mt-0.5">Zapamiętują preferencje (np. język, waluta, ciemny motyw).</span>
                </div>
                <div class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="cookie-opt-functional" class="sr-only peer">
                    <div class="w-8 h-4 bg-zinc-200 dark:bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-zinc-300 dark:after:bg-zinc-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-zinc-600 peer-checked:bg-zinc-900 dark:peer-checked:bg-white"></div>
                </div>
            </div>

            <!-- Analityczne -->
            <div class="flex items-start justify-between gap-3">
                <div>
                    <label for="cookie-opt-analytics" class="text-xs font-semibold text-zinc-900 dark:text-white block cursor-pointer">Analityczne i statystyczne</label>
                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block leading-tight mt-0.5">Mierzą ruch na stronie i pomagają nam optymalizować usługi.</span>
                </div>
                <div class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="cookie-opt-analytics" class="sr-only peer">
                    <div class="w-8 h-4 bg-zinc-200 dark:bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-zinc-300 dark:after:bg-zinc-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-zinc-600 peer-checked:bg-zinc-900 dark:peer-checked:bg-white"></div>
                </div>
            </div>

            <!-- Marketingowe -->
            <div class="flex items-start justify-between gap-3">
                <div>
                    <label for="cookie-opt-marketing" class="text-xs font-semibold text-zinc-900 dark:text-white block cursor-pointer">Marketingowe i reklamowe</label>
                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block leading-tight mt-0.5">Służą do personalizacji reklam (Google Ads, Meta Pixel) i remarketingu.</span>
                </div>
                <div class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="cookie-opt-marketing" class="sr-only peer">
                    <div class="w-8 h-4 bg-zinc-200 dark:bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-zinc-300 dark:after:bg-zinc-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-zinc-600 peer-checked:bg-zinc-900 dark:peer-checked:bg-white"></div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <button id="btn-cookie-back-to-main" type="button" class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors py-2">
                Wróć
            </button>
            <button id="btn-cookie-save-prefs" type="button" class="py-2 px-5 bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 text-xs font-semibold rounded-xl transition-all duration-200 active:scale-[0.98]">
                Zapisz preferencje
            </button>
        </div>
    </div>
</div>

<!-- Wyzwalacz zarządzania ciasteczkami (pływająca tarczka w rogu) -->
<button id="cookie-consent-trigger" type="button" class="fixed bottom-6 left-6 p-3 bg-white/95 dark:bg-[#161615]/95 border border-zinc-200 dark:border-zinc-800 rounded-full shadow-lg text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-all duration-300 z-[9998] hover:scale-110 active:scale-95 group" aria-label="Zarządzaj plikami cookies">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:rotate-12 transition-transform duration-300"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm0 0"/><path d="M11.5 15.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm0 0"/><path d="M16.5 13.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm0 0"/><path d="M6 12a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 0"/><path d="M14.5 18.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm0 0"/></svg>
</button>

<script>
    (function() {
        var banner = document.getElementById('cookie-consent-banner');
        var trigger = document.getElementById('cookie-consent-trigger');
        var mainView = document.getElementById('cookie-main-view');
        var settingsView = document.getElementById('cookie-settings-view');

        var btnReject = document.getElementById('btn-cookie-reject');
        var btnAccept = document.getElementById('btn-cookie-accept');
        var btnSettingsToggle = document.getElementById('btn-cookie-settings-toggle');
        var btnBackToMain = document.getElementById('btn-cookie-back-to-main');
        var btnSavePrefs = document.getElementById('btn-cookie-save-prefs');

        var optFunctional = document.getElementById('cookie-opt-functional');
        var optAnalytics = document.getElementById('cookie-opt-analytics');
        var optMarketing = document.getElementById('cookie-opt-marketing');

        var bannerVersion = '{{ $version }}';

        // Sprawdź istnienie tokenu i zgód
        var savedChoices = null;
        var consentToken = localStorage.getItem('cookie_consent_token');
        try {
            savedChoices = JSON.parse(localStorage.getItem('cookie_consent_choices'));
        } catch (e) {}

        if (!consentToken) {
            consentToken = 'consent_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            localStorage.setItem('cookie_consent_token', consentToken);
        }

        // Inicjalizacja stanów checkboxów na podstawie zapisanych wyborów
        if (savedChoices) {
            optFunctional.checked = !!savedChoices.functional;
            optAnalytics.checked = !!savedChoices.analytics;
            optMarketing.checked = !!savedChoices.marketing;
        } else {
            // Domyślnie wszystkie opcjonalne są wyłączone (OFF)
            optFunctional.checked = false;
            optAnalytics.checked = false;
            optMarketing.checked = false;
            
            // Pokaż baner, jeśli użytkownik nie dokonał jeszcze wyboru
            setTimeout(showBanner, 800);
        }

        // Zdarzenia
        btnSettingsToggle.addEventListener('click', function() {
            mainView.classList.add('hidden');
            settingsView.classList.remove('hidden');
        });

        btnBackToMain.addEventListener('click', function() {
            settingsView.classList.add('hidden');
            mainView.classList.remove('hidden');
        });

        btnAccept.addEventListener('click', function() {
            saveConsent({ necessary: true, functional: true, analytics: true, marketing: true });
        });

        btnReject.addEventListener('click', function() {
            saveConsent({ necessary: true, functional: false, analytics: false, marketing: false });
        });

        btnSavePrefs.addEventListener('click', function() {
            saveConsent({
                necessary: true,
                functional: optFunctional.checked,
                analytics: optAnalytics.checked,
                marketing: optMarketing.checked
            });
        });

        trigger.addEventListener('click', function() {
            if (banner.classList.contains('opacity-0')) {
                showBanner();
            } else {
                hideBanner();
            }
        });

        function showBanner() {
            banner.classList.remove('pointer-events-none', 'translate-y-20', 'opacity-0');
            banner.classList.add('translate-y-0', 'opacity-100');
        }

        function hideBanner() {
            banner.classList.remove('translate-y-0', 'opacity-100');
            banner.classList.add('pointer-events-none', 'translate-y-20', 'opacity-0');
        }

        function saveConsent(choices) {
            // Zapisz lokalnie
            localStorage.setItem('cookie_consent_choices', JSON.stringify(choices));

            // Zaktualizuj Google Consent Mode v2
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'ad_storage': choices.marketing ? 'granted' : 'denied',
                    'ad_user_data': choices.marketing ? 'granted' : 'denied',
                    'ad_personalization': choices.marketing ? 'granted' : 'denied',
                    'analytics_storage': choices.analytics ? 'granted' : 'denied',
                    'functional_storage': choices.functional ? 'granted' : 'denied',
                    'personalization_storage': choices.functional ? 'granted' : 'denied'
                });
                
                // Prześlij zdarzenie do dataLayer dla GTM
                window.dataLayer.push({
                    'event': 'cookie_consent_updated',
                    'consent_choices': choices
                });
            }

            // Wyślij log do bazy danych (API)
            fetch('/api/cookie-consents', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    consent_token: consentToken,
                    consent_choices: choices,
                    banner_version: bannerVersion
                })
            })
            .then(function(res) {
                if (!res.ok) console.warn('Nie udało się zapisać zgody cookies w bazie.');
            })
            .catch(function(err) {
                console.error('Błąd zapisu zgody cookies:', err);
            });

            hideBanner();
        }

        function getCsrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }
    })();
</script>
