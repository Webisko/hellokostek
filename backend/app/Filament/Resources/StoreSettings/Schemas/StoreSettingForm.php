<?php

namespace App\Filament\Resources\StoreSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class StoreSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        $isEcommerce = app(\App\Support\StoreSettings::class)->isEcommerce();
        $isLms = app(\App\Support\StoreSettings::class)->isLms();

        return $schema->components([
            Tabs::make('Ustawienia')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Wygląd i Menu')
                        ->schema([
                            // 1. BRANDING I WYGLAD PANELU (Wspólne)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('metadata.admin_brand_name')
                                        ->label('Nazwa w panelu administratora')
                                        ->placeholder('np. Panel CMS')
                                        ->maxLength(255),
                                    FileUpload::make('metadata.admin_logo_path')
                                        ->label('Logo w panelu administratora')
                                        ->image()
                                        ->directory('branding')
                                        ->visibility('public'),
                                    FileUpload::make('metadata.admin_favicon_path')
                                        ->label('Favicon panelu')
                                        ->image()
                                        ->directory('branding')
                                        ->visibility('public'),
                                    FileUpload::make('metadata.admin_login_background_path')
                                        ->label('Tło ekranu logowania')
                                        ->image()
                                        ->directory('branding')
                                        ->visibility('public')
                                        ->columnSpanFull(),
                                ]),

                            // 2. DYNAMICZNE MENU (Wspólne)
                            Group::make()
                                ->schema([
                                    Repeater::make('metadata.navigation_groups')
                                        ->label('Grupy menu')
                                        ->helperText('Ustal nazwy grup menu, ich kolejność (sortowanie od najniższej) oraz wyświetlaną nazwę (etykietę).')
                                        ->columns(3)
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Nazwa grupy (klucz systemowy)')
                                                ->required(),
                                            TextInput::make('label')
                                                ->label('Wyświetlana etykieta grupy')
                                                ->required(),
                                            TextInput::make('sort_order')
                                                ->label('Kolejność')
                                                ->numeric()
                                                ->required(),
                                        ])
                                        ->defaultItems(5)
                                        ->columnSpanFull(),

                                    Repeater::make('metadata.resources_navigation')
                                        ->label('Zarządzanie zakładkami (Zasobami)')
                                        ->helperText('Zmieniaj nazwy, przypisuj do innych grup, zmieniaj kolejność oraz włączaj/wyłączaj widoczność poszczególnych zakładek.')
                                        ->columns(5)
                                        ->schema([
                                            TextInput::make('resource')
                                                ->label('Zasób (klasa)')
                                                ->disabled()
                                                ->dehydrated()
                                                ->required(),
                                            TextInput::make('label')
                                                ->label('Etykieta w menu')
                                                ->required(),
                                            Select::make('group')
                                                ->label('Grupa menu')
                                                ->options(function ($get) {
                                                    $groups = $get('../../navigation_groups') ?? [];
                                                    $options = [];
                                                    foreach ($groups as $group) {
                                                        if (filled($group['name'] ?? null)) {
                                                            $options[$group['name']] = $group['label'] ?: $group['name'];
                                                        }
                                                    }
                                                    return $options;
                                                 })
                                                ->required(),
                                            TextInput::make('sort_order')
                                                ->label('Kolejność')
                                                ->numeric()
                                                ->required(),
                                            Toggle::make('visible')
                                                ->label('Widoczny')
                                                ->default(true),
                                        ])
                                        ->addable(false)
                                        ->deletable(false)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('System i Poczta')
                        ->schema([
                            // 3. BEZPIECZEŃSTWO I SYSTEM (Wspólne)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('metadata.admin_path')
                                        ->label('Ścieżka panelu administratora (URL)')
                                        ->placeholder('admin')
                                        ->helperText('Zmień domyślny adres panelu /admin na niestandardowy np. sekretny-cms dla zwiększenia bezpieczeństwa.')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Toggle::make('metadata.security_headers_enabled')
                                        ->label('Włącz nagłówki bezpieczeństwa HTTP')
                                        ->default(true),
                                    Textarea::make('metadata.content_security_policy')
                                        ->label('Content Security Policy (CSP)')
                                        ->placeholder("np. default-src 'self'; script-src 'self' 'unsafe-inline';")
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    Textarea::make('metadata.robots_txt')
                                        ->label('Zawartość pliku robots.txt')
                                        ->placeholder("User-agent: *\nDisallow: /admin/\nSitemap: " . url('sitemap.xml'))
                                        ->rows(5)
                                        ->columnSpanFull(),
                                    Toggle::make('global_noindex')
                                        ->label('Zablokuj indeksowanie całej strony (noindex)')
                                        ->default(false)
                                        ->columnSpanFull(),
                                    Toggle::make('maintenance_mode_enabled')
                                        ->label('Włącz tryb konserwacji (strona w budowie)')
                                        ->default(false)
                                        ->reactive()
                                        ->columnSpanFull(),
                                    Textarea::make('maintenance_mode_message')
                                        ->label('Komunikat trybu konserwacji')
                                        ->visible(fn ($get) => (bool) $get('maintenance_mode_enabled'))
                                        ->columnSpanFull(),
                                    TextInput::make('maintenance_mode_allowed_ips')
                                        ->label('Dozwolone adresy IP (oddzielone przecinkami)')
                                        ->visible(fn ($get) => (bool) $get('maintenance_mode_enabled'))
                                        ->placeholder('np. 127.0.0.1, 192.168.1.100')
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.newsletter_webhook_url')
                                        ->label('Webhook URL subskrypcji newslettera (np. Make, Zapier, Mailerlite)')
                                        ->url()
                                        ->placeholder('https://...')
                                        ->columnSpanFull(),
                                ]),

                            // 4. POCZTA I E-MAIL (Wspólne)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('support_email')->label('E-mail wsparcia')->email()->maxLength(255),
                                    TextInput::make('admin_notification_email')->label('E-mail powiadomień admina')->email()->maxLength(255),
                                    TextInput::make('mail_from_name')->label('Nazwa nadawcy e-mail')->maxLength(255),
                                    TextInput::make('mail_from_address')->label('Adres nadawcy e-mail')->email()->maxLength(255),
                                    TextInput::make('metadata.resend_key')
                                        ->label('Klucz API Resend')
                                        ->placeholder('re_...')
                                        ->maxLength(255),
                                    TextInput::make('metadata.mail_safety_redirect')
                                        ->label('Przekierowanie e-maili (Tryb testowy)')
                                        ->email()
                                        ->helperText('Jeśli podasz tutaj adres e-mail, wszystkie wiadomości wychodzące ze strony zostaną przekierowane na ten adres.')
                                        ->maxLength(255),
                                 ]),
                        ]),

                    Tabs\Tab::make('SEO i Blog')
                        ->schema([
                            // 5. SEO I ANALITYKA (Wspólne)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    KeyValue::make('seo')
                                        ->label('SEO domyślne')
                                        ->keyLabel('Tag')
                                        ->valueLabel('Wartość'),
                                    Toggle::make('cookie_banner_enabled')
                                        ->label('Włącz baner cookies na froncie')
                                        ->default(false)
                                        ->columnSpanFull(),
                                    TextInput::make('google_tag_manager_id')
                                        ->label('Google Tag Manager ID')
                                        ->placeholder('np. GTM-XXXXXX')
                                        ->maxLength(50),
                                    TextInput::make('google_analytics_id')
                                        ->label('Google Analytics ID')
                                        ->placeholder('np. G-XXXXXX')
                                        ->maxLength(50),
                                    TextInput::make('facebook_pixel_id')
                                        ->label('Facebook Pixel ID')
                                        ->placeholder('np. 1234567890')
                                        ->maxLength(50)
                                        ->columnSpanFull(),
                                    TextInput::make('cookie_banner_title')
                                        ->label('Nagłówek baneru cookies')
                                        ->placeholder('np. Szanujemy Twoją prywatność')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Textarea::make('cookie_banner_description')
                                        ->label('Treść baneru cookies')
                                        ->placeholder('np. Używamy plików cookie w celu...')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    Textarea::make('custom_head_scripts')
                                        ->label('Niestandardowe skrypty w sekcji <head>')
                                        ->placeholder('np. <script>...</script>')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),

                            // 6. USTAWIENIA BLOGA (Wspólne)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('metadata.blog_posts_per_page')
                                        ->label('Liczba wpisów na stronie (paginacja)')
                                        ->numeric()
                                        ->default(10)
                                        ->required(),
                                    Toggle::make('metadata.blog_comments_enabled')
                                        ->label('Włącz komentarze pod wpisami')
                                        ->default(false),
                                    Toggle::make('metadata.blog_show_author')
                                        ->label('Pokazuj autora wpisu')
                                        ->default(true),
                                    Toggle::make('metadata.blog_show_date')
                                        ->label('Pokazuj datę publikacji')
                                        ->default(true),
                                    Toggle::make('metadata.blog_show_related')
                                        ->label('Pokazuj powiązane wpisy')
                                        ->default(true),
                                 ]),
                        ]),

                    Tabs\Tab::make('E-commerce')
                        ->visible($isEcommerce)
                        ->schema([
                            // 7. GŁÓWNE E-COMMERCE (Tylko Ecommerce i LMS)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('store_name')->label('Nazwa sklepu')->required()->maxLength(255),
                                    TextInput::make('currency')->label('Waluta')->required()->minLength(3)->maxLength(3)
                                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? mb_strtoupper(trim($state)) : null),
                                    TextInput::make('free_shipping_threshold')->label('Próg darmowej dostawy (PLN)')->numeric()->required()->minValue(0)
                                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                        ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                                    TextInput::make('wholesale_minimum_regular_price_multiplier')->label('Mnożnik minimum hurt')->numeric()->required()->minValue(0)->maxValue(1),
                                    Toggle::make('allow_guest_checkout')->label('Zezwalaj na zakupy jako gość')->default(true),
                                    TextInput::make('cod_only_method')->label('Kod metody tylko za pobraniem')->maxLength(255),
                                    Toggle::make('eu_import_flat_duty_enabled')->label('Włącz cło importowe UE (ryczałt 3 EUR)')->default(false),
                                    KeyValue::make('exchange_rates')
                                        ->label('Kursy wymiany walut (np. EUR -> 0.23)')
                                        ->keyLabel('Waluta')
                                        ->valueLabel('Mnożnik (1 PLN = X waluty)')
                                        ->columnSpanFull(),
                                ]),

                            // 8. ODZYSKIWANIE KOSZYKÓW (Tylko Ecommerce i LMS)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    Toggle::make('metadata.abandoned_cart_enabled')
                                        ->label('Włącz odzyskiwanie porzuconych koszyków')
                                        ->default(true),
                                    TextInput::make('metadata.abandoned_cart_hours_threshold')
                                        ->label('Czas wysyłki po porzuceniu (w godzinach)')
                                        ->numeric()
                                        ->default(2)
                                        ->required(),
                                ]),

                            // Metody wysyłki
                            Group::make()
                                ->schema([
                                    Repeater::make('shipping_zones')
                                        ->label('Strefy wysyłki')
                                        ->helperText('Zdefiniuj strefy geograficzne (np. krajowe, międzynarodowe) i przypisz do nich kraje (kody ISO oddzielone przecinkami, np. PL lub DE, FR, ES).')
                                        ->columns(3)
                                        ->schema([
                                            TextInput::make('code')
                                                ->label('Kod strefy')
                                                ->placeholder('np. PL, EU')
                                                ->required(),
                                            TextInput::make('name')
                                                ->label('Nazwa strefy')
                                                ->placeholder('np. Polska, Unia Europejska')
                                                ->required(),
                                            TextInput::make('countries')
                                                ->label('Kraje (kody ISO)')
                                                ->placeholder('np. PL lub DE, FR, ES')
                                                ->required(),
                                        ])
                                        ->columnSpanFull(),

                                    Repeater::make('shipping_methods')
                                        ->label('Metody wysyłki')
                                        ->helperText('Zarządzaj dostępnymi metodami wysyłki oraz zdefiniuj stawki dynamiczne na podstawie wagi i wartości koszyka.')
                                        ->schema([
                                            TextInput::make('code')
                                                ->label('Kod metody wysyłki')
                                                ->placeholder('np. flat_rate:courier')
                                                ->required(),
                                            TextInput::make('name')
                                                ->label('Nazwa wyświetlana')
                                                ->placeholder('np. Kurier DPD')
                                                ->required(),
                                            Toggle::make('supports_cod')
                                                ->label('Płatność za pobraniem (COD)')
                                                ->default(false),
                                            Toggle::make('requires_delivery_point')
                                                ->label('Wymaga punktu odbioru')
                                                ->default(false),
                                            TextInput::make('amount')
                                                ->label('Domyślna cena bazowa (PLN)')
                                                ->numeric()
                                                ->default(0)
                                                ->required()
                                                ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                                ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                                            Repeater::make('rates')
                                                ->label('Progi i stawki dostawy dla stref')
                                                ->columns(6)
                                                ->schema([
                                                    Select::make('zone_code')
                                                        ->label('Strefa')
                                                        ->options(function ($get) {
                                                            $zones = $get('../../../shipping_zones') ?? [];
                                                            $options = [];
                                                            foreach ($zones as $zone) {
                                                                if (filled($zone['code'] ?? null)) {
                                                                    $options[$zone['code']] = $zone['name'] ?: $zone['code'];
                                                                }
                                                            }
                                                            if (empty($options)) {
                                                                $options['PL'] = 'Polska';
                                                            }
                                                            return $options;
                                                        })
                                                        ->required(),
                                                    TextInput::make('min_weight')
                                                        ->label('Waga od (kg)')
                                                        ->numeric()
                                                        ->default(0)
                                                        ->required(),
                                                    TextInput::make('max_weight')
                                                        ->label('Waga do (kg)')
                                                        ->numeric()
                                                        ->nullable(),
                                                    TextInput::make('min_value')
                                                        ->label('Koszyk od (PLN)')
                                                        ->numeric()
                                                        ->default(0)
                                                        ->required()
                                                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                                        ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                                                    TextInput::make('max_value')
                                                        ->label('Koszyk do (PLN)')
                                                        ->numeric()
                                                        ->nullable()
                                                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                                        ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                                                    TextInput::make('amount')
                                                        ->label('Cena (PLN)')
                                                        ->numeric()
                                                        ->default(0)
                                                        ->required()
                                                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                                        ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                                                    Toggle::make('free_shipping')
                                                        ->label('Darmowa dostawa')
                                                        ->default(false)
                                                        ->columnSpanFull(),
                                                ])
                                                ->columnSpanFull(),
                                        ])
                                        ->columnSpanFull(),
                                 ])
                                ->columnSpanFull(),
                        ]),

                    Tabs\Tab::make('Płatności i Dostawa')
                        ->visible($isEcommerce)
                        ->schema([
                            // 11. BRAMKI PŁATNOŚCI (Tylko Ecommerce i LMS)
                            Group::make()
                                ->schema([
                                    Toggle::make('metadata.stripe_enabled')->label('Stripe włączony')->default(true),
                                    TextInput::make('metadata.stripe_key')->label('Stripe Public Key (Publishable)')->maxLength(255),
                                    TextInput::make('metadata.stripe_secret')->label('Stripe Secret Key')->maxLength(255),
                                    TextInput::make('metadata.stripe_webhook_secret')->label('Stripe Webhook Secret')->maxLength(255),

                                    Toggle::make('metadata.przelewy24_enabled')->label('Przelewy24 włączone')->default(true),
                                    TextInput::make('metadata.przelewy24_merchant_id')->label('Przelewy24 Merchant ID')->maxLength(50),
                                    TextInput::make('metadata.przelewy24_pos_id')->label('Przelewy24 POS ID')->maxLength(50),
                                    TextInput::make('metadata.przelewy24_crc')->label('Przelewy24 CRC Key')->maxLength(255),
                                    TextInput::make('metadata.przelewy24_api_key')->label('Przelewy24 API Key')->maxLength(255),
                                    TextInput::make('metadata.przelewy24_api_base_url')->label('Przelewy24 API Base URL')->maxLength(255),
                                    TextInput::make('metadata.przelewy24_redirect_base_url')->label('Przelewy24 Redirect Base URL')->maxLength(255),
                                    TextInput::make('metadata.przelewy24_callback_token')->label('Przelewy24 Callback Token')->maxLength(255),
                                ]),

                            // 12. INTEGRACJE KURIERSKIE (Tylko Ecommerce i LMS)
                            Group::make()
                                ->schema([
                                    Toggle::make('metadata.inpost_sandbox')->label('InPost Sandbox (Testowy)')->default(true),
                                    TextInput::make('metadata.inpost_organization_id')->label('InPost Organization ID')->maxLength(50),
                                    TextInput::make('metadata.inpost_token')->label('InPost API Token')->maxLength(255),
                                    TextInput::make('metadata.inpost_sender_email')->label('Email nadawcy Paczkomat')->email()->maxLength(255),
                                    TextInput::make('metadata.inpost_sender_phone')->label('Telefon nadawcy Paczkomat')->maxLength(50),
                                    TextInput::make('metadata.inpost_sender_name')->label('Imię i Nazwisko nadawcy Paczkomat')->maxLength(255),
                                    TextInput::make('metadata.inpost_sender_company')->label('Nazwa firmy nadawcy Paczkomat')->maxLength(255),
                                    TextInput::make('metadata.inpost_sender_street')->label('Ulica nadawcy Paczkomat')->maxLength(255),
                                    TextInput::make('metadata.inpost_sender_building')->label('Budynek nadawcy Paczkomat')->maxLength(20),
                                    TextInput::make('metadata.inpost_sender_city')->label('Miasto nadawcy Paczkomat')->maxLength(255),
                                    TextInput::make('metadata.inpost_sender_postcode')->label('Kod pocztowy nadawcy Paczkomat')->maxLength(20),

                                    Toggle::make('metadata.orlen_sandbox')->label('Orlen Paczka Sandbox (Testowy)')->default(true),
                                    TextInput::make('metadata.orlen_partner_id')->label('Orlen Paczka Partner ID')->maxLength(50),
                                    TextInput::make('metadata.orlen_partner_key')->label('Orlen Paczka Partner Key')->maxLength(255),
                                    TextInput::make('metadata.orlen_sender_email')->label('Email nadawcy Orlen Paczka')->email()->maxLength(255),
                                    TextInput::make('metadata.orlen_sender_phone')->label('Telefon nadawcy Orlen Paczka')->maxLength(50),
                                    TextInput::make('metadata.orlen_sender_name')->label('Imię i Nazwisko nadawcy Orlen Paczka')->maxLength(255),
                                    TextInput::make('metadata.orlen_sender_company')->label('Nazwa firmy nadawcy Orlen Paczka')->maxLength(255),
                                    TextInput::make('metadata.orlen_sender_street')->label('Ulica nadawcy Orlen Paczka')->maxLength(255),
                                    TextInput::make('metadata.orlen_sender_building')->label('Budynek nadawcy Orlen Paczka')->maxLength(20),
                                    TextInput::make('metadata.orlen_sender_city')->label('Miasto nadawcy Orlen Paczka')->maxLength(255),
                                    TextInput::make('metadata.orlen_sender_postcode')->label('Kod pocztowy nadawcy Orlen Paczka')->maxLength(20),
                                 ]),
                        ]),

                    Tabs\Tab::make('Księgowość i Opinie')
                        ->visible($isEcommerce)
                        ->schema([
                            // 9. OPINIE I RECENZJE (Tylko Ecommerce i LMS)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    Toggle::make('product_reviews_enabled')
                                        ->label('Opinie o produktach włączone')
                                        ->default(true),
                                    Toggle::make('general_reviews_enabled')
                                        ->label('Opinie ogólne o sklepie włączone')
                                        ->default(true)
                                        ->reactive(),
                                    Select::make('general_reviews_source')
                                        ->label('Źródło opinii ogólnych')
                                        ->options([
                                            'google' => 'Tylko Google Reviews',
                                            'site' => 'Tylko strona sklepu',
                                            'both' => 'Oba źródła jednocześnie (Google + Strona)',
                                        ])
                                        ->default('both')
                                        ->visible(fn ($get) => (bool) $get('general_reviews_enabled'))
                                        ->required(),
                                 ]),

                            // 10. OPINIE GOOGLE PLACES (Tylko Ecommerce i LMS)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('metadata.google_places_api_key')
                                        ->label('Google Places API Key')
                                        ->maxLength(255),
                                    TextInput::make('metadata.google_places_place_id')
                                        ->label('Google Places Place ID')
                                        ->maxLength(255),
                                    TextInput::make('metadata.google_places_business_name')
                                        ->label('Nazwa firmy Google Places')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                 ]),

                            // 13. KSIĘGOWOŚĆ I FAKTUROWANIE (Tylko Ecommerce i LMS)
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    Select::make('metadata.active_accounting_driver')
                                        ->label('Aktywna platforma księgowości')
                                        ->options([
                                            'built_in' => 'Wbudowany system faktur',
                                            'fakturownia' => 'Fakturownia.pl',
                                            'ifirma' => 'iFirma.pl',
                                            'infakt' => 'inFakt.pl',
                                            'wfirma' => 'wFirma.pl',
                                        ])
                                        ->default('built_in')
                                        ->required()
                                        ->reactive()
                                        ->columnSpanFull(),

                                    // Fakturownia
                                    TextInput::make('metadata.fakturownia_api_token')
                                        ->label('Fakturownia API Token')
                                        ->visible(fn ($get) => $get('metadata.active_accounting_driver') === 'fakturownia')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('metadata.fakturownia_domain')
                                        ->label('Fakturownia Domain (np. twojafirma)')
                                        ->visible(fn ($get) => $get('metadata.active_accounting_driver') === 'fakturownia')
                                        ->required()
                                        ->maxLength(255),

                                    // iFirma
                                    TextInput::make('metadata.ifirma_api_key')
                                        ->label('iFirma API Key')
                                        ->visible(fn ($get) => $get('metadata.active_accounting_driver') === 'ifirma')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('metadata.ifirma_username')
                                        ->label('iFirma Email logowania')
                                        ->visible(fn ($get) => $get('metadata.active_accounting_driver') === 'ifirma')
                                        ->required()
                                        ->maxLength(255),

                                    // inFakt
                                    TextInput::make('metadata.infakt_api_key')
                                        ->label('inFakt API Key')
                                        ->visible(fn ($get) => $get('metadata.active_accounting_driver') === 'infakt')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    // wFirma
                                    TextInput::make('metadata.wfirma_api_key')
                                        ->label('wFirma API Key (Secret)')
                                        ->visible(fn ($get) => $get('metadata.active_accounting_driver') === 'wfirma')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('metadata.wfirma_access_key')
                                        ->label('wFirma Access Key (Klucz dostępu)')
                                        ->visible(fn ($get) => $get('metadata.active_accounting_driver') === 'wfirma')
                                        ->required()
                                        ->maxLength(255),

                                    // Walidacja VIES (VAT UE)
                                    Toggle::make('metadata.vies_enabled')
                                        ->label('Włącz walidację VIES (VAT UE)')
                                        ->default(true)
                                        ->columnSpanFull(),
                                    Toggle::make('metadata.vies_strict_mode')
                                        ->label('Tryb restrykcyjny VIES')
                                        ->default(false)
                                        ->helperText('Zablokuj zakupy B2B dla klientów z UE, jeśli serwery walidacji VIES są niedostępne.')
                                        ->columnSpanFull(),
                                 ]),
                        ]),

                    Tabs\Tab::make('Kursy i Zaawansowane')
                        ->visible($isLms || !$isEcommerce)
                        ->schema([
                            // 14. LMS / KURSY (Tylko LMS)
                            Group::make()
                                ->visible($isLms)
                                ->columns(2)
                                ->schema([
                                    Toggle::make('metadata.lms_lock_lesson_order')
                                        ->label('Wymuś kolejność przechodzenia lekcji (blokuj lekcje przed ukończeniem poprzednich)')
                                        ->default(false),
                                    Toggle::make('metadata.lms_generate_certificates')
                                        ->label('Generuj automatycznie certyfikaty PDF po ukończeniu kursu')
                                        ->default(false),
                                    TextInput::make('metadata.lms_passing_score_percentage')
                                        ->label('Próg punktowy zaliczenia quizów (%)')
                                        ->numeric()
                                        ->default(80)
                                        ->required(),
                                ]),

                            // 15. POZOSTAŁE METADANE
                            Group::make()
                                ->schema([
                                    KeyValue::make('metadata')
                                        ->label('Metadane zaawansowane')
                                        ->keyLabel('Klucz')
                                        ->valueLabel('Wartość')
                                        ->formatStateUsing(fn ($state) => is_array($state) ? Arr::dot($state) : $state)
                                        ->dehydrateStateUsing(fn ($state) => is_array($state) ? collect($state)->reduce(function ($carry, $value, $key) {
                                            Arr::set($carry, $key, $value);
                                            return $carry;
                                        }, []) : $state),
                                ]),
                        ]),

                    Tabs\Tab::make('Zgodność z AI Act')
                        ->schema([
                            Group::make()
                                ->columns(2)
                                ->schema([
                                    Toggle::make('metadata.ai_chatbot_enabled')
                                        ->label('Włącz chatbot AI na stronie')
                                        ->default(false)
                                        ->reactive(),
                                    Toggle::make('metadata.ai_automated_decisions_enabled')
                                        ->label('Zautomatyzowane podejmowanie decyzji')
                                        ->default(false)
                                        ->helperText('Oznacz, jeśli system podejmuje decyzje wywołujące skutki prawne dla klienta.'),
                                    Textarea::make('metadata.ai_chatbot_disclosure_text')
                                        ->label('Nota informacyjna chatbota (AI Disclosure)')
                                        ->placeholder('np. Rozmawiasz z asystentem sztucznej inteligencji. Twoje zapytania są przetwarzane automatycznie.')
                                        ->visible(fn ($get) => (bool) $get('metadata.ai_chatbot_enabled'))
                                        ->default('Rozmawiasz z asystentem AI. Wszystkie odpowiedzi generowane są automatycznie.')
                                        ->columnSpanFull()
                                        ->rows(2),
                                ]),
                        ]),
                ])
        ]);
    }
}
