<!-- Google Consent Mode v2 Default State -->
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag() {
        window.dataLayer.push(arguments);
    }

    // Odczytaj zapisaną zgodę z localStorage
    (function() {
        var consent = null;
        try {
            consent = JSON.parse(localStorage.getItem('cookie_consent_choices'));
        } catch (e) {}

        var defaults = {
            'ad_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
            'analytics_storage': 'denied',
            'functional_storage': 'denied',
            'personalization_storage': 'denied'
        };

        if (consent) {
            defaults.ad_storage = consent.marketing ? 'granted' : 'denied';
            defaults.ad_user_data = consent.marketing ? 'granted' : 'denied';
            defaults.ad_personalization = consent.marketing ? 'granted' : 'denied';
            defaults.analytics_storage = consent.analytics ? 'granted' : 'denied';
            defaults.functional_storage = consent.functional ? 'granted' : 'denied';
            defaults.personalization_storage = consent.functional ? 'granted' : 'denied';
        }

        gtag('consent', 'default', defaults);
        
        // Zdarzenie o załadowaniu domyślnych zgód
        window.dataLayer.push({
            'event': 'consent_default_loaded',
            'consent_choices': consent || { necessary: true, analytics: false, functional: false, marketing: false }
        });
    })();
</script>
