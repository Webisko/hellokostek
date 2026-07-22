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
                    Tabs\Tab::make('Wygląd')
                        ->schema([
                            Group::make()
                                ->columns(['lg' => 2, 'default' => 1])
                                ->extraAttributes(['class' => 'items-stretch'])
                                ->schema([
                                    // Lewa kolumna: Nazwa panelu oraz Logo i Favicon obok siebie (1:1)
                                    Group::make()
                                        ->schema([
                                            TextInput::make('metadata.admin_brand_name')
                                                ->label('Nazwa w panelu administratora')
                                                ->placeholder('np. Panel CMS')
                                                ->maxLength(255),
                                            Group::make()
                                                ->columns(2)
                                                ->schema([
                                                    FileUpload::make('metadata.admin_logo_path')
                                                        ->label('Logo w panelu administratora')
                                                        ->image()
                                                        ->directory('branding')
                                                        ->visibility('public')
                                                        ->imageCropAspectRatio('1:1')
                                                        ->panelAspectRatio('1:1'),
                                                    FileUpload::make('metadata.admin_favicon_path')
                                                        ->label('Favicon panelu')
                                                        ->image()
                                                        ->directory('branding')
                                                        ->visibility('public')
                                                        ->imageCropAspectRatio('1:1')
                                                        ->panelAspectRatio('1:1'),
                                                ]),
                                        ]),

                                    // Prawa kolumna: Tło ekranu logowania (wysokość dopasowana do lewej kolumny)
                                    Group::make()
                                        ->extraAttributes(['class' => 'flex flex-col h-full'])
                                        ->schema([
                                            FileUpload::make('metadata.admin_login_background_path')
                                                ->label('Tło ekranu logowania')
                                                ->image()
                                                ->directory('branding')
                                                ->visibility('public')
                                                ->imageCropAspectRatio('16:9')
                                                ->extraAttributes(['class' => 'fi-login-bg-upload flex-1 flex flex-col h-full']),
                                        ]),
                                ]),
                        ]),

                    Tabs\Tab::make('System i poczta')
                        ->schema([
                            Section::make('Bezpieczeństwo i Dostęp')
                                ->description('Konfiguracja adresu URL panelu, nagłówków HTTP i indeksowania.')
                                ->columns(['lg' => 2, 'default' => 1])
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
                                    Toggle::make('global_noindex')
                                        ->label('Zablokuj indeksowanie całej strony (noindex)')
                                        ->default(false),
                                    Textarea::make('metadata.content_security_policy')
                                        ->label('Content Security Policy (CSP)')
                                        ->placeholder("np. default-src 'self'; script-src 'self' 'unsafe-inline';")
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    Textarea::make('metadata.robots_txt')
                                        ->label('Zawartość pliku robots.txt')
                                        ->placeholder("User-agent: *\nDisallow: /admin/\nSitemap: " . url('sitemap.xml'))
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Tryb Konserwacji i Integracje')
                                ->description('Zarządzanie niedostępnością serwisu i zewnętrznymi powiadomieniami.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('maintenance_mode_enabled')
                                        ->label('Włącz tryb konserwacji (strona w budowie)')
                                        ->default(false)
                                        ->reactive(),
                                    TextInput::make('maintenance_mode_allowed_ips')
                                        ->label('Dozwolone adresy IP (oddzielone przecinkami)')
                                        ->visible(fn ($get) => (bool) $get('maintenance_mode_enabled'))
                                        ->placeholder('np. 127.0.0.1, 192.168.1.100'),
                                    Textarea::make('maintenance_mode_message')
                                        ->label('Komunikat trybu konserwacji')
                                        ->visible(fn ($get) => (bool) $get('maintenance_mode_enabled'))
                                        ->rows(2)
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.newsletter_webhook_url')
                                        ->label('Webhook URL subskrypcji newslettera (np. Make, Zapier, Mailerlite)')
                                        ->url()
                                        ->placeholder('https://...')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Poczta E-mail i Serwer')
                                ->description('Dane nadawcy wiadomości wychodzących oraz klucze API poczty.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    TextInput::make('mail_from_name')
                                        ->label('Nazwa nadawcy e-mail')
                                        ->maxLength(255),
                                    TextInput::make('mail_from_address')
                                        ->label('Adres nadawcy e-mail')
                                        ->email()
                                        ->maxLength(255),
                                    TextInput::make('support_email')
                                        ->label('E-mail wsparcia')
                                        ->email()
                                        ->maxLength(255),
                                    TextInput::make('admin_notification_email')
                                        ->label('E-mail powiadomień admina')
                                        ->email()
                                        ->maxLength(255),
                                    TextInput::make('metadata.resend_key')
                                        ->label('Klucz API Resend')
                                        ->placeholder('re_...')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255),
                                    TextInput::make('metadata.mail_safety_redirect')
                                        ->label('Przekierowanie e-maili (Tryb testowy)')
                                        ->email()
                                        ->helperText('Jeśli podasz tutaj adres e-mail, wszystkie wiadomości wychodzące ze strony zostaną przekierowane na ten adres.')
                                        ->maxLength(255),
                                ]),
                        ]),

                    Tabs\Tab::make('SEO')
                        ->schema([
                            Section::make('Analityka i Śledzenie Ruchu')
                                ->description('Klucze integracyjne dla Google Tag Manager, Google Analytics oraz Meta Pixel.')
                                ->columns(['lg' => 3, 'default' => 1])
                                ->schema([
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
                                        ->maxLength(50),
                                ]),

                            Section::make('Zgoda na Cookies (RODO)')
                                ->description('Ustawienia wyświetlania i treści baneru informacyjnego o plikach cookie.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('cookie_banner_enabled')
                                        ->label('Włącz baner cookies na froncie')
                                        ->default(false)
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
                                ]),

                            Section::make('Domyślne Meta Tagi i Skrypty Nagłówka')
                                ->description('Dodatkowa konfiguracja SEO oraz osadzanie skryptów w sekcji <head>.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    KeyValue::make('seo')
                                        ->label('Domyślne tagi SEO (meta)')
                                        ->keyLabel('Tag (np. og:title)')
                                        ->valueLabel('Wartość'),
                                    Textarea::make('custom_head_scripts')
                                        ->label('Niestandardowe skrypty w sekcji <head>')
                                        ->placeholder("np. <script>...</script>")
                                        ->rows(5),
                                ]),
                        ]),

                    Tabs\Tab::make('E-commerce')
                        ->visible($isEcommerce)
                        ->schema([
                            Section::make('Podstawowe Ustawienia Sklepu')
                                ->description('Nazwa, waluta, zakupy bez rejestracji i przewalutowania.')
                                ->columns(['lg' => 3, 'default' => 1])
                                ->schema([
                                    TextInput::make('store_name')
                                        ->label('Nazwa sklepu')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('currency')
                                        ->label('Główna waluta')
                                        ->required()
                                        ->minLength(3)
                                        ->maxLength(3)
                                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? mb_strtoupper(trim($state)) : null),
                                    Toggle::make('allow_guest_checkout')
                                        ->label('Zezwalaj na zakupy jako gość')
                                        ->default(true),
                                    KeyValue::make('exchange_rates')
                                        ->label('Przeliczniki walut obcych (np. EUR -> 0.23)')
                                        ->keyLabel('Waluta (EUR, USD)')
                                        ->valueLabel('Mnożnik (1 PLN = X waluty)')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Odzyskiwanie Porzuconych Koszyków')
                                ->description('Automatyczne przypomnienia e-mail po porzuceniu procesu zakupowego.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.abandoned_cart_enabled')
                                        ->label('Włącz odzyskiwanie porzuconych koszyków')
                                        ->default(true),
                                    TextInput::make('metadata.abandoned_cart_hours_threshold')
                                        ->label('Czas wysyłki przypomnienia (w godzinach)')
                                        ->numeric()
                                        ->default(2)
                                        ->required(),
                                ]),

                            Section::make('Strefy i Metody Wysyłki')
                                ->description('Definicje krajów dostawy oraz opcji dostarczenia paczki.')
                                ->schema([
                                    Repeater::make('shipping_zones')
                                        ->label('Strefy wysyłki')
                                        ->helperText('Domyślna strefa wysyłki to Polska (PL).')
                                        ->columns(3)
                                        ->schema([
                                            TextInput::make('code')
                                                ->label('Kod strefy')
                                                ->placeholder('PL')
                                                ->required(),
                                            TextInput::make('name')
                                                ->label('Nazwa strefy')
                                                ->placeholder('Polska')
                                                ->required(),
                                            TextInput::make('countries')
                                                ->label('Kraje (kody ISO)')
                                                ->placeholder('PL')
                                                ->required(),
                                        ])
                                        ->columnSpanFull(),

                                    Repeater::make('shipping_methods')
                                        ->label('Metody wysyłki')
                                        ->helperText('Zarządzaj dostępnymi metodami wysyłki.')
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
                                        ])
                                        ->columns(5)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Płatności i dostawa')
                        ->visible($isEcommerce)
                        ->schema([
                            Section::make('Stripe')
                                ->description('Płatności kartą płatniczą i portfelami cyfrowymi (Apple Pay / Google Pay).')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.stripe_enabled')
                                        ->label('Stripe włączony')
                                        ->default(true)
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.stripe_key')
                                        ->label('Stripe Public Key (Publishable)')
                                        ->placeholder('pk_live_...')
                                        ->maxLength(255),
                                    TextInput::make('metadata.stripe_secret')
                                        ->label('Stripe Secret Key')
                                        ->placeholder('sk_live_...')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255),
                                    TextInput::make('metadata.stripe_webhook_secret')
                                        ->label('Stripe Webhook Secret')
                                        ->placeholder('whsec_...')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Przelewy24 (P24)')
                                ->description('Polskie szybkie przelewy i BLIK.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.przelewy24_enabled')
                                        ->label('Przelewy24 włączone')
                                        ->default(true)
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.przelewy24_merchant_id')
                                        ->label('Przelewy24 Merchant ID')
                                        ->maxLength(50),
                                    TextInput::make('metadata.przelewy24_pos_id')
                                        ->label('Przelewy24 POS ID')
                                        ->maxLength(50),
                                    TextInput::make('metadata.przelewy24_crc')
                                        ->label('Przelewy24 CRC Key')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255),
                                    TextInput::make('metadata.przelewy24_api_key')
                                        ->label('Przelewy24 API Key')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255),
                                    TextInput::make('metadata.przelewy24_api_base_url')
                                        ->label('Przelewy24 API Base URL')
                                        ->placeholder('https://secure.przelewy24.pl/api/v1')
                                        ->maxLength(255),
                                    TextInput::make('metadata.przelewy24_redirect_base_url')
                                        ->label('Przelewy24 Redirect Base URL')
                                        ->placeholder('https://secure.przelewy24.pl')
                                        ->maxLength(255),
                                    TextInput::make('metadata.przelewy24_callback_token')
                                        ->label('Przelewy24 Callback Token')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('InPost Paczkomaty i Kurier')
                                ->description('Klucze API i dane nadawcy dla przesyłek InPost.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.inpost_sandbox')
                                        ->label('Tryb testowy (Sandbox)')
                                        ->default(true)
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.inpost_organization_id')
                                        ->label('InPost Organization ID')
                                        ->maxLength(50),
                                    TextInput::make('metadata.inpost_token')
                                        ->label('InPost API Token')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255),
                                    Section::make('Dane nadawcy InPost')
                                        ->columns(['lg' => 2, 'default' => 1])
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('metadata.inpost_sender_name')->label('Imię i Nazwisko nadawcy')->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_company')->label('Nazwa firmy nadawcy')->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_email')->label('E-mail nadawcy')->email()->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_phone')->label('Telefon nadawcy')->maxLength(50),
                                            TextInput::make('metadata.inpost_sender_street')->label('Ulica nadawcy')->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_building')->label('Budynek nadawcy')->maxLength(20),
                                            TextInput::make('metadata.inpost_sender_city')->label('Miasto nadawcy')->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_postcode')->label('Kod pocztowy nadawcy')->maxLength(20),
                                        ]),
                                ]),

                            Section::make('Orlen Paczka')
                                ->description('Klucze API i dane nadawcy dla przesyłek Orlen Paczka.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.orlen_sandbox')
                                        ->label('Tryb testowy (Sandbox)')
                                        ->default(true)
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.orlen_partner_id')
                                        ->label('Orlen Paczka Partner ID')
                                        ->maxLength(50),
                                    TextInput::make('metadata.orlen_partner_key')
                                        ->label('Orlen Paczka Partner Key')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(255),
                                    Section::make('Dane nadawcy Orlen Paczka')
                                        ->columns(['lg' => 2, 'default' => 1])
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('metadata.orlen_sender_name')->label('Imię i Nazwisko nadawcy')->maxLength(255),
                                            TextInput::make('metadata.orlen_sender_company')->label('Nazwa firmy nadawcy')->maxLength(255),
                                            TextInput::make('metadata.orlen_sender_email')->label('E-mail nadawcy')->email()->maxLength(255),
                                            TextInput::make('metadata.orlen_sender_phone')->label('Telefon nadawcy')->maxLength(50),
                                            TextInput::make('metadata.orlen_sender_street')->label('Ulica nadawcy')->maxLength(255),
                                            TextInput::make('metadata.orlen_sender_building')->label('Budynek nadawcy')->maxLength(20),
                                            TextInput::make('metadata.orlen_sender_city')->label('Miasto nadawcy')->maxLength(255),
                                            TextInput::make('metadata.orlen_sender_postcode')->label('Kod pocztowy nadawcy')->maxLength(20),
                                        ]),
                                ]),
                        ]),
                ])
        ]);
    }
}
