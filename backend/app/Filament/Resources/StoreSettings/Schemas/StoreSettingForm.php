<?php

namespace App\Filament\Resources\StoreSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StoreSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        $isEcommerce = app(\App\Support\StoreSettings::class)->isEcommerce();

        return $schema->components([
            Tabs::make('Ustawienia')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Wygląd')
                        ->schema([
                            Section::make('Identyfikacja panelu administratora')
                                ->description('Nazwa, logotyp i oprawa graficzna ekranu logowania CMS.')
                                ->visible(fn (): bool => self::isAdmin())
                                ->schema([
                                    TextInput::make('metadata.admin_brand_name')
                                        ->label('Nazwa w panelu administratora')
                                        ->placeholder('np. hellokostek CMS')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Group::make()
                                        ->columns(['lg' => 3, 'default' => 1])
                                        ->schema([
                                            FileUpload::make('metadata.admin_logo_path')
                                                ->label('Logo w panelu')
                                                ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Opcjonalnie. Gdy puste, w lewym górnym rogu wyświetla się nazwa tekstowa.')
                                                ->disk('public')
                                                ->image()
                                                ->directory('branding')
                                                ->visibility('public')
                                                ->imagePreviewHeight('148')
                                                ->extraAttributes(['class' => 'fi-settings-upload']),
                                            FileUpload::make('metadata.admin_favicon_path')
                                                ->label('Favicon panelu')
                                                ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Opcjonalnie. Gdy puste, używana jest domyślna ikona serwera (favicon.ico).')
                                                ->disk('public')
                                                ->image()
                                                ->directory('branding')
                                                ->visibility('public')
                                                ->imagePreviewHeight('148')
                                                ->extraAttributes(['class' => 'fi-settings-upload']),
                                            FileUpload::make('metadata.admin_login_background_path')
                                                ->label('Tło ekranu logowania')
                                                ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Opcjonalnie. Tło graficzne wyświetlane na ekranie logowania.')
                                                ->disk('public')
                                                ->image()
                                                ->directory('branding')
                                                ->visibility('public')
                                                ->imagePreviewHeight('148')
                                                ->extraAttributes(['class' => 'fi-settings-upload']),
                                        ]),
                                ]),
                            Section::make('Pasek ogłoszeń sklepu')
                                ->description('Pasek informacyjny na samej górze sklepu (np. darmowa dostawa lub informacje o promocjach).')
                                ->schema([
                                    Toggle::make('announcement_enabled')
                                        ->label('Włącz pasek ogłoszeń na górze strony')
                                        ->default(false)
                                        ->live(),
                                    TextInput::make('announcement_text')
                                        ->label('Treść paska ogłoszeń')
                                        ->placeholder('np. Darmowa dostawa dla zamówień od 200 zł | Wysyłka w 24–48h')
                                        ->maxLength(255),
                                ]),
                        ]),

                    Tabs\Tab::make('E-commerce')
                        ->visible($isEcommerce)
                        ->schema([
                            Section::make('Podstawowe ustawienia sklepu')
                                ->description('Nazwa sklepu wyświetlana klientom oraz parametry techniczne koszyka.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    TextInput::make('store_name')
                                        ->label('Nazwa sklepu')
                                        ->required()
                                        ->columnSpan(fn (): int|string => self::isAdmin() ? 1 : 'full')
                                        ->maxLength(255),
                                    TextInput::make('currency')
                                        ->label('Główna waluta')
                                        ->required()
                                        ->disabled()
                                        ->dehydrated()
                                        ->visible(fn (): bool => self::isAdmin())
                                        ->minLength(3)
                                        ->maxLength(3)
                                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? mb_strtoupper(trim($state)) : null),
                                    Toggle::make('allow_guest_checkout')
                                        ->label('Zezwalaj na zakupy jako gość')
                                        ->default(true)
                                        ->visible(fn (): bool => self::isAdmin())
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Dane sprzedawcy, przelewy i faktury VAT')
                                ->description('Konfiguracja danych firmy do przelewów tradycyjnych i automatycznie generowanych faktur VAT PDF.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.invoicing_enabled')
                                        ->label('Włącz automatyczne generowanie faktur PDF')
                                        ->default(false)
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.invoice_seller_name')
                                        ->label('Pełna nazwa firmy sprzedawcy')
                                        ->placeholder('np. hellokostek Maciej Kosteczka')
                                        ->default('hellokostek Maciej Kosteczka')
                                        ->maxLength(255),
                                    TextInput::make('metadata.invoice_seller_nip')
                                        ->label('NIP sprzedawcy')
                                        ->placeholder('np. 6252363656')
                                        ->default('6252363656')
                                        ->maxLength(32),
                                    TextInput::make('metadata.invoice_seller_address')
                                        ->label('Adres siedziby sprzedawcy')
                                        ->placeholder('np. Rynek 33, 42-470 Siewierz')
                                        ->default('Rynek 33, 42-470 Siewierz')
                                        ->columnSpanFull()
                                        ->maxLength(255),
                                    TextInput::make('metadata.invoice_seller_bank_account')
                                        ->label('Numer rachunku bankowego (IBAN)')
                                        ->placeholder('np. PL 74 1050 1227 1000 0090 8312 8604')
                                        ->default('PL 74 1050 1227 1000 0090 8312 8604')
                                        ->maxLength(64),
                                    TextInput::make('metadata.bank_transfer_reservation_days')
                                        ->label('Czas rezerwacji pracy przy przelewie tradycyjnym (dni robocze)')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Przez ile dni roboczych od złożenia zamówienia oryginał pracy jest zarezerwowany w oczekiwaniu na przelew tradycyjny (zalecane: 3 dni robocze).')
                                        ->numeric()
                                        ->default(3)
                                        ->minValue(1)
                                        ->maxValue(14)
                                        ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) $state : 3),
                                    TextInput::make('metadata.invoice_number_prefix')
                                        ->label('Prefiks numeracji faktur')
                                        ->placeholder('FV/')
                                        ->default('FV/')
                                        ->maxLength(32),
                                    TextInput::make('metadata.invoice_payment_days')
                                        ->label('Domyślny termin płatności na fakturze (dni)')
                                        ->numeric()
                                        ->default(14)
                                        ->minValue(1),
                                ]),

                            Section::make('Odzyskiwanie porzuconych koszyków')
                                ->description('Automatyczne przypomnienia e-mail po porzuceniu procesu zakupowego.')
                                ->collapsible()
                                ->collapsed()
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.abandoned_cart_enabled')
                                        ->label('Włącz odzyskiwanie porzuconych koszyków')
                                        ->default(false),
                                    TextInput::make('metadata.abandoned_cart_hours_threshold')
                                        ->label('Czas wysyłki przypomnienia (w godzinach)')
                                        ->numeric()
                                        ->default(2)
                                        ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) $state : 2),
                                ]),
                        ]),

                    Tabs\Tab::make('Płatności i dostawa')
                        ->visible($isEcommerce)
                        ->schema([
                            Section::make('Próg darmowej dostawy i metody wysyłki')
                                ->description('Kwota darmowej wysyłki dla wydruków (oryginały zawsze mają bezpłatną dostawę) oraz cennik metod dostawy.')
                                ->schema([
                                    TextInput::make('free_shipping_threshold')
                                        ->label('Próg darmowej dostawy dla wydruków (PLN)')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Minimalna wartość wydruków w koszyku (po rabatach), od której wysyłka jest bezpłatna. Wpisz 0, aby wyłączyć.')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(1)
                                        ->suffix('zł')
                                        ->placeholder('np. 200')
                                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                        ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) round((float) $state * 100) : 0),
                                    Repeater::make('shipping_methods')
                                        ->label('Metody wysyłki')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Zarządzaj dostępnymi metodami wysyłki oraz ich cenami bazowymi.')
                                        ->columns(fn (): array => self::isAdmin() ? ['md' => 3, 'default' => 1] : ['md' => 2, 'default' => 1])
                                        ->collapsible()
                                        ->addable(fn (): bool => self::isAdmin())
                                        ->deletable(fn (): bool => self::isAdmin())
                                        ->reorderable(fn (): bool => self::isAdmin())
                                        ->itemLabel(fn (array $state): ?string => ($state['name'] ?? null) ?: ($state['code'] ?? 'Metoda wysyłki'))
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Nazwa wyświetlana')
                                                ->placeholder('np. Kurier DPD')
                                                ->required(),
                                            TextInput::make('code')
                                                ->label('Kod techniczny metody')
                                                ->placeholder('np. flat_rate:courier')
                                                ->visible(fn (): bool => self::isAdmin())
                                                ->required(),
                                            TextInput::make('amount')
                                                ->label('Cena bazowa (PLN)')
                                                ->numeric()
                                                ->default(0)
                                                ->suffix('zł')
                                                ->required()
                                                ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                                ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                                            Toggle::make('supports_cod')
                                                ->label('Płatność za pobraniem (COD)')
                                                ->visible(fn (): bool => self::isAdmin())
                                                ->default(false),
                                            Toggle::make('requires_delivery_point')
                                                ->label('Wymaga punktu odbioru')
                                                ->visible(fn (): bool => self::isAdmin())
                                                ->default(false),
                                        ])
                                        ->columnSpanFull(),
                                    Section::make('Strefy wysyłki')
                                        ->description('Domyślna strefa wysyłki to Polska (PL). Rozwiń tylko przy obsłudze wysyłek zagranicznych.')
                                        ->visible(fn (): bool => self::isAdmin())
                                        ->collapsible()
                                        ->collapsed()
                                        ->schema([
                                            Repeater::make('shipping_zones')
                                                ->label('Lista stref')
                                                ->columns(['md' => 3, 'default' => 1])
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
                                                        ->formatStateUsing(fn ($state): ?string => is_array($state) ? implode(', ', $state) : $state)
                                                        ->required(),
                                                ])
                                                ->columnSpanFull(),
                                        ]),
                                ]),

                            Section::make('Stripe')
                                ->visible(fn (): bool => self::isAdmin())
                                ->description(fn (Get $get): string => self::integrationSummary(
                                    (bool) $get('metadata.stripe_enabled'),
                                    filled($get('metadata.stripe_key')) && filled($get('metadata.stripe_secret')),
                                    'Płatności kartą, BLIK, Apple Pay / Google Pay'
                                ))
                                ->collapsible()
                                ->collapsed()
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.stripe_enabled')
                                        ->label('Stripe włączony')
                                        ->default(true)
                                        ->live()
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.stripe_key')
                                        ->label('Klucz publiczny Stripe (publishable key)')
                                        ->placeholder('pk_live_...')
                                        ->maxLength(255),
                                    self::secretInput('metadata.stripe_secret', 'Klucz prywatny Stripe (secret key)', 'sk_live_...'),
                                    self::secretInput('metadata.stripe_webhook_secret', 'Sekret webhooka Stripe (webhook secret)', 'whsec_...')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Przelewy24 (P24)')
                                ->visible(fn (): bool => self::isAdmin())
                                ->description(fn (Get $get): string => self::integrationSummary(
                                    (bool) $get('metadata.przelewy24_enabled'),
                                    filled($get('metadata.przelewy24_merchant_id')) && filled($get('metadata.przelewy24_crc')),
                                    'Polskie szybkie przelewy i BLIK'
                                ))
                                ->collapsible()
                                ->collapsed()
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.przelewy24_enabled')
                                        ->label('Przelewy24 włączone')
                                        ->default(true)
                                        ->live()
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.przelewy24_merchant_id')
                                        ->label('ID sprzedawcy Przelewy24 (Merchant ID)')
                                        ->maxLength(50),
                                    TextInput::make('metadata.przelewy24_pos_id')
                                        ->label('ID punktu Przelewy24 (POS ID)')
                                        ->maxLength(50),
                                    self::secretInput('metadata.przelewy24_crc', 'Klucz CRC Przelewy24'),
                                    self::secretInput('metadata.przelewy24_api_key', 'Klucz API Przelewy24'),
                                    TextInput::make('metadata.przelewy24_api_base_url')
                                        ->label('Bazowy adres URL API Przelewy24')
                                        ->placeholder('https://secure.przelewy24.pl/api/v1')
                                        ->maxLength(255),
                                    TextInput::make('metadata.przelewy24_redirect_base_url')
                                        ->label('Bazowy adres URL przekierowania Przelewy24')
                                        ->placeholder('https://secure.przelewy24.pl')
                                        ->maxLength(255),
                                    self::secretInput('metadata.przelewy24_callback_token', 'Token wywołania zwrotnego Przelewy24')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('InPost Paczkomaty i kurier')
                                ->visible(fn (): bool => self::isAdmin())
                                ->description(fn (Get $get): string => self::credentialsSummary(
                                    filled($get('metadata.inpost_organization_id')) && filled($get('metadata.inpost_token')),
                                    (bool) $get('metadata.inpost_sandbox'),
                                    'Generowanie etykiet InPost z poziomu zamówień'
                                ))
                                ->collapsible()
                                ->collapsed()
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.inpost_sandbox')
                                        ->label('Tryb testowy (sandbox)')
                                        ->default(true)
                                        ->live()
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.inpost_organization_id')
                                        ->label('ID organizacji InPost')
                                        ->maxLength(50),
                                    self::secretInput('metadata.inpost_token', 'Token API InPost'),
                                    Section::make('Dane nadawcy InPost')
                                        ->columns(['lg' => 2, 'default' => 1])
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('metadata.inpost_sender_name')->label('Imię i nazwisko nadawcy')->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_company')->label('Nazwa firmy nadawcy')->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_email')->label('E-mail nadawcy')->email()->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_phone')->label('Telefon nadawcy')->maxLength(50),
                                            TextInput::make('metadata.inpost_sender_street')->label('Ulica nadawcy')->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_building')->label('Budynek nadawcy')->maxLength(20),
                                            TextInput::make('metadata.inpost_sender_city')->label('Miasto nadawcy')->maxLength(255),
                                            TextInput::make('metadata.inpost_sender_postcode')->label('Kod pocztowy nadawcy')->maxLength(20),
                                        ]),
                                ]),

                            Section::make('ORLEN Paczka')
                                ->visible(fn (): bool => self::isAdmin())
                                ->description(fn (Get $get): string => self::credentialsSummary(
                                    filled($get('metadata.orlen_partner_id')) && filled($get('metadata.orlen_partner_key')),
                                    (bool) $get('metadata.orlen_sandbox'),
                                    'Generowanie etykiet ORLEN Paczka z poziomu zamówień'
                                ))
                                ->collapsible()
                                ->collapsed()
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('metadata.orlen_sandbox')
                                        ->label('Tryb testowy (sandbox)')
                                        ->default(true)
                                        ->live()
                                        ->columnSpanFull(),
                                    TextInput::make('metadata.orlen_partner_id')
                                        ->label('ID partnera ORLEN Paczka')
                                        ->maxLength(50),
                                    self::secretInput('metadata.orlen_partner_key', 'Klucz partnera ORLEN Paczka'),
                                    Section::make('Dane nadawcy ORLEN Paczka')
                                        ->columns(['lg' => 2, 'default' => 1])
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('metadata.orlen_sender_name')->label('Imię i nazwisko nadawcy')->maxLength(255),
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

                    Tabs\Tab::make('Słowniki prac')
                        ->schema([
                            Section::make('Techniki wykonania i formaty prac')
                                ->description('Zarządzaj listą technik i wymiarów wyświetlanych do wyboru przy dodawaniu i edycji produktów oraz prac w galerii.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Repeater::make('metadata.artwork_techniques')
                                        ->label('Techniki wykonania')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Lista dostępna w formularzach Produktów i Galerii. Możesz dodawać, edytować, usuwać oraz zmieniać kolejność pozycji.')
                                        ->simple(
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('np. Olej na płótnie')
                                        )
                                        ->addActionLabel('Dodaj technikę')
                                        ->reorderable()
                                        ->defaultItems(0),
                                    Repeater::make('metadata.artwork_formats')
                                        ->label('Formaty / wymiary')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Lista formatów dostępna w Produktach i Galerii.')
                                        ->simple(
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(100)
                                                ->placeholder('np. 30x40 cm')
                                        )
                                        ->addActionLabel('Dodaj format')
                                        ->reorderable()
                                        ->defaultItems(0),
                                ]),
                        ]),

                    Tabs\Tab::make('SEO')
                        ->visible(fn (): bool => self::isAdmin())
                        ->schema([
                            Section::make('Analityka i śledzenie ruchu')
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

                            Section::make('Zgoda na pliki cookie (RODO)')
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

                            Section::make('Domyślne tagi meta i skrypty nagłówka (zaawansowane)')
                                ->description('Dodatkowa konfiguracja SEO oraz osadzanie skryptów w sekcji <head>.')
                                ->collapsible()
                                ->collapsed()
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    KeyValue::make('seo')
                                        ->label('Domyślne tagi SEO (meta)')
                                        ->keyLabel('Tag (np. og:title)')
                                        ->valueLabel('Wartość'),
                                    Textarea::make('custom_head_scripts')
                                        ->label('Niestandardowe skrypty w sekcji <head>')
                                        ->placeholder('np. <script>...</script>')
                                        ->rows(5),
                                ]),
                        ]),

                    Tabs\Tab::make('System i poczta')
                        ->schema([
                            Section::make('Poczta e-mail i powiadomienia')
                                ->description('Adresy e-mail do obsługi powiadomień o zamówieniach i wiadomości od klientów.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    TextInput::make('mail_from_name')
                                        ->label('Nazwa nadawcy e-mail')
                                        ->visible(fn (): bool => self::isAdmin())
                                        ->maxLength(255),
                                    TextInput::make('mail_from_address')
                                        ->label('Adres nadawcy e-mail')
                                        ->email()
                                        ->visible(fn (): bool => self::isAdmin())
                                        ->maxLength(255),
                                    TextInput::make('support_email')
                                        ->label('E-mail wsparcia')
                                        ->email()
                                        ->maxLength(255),
                                    TextInput::make('admin_notification_email')
                                        ->label('E-mail powiadomień admina')
                                        ->email()
                                        ->maxLength(255),
                                    TextInput::make('metadata.mail_safety_redirect')
                                        ->label('Przekierowanie e-maili (tryb testowy)')
                                        ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Jeśli podasz tutaj adres e-mail, wszystkie wiadomości wychodzące ze strony zostaną przekierowane na ten adres.')
                                        ->email()
                                        ->visible(fn (): bool => self::isAdmin())
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Tryb konserwacji')
                                ->description('Tymczasowa blokada frontu sklepu na czas prac serwisowych.')
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    Toggle::make('maintenance_mode_enabled')
                                        ->label('Włącz tryb konserwacji (strona w budowie)')
                                        ->default(false)
                                        ->live()
                                        ->columnSpanFull(),
                                    TextInput::make('maintenance_mode_allowed_ips')
                                        ->label('Dozwolone adresy IP (oddzielone przecinkami)')
                                        ->visible(fn (Get $get) => (bool) $get('maintenance_mode_enabled'))
                                        ->placeholder('np. 127.0.0.1, 192.168.1.100')
                                        ->columnSpanFull(),
                                    Textarea::make('maintenance_mode_message')
                                        ->label('Komunikat trybu konserwacji')
                                        ->visible(fn (Get $get) => (bool) $get('maintenance_mode_enabled'))
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Zaawansowane ustawienia techniczne')
                                ->description('Uwaga: zmiana ścieżki panelu, nagłówków bezpieczeństwa (CSP) lub robots.txt może zablokować dostęp do CMS lub działanie strony.')
                                ->visible(fn (): bool => self::isAdmin())
                                ->collapsible()
                                ->collapsed()
                                ->columns(['lg' => 2, 'default' => 1])
                                ->schema([
                                    TextInput::make('metadata.admin_path')
                                        ->label('Ścieżka panelu administratora (URL)')
                                        ->placeholder('admin')
                                        ->helperText('Ostrzeżenie: zmiana tego pola natychmiast zmienia adres logowania do panelu (/admin). Pozostaw bez zmian, jeśli nie przenosisz panelu.')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Toggle::make('metadata.security_headers_enabled')
                                        ->label('Włącz nagłówki bezpieczeństwa HTTP')
                                        ->default(true),
                                    Toggle::make('global_noindex')
                                        ->label('Zablokuj indeksowanie całej strony (noindex)')
                                        ->default(false),
                                    Textarea::make('metadata.content_security_policy')
                                        ->label('Polityka bezpieczeństwa treści (CSP)')
                                        ->placeholder("np. default-src 'self'; script-src 'self' 'unsafe-inline';")
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    Textarea::make('metadata.robots_txt')
                                        ->label('Zawartość pliku robots.txt')
                                        ->placeholder("User-agent: *\nDisallow: /admin/\nSitemap: " . url('sitemap.xml'))
                                        ->rows(4)
                                        ->columnSpanFull(),
                                    self::secretInput('metadata.resend_key', 'Klucz API Resend (opcjonalny)', 're_...'),
                                    TextInput::make('metadata.newsletter_webhook_url')
                                        ->label('Adres URL webhooka newslettera')
                                        ->url()
                                        ->placeholder('https://...')
                                        ->maxLength(255),
                                ]),
                        ]),
                ]),
        ]);
    }

    private static function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    private static function secretInput(string $name, string $label, ?string $placeholder = null): TextInput
    {
        $field = TextInput::make($name)
            ->label($label)
            ->password()
            ->revealable()
            ->autocomplete('new-password')
            ->extraInputAttributes([
                'data-1p-ignore' => 'true',
                'data-lpignore' => 'true',
                'data-bwignore' => 'true',
            ])
            ->maxLength(255);

        if ($placeholder !== null) {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    private static function integrationSummary(bool $enabled, bool $configured, string $label): string
    {
        $status = $enabled
            ? ($configured ? 'Włączona • skonfigurowana' : 'Włączona • brak kluczy API')
            : 'Wyłączona';

        return "{$status} — {$label}";
    }

    private static function credentialsSummary(bool $configured, bool $sandbox, string $label): string
    {
        if (! $configured) {
            return "Nie skonfigurowano — {$label}";
        }

        $mode = $sandbox ? 'Tryb testowy (sandbox)' : 'Tryb produkcyjny';

        return "{$mode} • klucze uzupełnione — {$label}";
    }
}
