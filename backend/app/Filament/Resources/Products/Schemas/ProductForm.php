<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\Products\ProductResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use App\Support\StoreSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Szczegóły produktu')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Podstawowe dane')
                            ->schema([
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('type')
                                            ->label('Typ produktu')
                                            ->options(ProductResource::typeOptions())
                                            ->required()
                                            ->native(false),
                                        TextInput::make('slug')
                                            ->label('Adres URL (slug)')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                        TextInput::make('sku')
                                            ->label('SKU')
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                        Select::make('categories')
                                            ->label('Kategorie')
                                            ->multiple()
                                            ->relationship(titleAttribute: 'name')
                                            ->preload()
                                            ->searchable()
                                            ->columnSpanFull(),
                                        TextInput::make('name')
                                            ->label('Nazwa')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                                                if (($get('slug') ?? '') !== Str::slug((string) $old)) {
                                                    return;
                                                }
                                                $set('slug', Str::slug((string) $state));
                                            })
                                            ->columnSpanFull(),
                                        RichEditor::make('short_description')
                                            ->label('Krótki opis')
                                            ->columnSpanFull(),
                                        RichEditor::make('description')
                                            ->label('Opis')
                                            ->columnSpanFull(),
                                    ]),
                                Group::make()
                                    ->visible(fn (Get $get): bool => $get('type') === \App\Domain\Commerce\Enums\ProductType::Bundle->value)
                                    ->schema([
                                        Repeater::make('bundleItems')
                                            ->relationship('bundleItems')
                                            ->label('Produkty wchodzące w skład zestawu')
                                            ->columns(2)
                                            ->schema([
                                                Select::make('product_id')
                                                    ->label('Produkt')
                                                    ->options(fn (Get $get) => \App\Models\Product::query()
                                                        ->where('type', '!=', \App\Domain\Commerce\Enums\ProductType::Bundle->value)
                                                        ->where('id', '!=', $get('../../id'))
                                                        ->pluck('name', 'id')
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->native(false),
                                                TextInput::make('quantity')
                                                    ->label('Ilość w zestawie')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->required(),
                                            ])
                                            ->defaultItems(1)
                                            ->columnSpanFull(),
                                    ]),
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('manages_stock')
                                            ->label('Zarzadzaj stanem magazynowym')
                                            ->live()
                                            ->visible(fn (Get $get): bool => $get('type') !== \App\Domain\Commerce\Enums\ProductType::Bundle->value),
                                        TextInput::make('stock_quantity')
                                            ->label('Stan magazynowy')
                                            ->numeric()
                                            ->visible(fn (Get $get): bool => (bool) $get('manages_stock') && $get('type') !== \App\Domain\Commerce\Enums\ProductType::Bundle->value),
                                        \Filament\Forms\Components\Placeholder::make('bundle_stock_info')
                                            ->label('Stan magazynowy zestawu')
                                            ->content('Stan magazynowy tego zestawu jest wyliczany dynamicznie na podstawie dostępności produktów składowych.')
                                            ->visible(fn (Get $get): bool => $get('type') === \App\Domain\Commerce\Enums\ProductType::Bundle->value)
                                            ->columnSpan(2),
                                        Toggle::make('is_active')
                                            ->label('Aktywny')
                                            ->default(true),
                                        Toggle::make('is_visible')
                                            ->label('Widoczny w katalogu')
                                            ->default(true),
                                        Toggle::make('is_purchasable')
                                            ->label('Mozliwy do zakupu')
                                            ->default(true),
                                    ]),
                            ]),
                        Tabs\Tab::make('Ceny i Warianty')
                            ->schema([
                                Section::make('Warianty Sklepu (Wydruk & Praca Oryginalna)')
                                    ->description('Wprowadź ceny i dostępność dla wydruku oraz oryginalnej pracy artystycznej.')
                                    ->columns(2)
                                    ->schema([
                                        Group::make()
                                            ->schema([
                                                Section::make('Wariant 1: Wydruk')
                                                    ->schema([
                                                        TextInput::make('print_regular_price')
                                                            ->label('Cena wydruku (PLN)')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0)
                                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                                if ($record) {
                                                                    $printVariant = $record->variants()->where('sku', 'like', '%-PR')->first();
                                                                    $price = $printVariant ? $printVariant->regular_price_amount : $record->regular_price_amount;
                                                                    $component->state($price ? $price / 100 : null);
                                                                }
                                                            }),
                                                        TextInput::make('print_stock_quantity')
                                                            ->label('Stan magazynowy wydruków')
                                                            ->numeric()
                                                            ->default(999)
                                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                                if ($record) {
                                                                    $printVariant = $record->variants()->where('sku', 'like', '%-PR')->first();
                                                                    $component->state($printVariant ? ($printVariant->stock_quantity ?? 999) : ($record->stock_quantity ?? 999));
                                                                }
                                                            }),
                                                    ]),
                                            ]),
                                        Group::make()
                                            ->schema([
                                                Section::make('Wariant 2: Praca Oryginalna')
                                                    ->schema([
                                                        Toggle::make('has_original')
                                                            ->label('Praca oryginalna dostępna w sprzedaży')
                                                            ->live()
                                                            ->default(false)
                                                            ->afterStateHydrated(function (Toggle $component, $record) {
                                                                if ($record) {
                                                                    $origVariant = $record->variants()->where('sku', 'like', '%-OR')->first();
                                                                    $component->state($origVariant ? ($origVariant->is_active && ($origVariant->stock_quantity > 0 || $origVariant->regular_price_amount > 0)) : false);
                                                                }
                                                            }),
                                                        TextInput::make('original_regular_price')
                                                            ->label('Cena pracy oryginalnej (PLN)')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->visible(fn (Get $get) => (bool) $get('has_original'))
                                                            ->required(fn (Get $get) => (bool) $get('has_original'))
                                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                                if ($record) {
                                                                    $origVariant = $record->variants()->where('sku', 'like', '%-OR')->first();
                                                                    $component->state($origVariant ? $origVariant->regular_price_amount / 100 : null);
                                                                }
                                                            }),
                                                        TextInput::make('original_stock_quantity')
                                                            ->label('Stan pracy oryginalnej (1 = dostępna, 0 = sprzedana)')
                                                            ->numeric()
                                                            ->default(1)
                                                            ->visible(fn (Get $get) => (bool) $get('has_original'))
                                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                                if ($record) {
                                                                    $origVariant = $record->variants()->where('sku', 'like', '%-OR')->first();
                                                                    $component->state($origVariant ? ($origVariant->stock_quantity ?? 1) : 1);
                                                                }
                                                            }),
                                                    ]),
                                            ]),
                                    ]),
                                Section::make('Ustawienia cenowe i logistyka')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('regular_price_amount')
                                            ->label('Cena bazowa (PLN)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null)
                                            ->hidden(),
                                        TextInput::make('sale_price_amount')
                                            ->label('Cena promocyjna wydruku (PLN)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                                        Select::make('vat_rate')
                                            ->label('Stawka VAT')
                                            ->options([
                                                23 => '23%',
                                                8 => '8%',
                                                5 => '5%',
                                                0 => '0%',
                                                99 => 'zw. (zwolniony)',
                                            ])
                                            ->required()
                                            ->default(23)
                                            ->native(false),
                                        TextInput::make('currency')
                                            ->label('Waluta')
                                            ->required()
                                            ->default(app(StoreSettings::class)->currency())
                                            ->minLength(3)
                                            ->maxLength(3),
                                        TextInput::make('weight')
                                            ->label('Waga (kg)')
                                            ->numeric()
                                            ->step(0.001)
                                            ->minValue(0),
                                        DateTimePicker::make('published_at')
                                            ->label('Publikacja od'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Media i Promocja')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        FileUpload::make('featured_image_path')
                                            ->label('Glowny obraz produktu')
                                            ->disk('public')
                                            ->directory('products')
                                            ->image()
                                            ->imageEditor()
                                            ->columnSpanFull(),
                                        TextInput::make('metadata.featured_image_alt')
                                            ->label('Tekst alternatywny głównego obrazu (alt)')
                                            ->placeholder('np. Buty sportowe Nike Air Max w kolorze czarnym')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        FileUpload::make('hover_image_path')
                                            ->label('Drugi obraz produktu (na hover)')
                                            ->disk('public')
                                            ->directory('products')
                                            ->image()
                                            ->imageEditor()
                                            ->columnSpanFull(),
                                        FileUpload::make('gallery_image_paths')
                                            ->label('Galeria zdjęć produktu')
                                            ->disk('public')
                                            ->directory('products/gallery')
                                            ->image()
                                            ->multiple()
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('is_new')
                                            ->label('Nowosc'),
                                        Toggle::make('is_bestseller')
                                            ->label('Bestseller'),
                                        Toggle::make('is_recommended')
                                            ->label('Polecany'),
                                        Toggle::make('is_promoted')
                                            ->label('Promocja'),
                                        Toggle::make('is_seasonal')
                                            ->label('Sezonowy'),
                                        Toggle::make('is_clearance')
                                            ->label('Wyprzedaz'),
                                        Toggle::make('show_on_homepage')
                                            ->label('Pokaz na stronie glownej'),
                                        Toggle::make('show_in_bestsellers')
                                            ->label('Pokaz w sekcji bestsellerow'),
                                        Toggle::make('show_in_new_arrivals')
                                            ->label('Pokaz w sekcji nowosci'),
                                        Toggle::make('show_in_recommended')
                                            ->label('Pokaz w sekcji polecanych'),
                                        TagsInput::make('manual_tags')
                                            ->label('Reczne tagi')
                                            ->placeholder('Dodaj tag')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Powiązania')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        Repeater::make('productRelations')
                                            ->relationship()
                                            ->label('Powiązane produkty')
                                            ->columns(3)
                                            ->schema([
                                                Select::make('related_product_id')
                                                    ->label('Produkt powiązany')
                                                    ->options(fn () => \App\Models\Product::query()->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->native(false),
                                                Select::make('relation_type')
                                                    ->label('Typ relacji')
                                                    ->options([
                                                        'similar' => 'Podobny',
                                                        'upsell' => 'Up-sell',
                                                        'cross_sell' => 'Cross-sell',
                                                    ])
                                                    ->required()
                                                    ->native(false),
                                                TextInput::make('sort_order')
                                                    ->label('Kolejność')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required(),
                                            ])
                                            ->defaultItems(0)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Zgodność i SEO')
                            ->schema([
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('gpsr_manufacturer_name')
                                            ->label('Nazwa producenta')
                                            ->maxLength(255),
                                        TextInput::make('gpsr_manufacturer_email')
                                            ->label('E-mail/WWW producenta')
                                            ->maxLength(255),
                                        Textarea::make('gpsr_manufacturer_address')
                                            ->label('Adres pocztowy producenta')
                                            ->columnSpanFull()
                                            ->rows(2),
                                        TextInput::make('gpsr_responsible_name')
                                            ->label('Osoba odpowiedzialna w UE (Nazwa)')
                                            ->placeholder('Wymagane dla producentów spoza UE')
                                            ->maxLength(255),
                                        TextInput::make('gpsr_responsible_email')
                                            ->label('E-mail/WWW osoby odpowiedzialnej')
                                            ->maxLength(255),
                                        Textarea::make('gpsr_responsible_address')
                                            ->label('Adres pocztowy osoby odpowiedzialnej')
                                            ->columnSpanFull()
                                            ->rows(2),
                                        Textarea::make('gpsr_safety_warnings')
                                            ->label('Ostrzeżenia i informacje o ryzyku')
                                            ->placeholder('Wielojęzyczne teksty ostrzegawcze')
                                            ->columnSpanFull()
                                            ->rows(4),
                                        FileUpload::make('gpsr_document_path')
                                            ->label('Dokumenty towarzyszące (instrukcje obsługi, deklaracje CE) - PDF')
                                            ->disk('public')
                                            ->directory('products/documents')
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->columnSpanFull(),
                                    ]),
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('digital_compatibility')
                                            ->label('Kompatybilność systemowa')
                                            ->placeholder('np. Windows 11, macOS Big Sur, iOS 16+')
                                            ->maxLength(255),
                                        TextInput::make('digital_interoperability')
                                            ->label('Interoperacyjność')
                                            ->placeholder('np. Działa z czytnikami Kindle, Adobe Digital Editions')
                                            ->maxLength(255),
                                        TextInput::make('digital_drm')
                                            ->label('System zabezpieczeń (DRM)')
                                            ->placeholder('np. Brak DRM, Adobe DRM')
                                            ->maxLength(255),
                                        TextInput::make('digital_updates_info')
                                            ->label('Informacje o aktualizacjach')
                                            ->placeholder('np. Bezpłatne aktualizacje przez 12 miesięcy')
                                            ->maxLength(255),
                                    ]),
                                Group::make()
                                    ->schema([
                                        Toggle::make('is_ai_generated')
                                            ->label('Produkt/Media wygenerowane przez AI')
                                            ->reactive()
                                            ->default(false),
                                        Textarea::make('ai_disclosure_text')
                                            ->label('Nota o udostępnieniu AI')
                                            ->placeholder('np. Zdjęcia modeli prezentujących produkt lub opis zostały wygenerowane przy pomocy sztucznej inteligencji.')
                                            ->visible(fn (Get $get) => (bool) $get('is_ai_generated'))
                                            ->columnSpanFull(),
                                    ]),
                                Group::make()
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('Tytul SEO')
                                            ->maxLength(255),
                                        Textarea::make('seo_description')
                                            ->label('Opis SEO')
                                            ->rows(3),
                                        Toggle::make('is_noindex')
                                            ->label('Zablokuj indeksowanie tego produktu (noindex)')
                                            ->default(false),
                                        TextInput::make('metadata.og_title')
                                            ->label('Tytuł Open Graph (og:title)')
                                            ->placeholder('Pozostaw puste, aby użyć Tytułu SEO')
                                            ->maxLength(255),
                                        Textarea::make('metadata.og_description')
                                            ->label('Opis Open Graph (og:description)')
                                            ->placeholder('Pozostaw puste, aby użyć Opisu SEO')
                                            ->rows(3),
                                        FileUpload::make('metadata.og_image_path')
                                            ->label('Obraz Open Graph (og:image)')
                                            ->disk('public')
                                            ->directory('seo/og')
                                            ->image()
                                            ->imageEditor(),
                                        KeyValue::make('metadata')
                                            ->label('Metadane')
                                            ->keyLabel('Klucz')
                                            ->valueLabel('Wartosc')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
            ]);
    }
}