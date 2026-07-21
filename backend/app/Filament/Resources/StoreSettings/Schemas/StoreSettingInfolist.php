<?php

namespace App\Filament\Resources\StoreSettings\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class StoreSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // 1. BRANDING I WYGLAD PANELU (Wspólne)
            Section::make('Branding i Wygląd Panelu')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('metadata.admin_brand_name')->label('Nazwa w panelu administratora'),
                    TextEntry::make('metadata.admin_logo_path')->label('Ścieżka do logo'),
                    TextEntry::make('metadata.admin_favicon_path')->label('Ścieżka do faviconek'),
                    TextEntry::make('metadata.admin_login_background_path')->label('Ścieżka do tła logowania'),
                ]),

            // 2. DYNAMICZNE MENU (Wspólne)
            Section::make('Zarządzanie Menu i Nawigacją')->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('metadata.navigation_groups')
                        ->label('Grupy menu')
                        ->schema([
                            TextEntry::make('name')->label('Nazwa (klucz)'),
                            TextEntry::make('label')->label('Wyświetlana etykieta'),
                            TextEntry::make('sort_order')->label('Kolejność'),
                        ])
                        ->columns(3),

                    RepeatableEntry::make('metadata.resources_navigation')
                        ->label('Zarządzanie zakładkami (Zasobami)')
                        ->schema([
                            TextEntry::make('resource')->label('Klasa zasobu'),
                            TextEntry::make('label')->label('Etykieta w menu'),
                            TextEntry::make('group')->label('Grupa menu'),
                            TextEntry::make('sort_order')->label('Kolejność'),
                            TextEntry::make('visible')
                                ->label('Widoczny')
                                ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                        ])
                        ->columns(5),
                ]),

            // 3. BEZPIECZEŃSTWO I SYSTEM (Wspólne)
            Section::make('Bezpieczeństwo i System')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('metadata.admin_path')->label('Ścieżka panelu administratora (URL)'),
                    TextEntry::make('metadata.security_headers_enabled')
                        ->label('Nagłówki bezpieczeństwa HTTP')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('metadata.content_security_policy')
                        ->label('Content Security Policy (CSP)')
                        ->columnSpanFull(),
                    TextEntry::make('global_noindex')
                        ->label('Blokuj indeksowanie (noindex)')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('maintenance_mode_enabled')
                        ->label('Tryb konserwacji')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('maintenance_mode_message')
                        ->label('Komunikat trybu konserwacji')
                        ->columnSpanFull(),
                    TextEntry::make('maintenance_mode_allowed_ips')
                        ->label('Dozwolone adresy IP')
                        ->columnSpanFull(),
                ]),

            // 4. POCZTA I E-MAIL (Wspólne)
            Section::make('Poczta i E-mail')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('support_email')->label('E-mail wsparcia'),
                    TextEntry::make('admin_notification_email')->label('E-mail powiadomień admina'),
                    TextEntry::make('mail_from_name')->label('Nazwa nadawcy e-mail'),
                    TextEntry::make('mail_from_address')->label('Adres nadawcy e-mail'),
                    TextEntry::make('metadata.resend_key')->label('Klucz API Resend'),
                    TextEntry::make('metadata.mail_safety_redirect')->label('Przekierowanie e-maili (Tryb testowy)'),
                ]),

            // 5. SEO I ANALITYKA (Wspólne)
            Section::make('SEO i Analityka')->columnSpanFull()
                ->columns(2)
                ->schema([
                    KeyValueEntry::make('seo')
                        ->label('SEO domyślne')
                        ->keyLabel('Tag')
                        ->valueLabel('Wartość'),
                    TextEntry::make('cookie_banner_enabled')
                        ->label('Baner cookies aktywny')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('google_tag_manager_id')->label('Google Tag Manager ID'),
                    TextEntry::make('google_analytics_id')->label('Google Analytics ID'),
                    TextEntry::make('facebook_pixel_id')->label('Facebook Pixel ID'),
                    TextEntry::make('cookie_banner_title')->label('Nagłówek baneru cookies')->columnSpanFull(),
                    TextEntry::make('cookie_banner_description')->label('Treść baneru cookies')->columnSpanFull(),
                    TextEntry::make('custom_head_scripts')->label('Skrypty <head>')->columnSpanFull(),
                ]),

            // 6. USTAWIENIA BLOGA (Wspólne)
            Section::make('Ustawienia Bloga / Wpisów')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('metadata.blog_posts_per_page')->label('Liczba wpisów na stronie'),
                    TextEntry::make('metadata.blog_comments_enabled')
                        ->label('Komentarze pod wpisami')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('metadata.blog_show_author')
                        ->label('Pokazuj autora')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('metadata.blog_show_date')
                        ->label('Pokazuj datę')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('metadata.blog_show_related')
                        ->label('Pokazuj powiązane wpisy')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                ]),

            // 7. GŁÓWNE E-COMMERCE (Tylko Ecommerce i LMS)
            Section::make('Ustawienia Sklepu (E-commerce)')
                ->visible(fn () => app(\App\Support\StoreSettings::class)->isEcommerce())
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('store_name')->label('Nazwa sklepu'),
                    TextEntry::make('currency')->label('Waluta'),
                    TextEntry::make('free_shipping_threshold')->label('Próg darmowej dostawy'),
                    TextEntry::make('wholesale_minimum_regular_price_multiplier')->label('Mnożnik minimum hurt'),
                    TextEntry::make('allow_guest_checkout')
                        ->label('Zakupy jako gość')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('cod_only_method')->label('Kod metody za pobraniem'),
                    TextEntry::make('eu_import_flat_duty_enabled')
                        ->label('Cło importowe UE')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    KeyValueEntry::make('exchange_rates')
                        ->label('Kursy wymiany walut')
                        ->keyLabel('Waluta')
                        ->valueLabel('Mnożnik'),
                ]),

            // 8. ODZYSKIWANIE KOSZYKÓW (Tylko Ecommerce i LMS)
            Section::make('Odzyskiwanie koszyków (Abandoned Carts)')
                ->visible(fn () => app(\App\Support\StoreSettings::class)->isEcommerce())
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('metadata.abandoned_cart_enabled')
                        ->label('Odzyskiwanie porzuconych koszyków')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('metadata.abandoned_cart_hours_threshold')->label('Czas wysyłki (w godzinach)'),
                ]),

            // 9. OPINIE I RECENZJE (Tylko Ecommerce i LMS)
            Section::make('Opinie i recenzje')
                ->visible(fn () => app(\App\Support\StoreSettings::class)->isEcommerce())
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('product_reviews_enabled')
                        ->label('Opinie o produktach')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('general_reviews_enabled')
                        ->label('Opinie ogólne o sklepie')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('general_reviews_source')->label('Źródło opinii ogólnych'),
                ]),

            // 10. OPINIE GOOGLE PLACES (Tylko Ecommerce i LMS)
            Section::make('Opinie Google Places')
                ->visible(fn () => app(\App\Support\StoreSettings::class)->isEcommerce())
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('metadata.google_places_api_key')->label('Google Places API Key'),
                    TextEntry::make('metadata.google_places_place_id')->label('Google Places Place ID'),
                    TextEntry::make('metadata.google_places_business_name')->label('Nazwa firmy Google Places')->columnSpanFull(),
                ]),

            // 11. BRAMKI PŁATNOŚCI (Tylko Ecommerce i LMS)
            Section::make('Bramki Płatności')
                ->visible(fn () => app(\App\Support\StoreSettings::class)->isEcommerce())
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('metadata.stripe_enabled')
                        ->label('Stripe włączony')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('metadata.stripe_key')->label('Stripe Public Key'),
                    TextEntry::make('metadata.stripe_secret')->label('Stripe Secret Key'),
                    TextEntry::make('metadata.stripe_webhook_secret')->label('Stripe Webhook Secret'),

                    TextEntry::make('metadata.przelewy24_enabled')
                        ->label('Przelewy24 włączone')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('metadata.przelewy24_merchant_id')->label('Przelewy24 Merchant ID'),
                    TextEntry::make('metadata.przelewy24_pos_id')->label('Przelewy24 POS ID'),
                ]),

            // 12. INTEGRACJE KURIERSKIE (Tylko Ecommerce i LMS)
            Section::make('Wysyłka i Kurierzy')
                ->visible(fn () => app(\App\Support\StoreSettings::class)->isEcommerce())
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('metadata.inpost_organization_id')->label('InPost ID organizacji'),
                    TextEntry::make('metadata.inpost_sender_email')->label('Email nadawcy Paczkomat'),
                    TextEntry::make('metadata.inpost_sender_phone')->label('Telefon nadawcy Paczkomat'),
                    TextEntry::make('metadata.inpost_sender_name')->label('Nazwa nadawcy Paczkomat'),

                    TextEntry::make('metadata.orlen_partner_id')->label('Orlen Paczka Partner ID'),
                    TextEntry::make('metadata.orlen_sender_email')->label('Email nadawcy Orlen Paczka'),
                    TextEntry::make('metadata.orlen_sender_phone')->label('Telefon nadawcy Orlen Paczka'),
                    TextEntry::make('metadata.orlen_sender_name')->label('Nazwa nadawcy Orlen Paczka'),
                ]),

            // 13. KSIĘGOWOŚĆ I FAKTUROWANIE (Tylko Ecommerce i LMS)
            Section::make('Księgowość i Faktury')
                ->visible(fn () => app(\App\Support\StoreSettings::class)->isEcommerce())
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('metadata.active_accounting_driver')->label('Aktywna platforma księgowości')->columnSpanFull(),
                    TextEntry::make('metadata.fakturownia_domain')
                        ->label('Fakturownia Domena')
                        ->visible(fn ($get) => $get('metadata.active_accounting_driver') === 'fakturownia'),
                    TextEntry::make('metadata.ifirma_username')
                        ->label('iFirma Email logowania')
                        ->visible(fn ($get) => $get('metadata.active_accounting_driver') === 'ifirma'),
                ]),

            // 14. LMS / KURSY (Tylko LMS)
            Section::make('Ustawienia LMS / Kursów')
                ->visible(fn () => app(\App\Support\StoreSettings::class)->isLms())
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('metadata.lms_lock_lesson_order')
                        ->label('Blokada kolejności lekcji')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('metadata.lms_generate_certificates')
                        ->label('Automatyczne certyfikaty PDF')
                        ->formatStateUsing(fn ($state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('metadata.lms_passing_score_percentage')->label('Próg zaliczenia quizów (%)'),
                ]),

            // Pozostałe Metadane
            Section::make('Pozostałe Metadane')->columnSpanFull()
                ->schema([
                    KeyValueEntry::make('metadata')
                        ->label('Metadane zaawansowane')
                        ->formatStateUsing(fn ($state) => is_array($state) ? Arr::dot($state) : $state),
                ]),
        ]);
    }
}
