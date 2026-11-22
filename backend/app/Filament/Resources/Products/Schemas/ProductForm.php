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
                                        \Filament\Forms\Components\Hidden::make('type')
                                            ->default(\App\Domain\Commerce\Enums\ProductType::Physical->value),
                                        TextInput::make('name')
                                            ->label('Tytuł pracy / nazwa produktu')
                                            ->placeholder('np. Obiekt 2')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (string $operation, Get $get, Set $set, ?string $old, ?string $state): void {
                                                if ($operation !== 'create') {
                                                    return;
                                                }
                                                $oldSlug = Str::slug((string) $old);
                                                $newSlug = Str::slug((string) $state);
                                                if (($get('slug') ?? '') === '' || ($get('slug') ?? '') === $oldSlug) {
                                                    $set('slug', $newSlug);
                                                }
                                                $oldSku = Str::upper($oldSlug);
                                                if (($get('sku') ?? '') === '' || ($get('sku') ?? '') === $oldSku) {
                                                    $set('sku', Str::upper($newSlug));
                                                }
                                            })
                                            ->columnSpanFull(),
                                        Select::make('categories')
                                            ->label('Kategoria (akwarela / rysunek)')
                                            ->relationship(titleAttribute: 'name')
                                            ->preload()
                                            ->native(false),
                                        TextInput::make('metadata.year')
                                            ->label('Rok powstania')
                                            ->placeholder('np. 2024')
                                            ->default((string) date('Y'))
                                            ->maxLength(16),
                                        TextInput::make('metadata.technique')
                                            ->label('Technika wykonania')
                                            ->placeholder('np. Akwarela na papierze bawełnianym')
                                            ->datalist([
                                                'Akwarela na papierze bawełnianym',
                                                'Rysunek ołówkiem i cienkopisem',
                                                'Ołówek na papierze',
                                                'Cienkopis na papierze',
                                                'Olej na płótnie',
                                                'Akryl na płótnie',
                                            ])
                                            ->maxLength(255),
                                        TextInput::make('metadata.format')
                                            ->label('Format / wymiary')
                                            ->placeholder('np. A4 (21 x 29,7 cm)')
                                            ->datalist([
                                                'A4 (21 x 29,7 cm)',
                                                'A3 (29,7 x 42 cm)',
                                                '30x40 cm',
                                                '40x50 cm',
                                                '50x70 cm',
                                            ])
                                            ->maxLength(100),
                                        TextInput::make('slug')
                                            ->label('Adres URL (slug)')
                                            ->required(fn (): bool => (bool) auth()->user()?->isAdmin())
                                            ->hidden(fn (): bool => ! auth()->user()?->isAdmin())
                                            ->dehydrated()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->withoutTrashed()),
                                        TextInput::make('sku')
                                            ->label('Kod bazowy SKU')
                                            ->placeholder('Generowany automatycznie z nazwy')
                                            ->hidden(fn (): bool => ! auth()->user()?->isAdmin())
                                            ->dehydrated()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->withoutTrashed()),
                                        Textarea::make('description')
                                            ->label('Opis pracy (wyświetlany na karcie produktu i w sklepie)')
                                            ->placeholder('np. Rogue planet')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                        Textarea::make('short_description')
                                            ->label('Krótki opis pomocniczy (opcjonalnie)')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Warianty i ceny w sklepie (wydruk oraz oryginał)')
                                    ->description('Każda dodana praca jest dostępna jako wydruk kolekcjonerski. Poniżej zdecyduj, czy fizyczny oryginał tej pracy jest również dostępny w sprzedaży, czy został już sprzedany.')
                                    ->columns(2)
                                    ->schema([
                                        Group::make()
                                            ->schema([
                                                Section::make('Wariant 1: wydruk kolekcjonerski (zawsze dostępny)')
                                                    ->schema([
                                                        TextInput::make('print_regular_price')
                                                            ->label('Cena regularna wydruku (PLN)')
                                                            ->placeholder('np. 30 (akwarela) lub 20 (rysunek)')
                                                            ->helperText('Domyślnie: 30 zł dla akwareli, 20 zł dla rysunku.')
                                                            ->numeric()
                                                            ->default(30)
                                                            ->required()
                                                            ->minValue(0)
                                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                                if ($record) {
                                                                    $printVariant = $record->variants()->where('sku', 'like', '%-PR')->first();
                                                                    $price = $printVariant ? $printVariant->regular_price_amount : $record->regular_price_amount;
                                                                    $component->state($price !== null ? $price / 100 : null);
                                                                }
                                                            }),
                                                        TextInput::make('print_sale_price')
                                                            ->label('Cena promocyjna wydruku (PLN)')
                                                            ->placeholder('Pozostaw puste, jeśli brak promocji')
                                                            ->helperText('Opcjonalnie. Cena niższa niż regularna włączy oznaczenie promocji i historię Omnibus.')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->lt('print_regular_price')
                                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                                if ($record) {
                                                                    $printVariant = $record->variants()->where('sku', 'like', '%-PR')->first();
                                                                    $salePrice = $printVariant ? $printVariant->sale_price_amount : $record->sale_price_amount;
                                                                    $component->state($salePrice !== null ? $salePrice / 100 : null);
                                                                }
                                                            }),
                                                        \Filament\Forms\Components\Hidden::make('print_stock_quantity')
                                                            ->default(999),
                                                    ]),
                                            ]),
                                        Group::make()
                                            ->schema([
                                                Section::make('Wariant 2: praca oryginalna')
                                                    ->schema([
                                                        Toggle::make('has_original')
                                                            ->label('Oryginał dostępny w sprzedaży')
                                                            ->helperText('Włącz, jeśli fizyczny oryginał jest dostępny do kupienia. Wyłącz, jeśli oryginał został już sprzedany (wtedy w sklepie widoczny jest status „Oryginał sprzedany” i można zamówić tylko wydruk).')
                                                            ->live()
                                                            ->default(true)
                                                            ->afterStateHydrated(function (Toggle $component, $record) {
                                                                if ($record) {
                                                                    $origVariant = $record->variants()->where('sku', 'like', '%-OR')->first();
                                                                    $component->state($origVariant ? ($origVariant->is_active && $origVariant->stock_quantity > 0) : false);
                                                                }
                                                            }),
                                                        TextInput::make('original_regular_price')
                                                            ->label('Cena regularna oryginału (PLN)')
                                                            ->placeholder('np. 300 (akwarela) lub 200 (rysunek)')
                                                            ->helperText('Domyślnie: 300 zł dla akwareli, 200 zł dla rysunku.')
                                                            ->numeric()
                                                            ->default(300)
                                                            ->minValue(0)
                                                            ->visible(fn (Get $get) => (bool) $get('has_original'))
                                                            ->required(fn (Get $get) => (bool) $get('has_original'))
                                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                                if ($record) {
                                                                    $origVariant = $record->variants()->where('sku', 'like', '%-OR')->first();
                                                                    $component->state($origVariant && $origVariant->regular_price_amount > 0 ? $origVariant->regular_price_amount / 100 : 300);
                                                                }
                                                            }),
                                                        TextInput::make('original_sale_price')
                                                            ->label('Cena promocyjna oryginału (PLN)')
                                                            ->placeholder('Pozostaw puste, jeśli brak promocji')
                                                            ->helperText('Opcjonalnie. Cena niższa niż regularna włączy promocję tylko dla oryginału oraz osobną historię Omnibus.')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->lt('original_regular_price')
                                                            ->visible(fn (Get $get) => (bool) $get('has_original'))
                                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                                if ($record) {
                                                                    $origVariant = $record->variants()->where('sku', 'like', '%-OR')->first();
                                                                    $component->state($origVariant && $origVariant->sale_price_amount !== null ? $origVariant->sale_price_amount / 100 : null);
                                                                }
                                                            }),
                                                    ]),
                                            ]),
                                    ]),
                                Group::make()
                                    ->columns(3)
                                    ->schema([
                                        \Filament\Forms\Components\Hidden::make('manages_stock')
                                            ->default(true),
                                        \Filament\Forms\Components\Hidden::make('stock_quantity')
                                            ->default(999),
                                        Toggle::make('is_active')
                                            ->label('Aktywny')
                                            ->default(true),
                                        Toggle::make('is_visible')
                                            ->label('Widoczny w sklepie')
                                            ->default(true),
                                        Toggle::make('is_purchasable')
                                            ->label('Możliwy do zakupu')
                                            ->default(true),
                                    ]),
                                Section::make('Zaawansowane ustawienia księgowe')
                                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                                    ->collapsible()
                                    ->collapsed()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('regular_price_amount')
                                            ->label('Cena bazowa (PLN)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null)
                                            ->hidden(),
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
                                            ->disabled()
                                            ->dehydrated()
                                            ->helperText('Produkty rozliczane są w PLN.')
                                            ->default(app(StoreSettings::class)->currency())
                                            ->minLength(3)
                                            ->maxLength(3),
                                        DateTimePicker::make('published_at')
                                            ->label('Publikacja od'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Zdjęcia produktu')
                            ->schema([
                                Group::make()
                                    ->columns(['default' => 1, 'lg' => 12])
                                    ->schema([
                                        Group::make()
                                            ->columnSpan(['default' => 1, 'lg' => 5])
                                            ->schema([
                                                FileUpload::make('featured_image_path')
                                                    ->label('Główny obraz pracy')
                                                    ->helperText('Główne zdjęcie widoczne na karcie pracy w sklepie.')
                                                    ->disk('public')
                                                    ->directory('products')
                                                    ->visibility('public')
                                                    ->image()
                                                    ->imageEditor()
                                                    ->imagePreviewHeight('320')
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('1920')
                                                    ->imageResizeTargetHeight('1920')
                                                    ->imageResizeUpscale(false)
                                                    ->maxSize(15360)
                                                    ->extraAttributes(['class' => 'fi-artwork-featured-upload']),
                                                TextInput::make('metadata.featured_image_alt')
                                                    ->label('Tekst alternatywny głównego obrazu (alt)')
                                                    ->placeholder('np. Obiekt 2 – oryginalna akwarela z serii Kosmos, Maciej Kosteczka')
                                                    ->maxLength(255),
                                            ]),
                                        Group::make()
                                            ->columnSpan(['default' => 1, 'lg' => 7])
                                            ->schema([
                                                FileUpload::make('gallery_image_paths')
                                                    ->label('Dodatkowe zdjęcia pracy (detale, oprawa)')
                                                    ->helperText('Kolejne zdjęcia układają się w siatkę miniatur. Przeciągnij kafelek, aby zmienić kolejność.')
                                                    ->disk('public')
                                                    ->directory('products/gallery')
                                                    ->visibility('public')
                                                    ->image()
                                                    ->appendFiles()
                                                    ->panelLayout('grid')
                                                    ->imagePreviewHeight('180')
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('1920')
                                                    ->imageResizeTargetHeight('1920')
                                                    ->imageResizeUpscale(false)
                                                    ->maxSize(15360)
                                                    ->multiple()
                                                    ->reorderable()
                                                    ->extraAttributes(['class' => 'fi-artwork-gallery-upload']),
                                            ]),
                                    ]),
                            ]),
                        Tabs\Tab::make('SEO')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('Tytuł SEO')
                                            ->placeholder('Pozostaw puste, aby użyć nazwy pracy')
                                            ->maxLength(255),
                                        Textarea::make('seo_description')
                                            ->label('Opis SEO')
                                            ->rows(3),
                                        Toggle::make('is_noindex')
                                            ->label('Zablokuj indeksowanie tego produktu (noindex)')
                                            ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                                            ->default(false),
                                    ]),
                            ]),
                    ])
            ]);
    }
}