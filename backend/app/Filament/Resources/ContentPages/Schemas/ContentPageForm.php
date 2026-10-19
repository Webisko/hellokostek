<?php

namespace App\Filament\Resources\ContentPages\Schemas;

use App\Models\ContentPage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ContentPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Podstawowe informacje o stronie')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Tytuł strony')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, Get $get, Set $set, ?string $old, ?string $state): void {
                            if ($operation !== 'create') {
                                return;
                            }
                            if (($get('slug') ?? '') !== Str::slug((string) $old)) {
                                return;
                            }
                            $set('slug', Str::slug((string) $state));
                        }),
                    TextInput::make('slug')
                        ->label('Adres URL (slug)')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('template')
                        ->label('Szablon strony')
                        ->options(ContentPage::templateOptions())
                        ->default('default')
                        ->required()
                        ->live()
                        ->native(false),
                    Toggle::make('is_active')
                        ->label('Strona aktywna')
                        ->default(true),
                    \Filament\Forms\Components\Hidden::make('sort_order')
                        ->default(fn () => (\App\Models\ContentPage::max('sort_order') ?? 0) + 10),
                    DateTimePicker::make('published_at')
                        ->label('Data publikacji'),
                    TextInput::make('metadata.subtitle')
                        ->label('Etykieta nad nazwiskiem / podtytuł (np. na stronie „O mnie”)')
                        ->placeholder('np. Aktor • Tancerz • Malarz • Rocznik ‘88')
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('template') === 'about'),
                    Textarea::make('excerpt')
                        ->label(fn (Get $get): string => $get('template') === 'about'
                            ? 'Cytat wyróżniony pod tytułem (np. o improwizacji i wolności w sztuce)'
                            : 'Krótki opis / zajawka')
                        ->placeholder('„Wydawać się może, że klasyczne malarstwo i sztuka figuratywna bardzo ograniczają twórcę..."')
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('content')
                        ->label(fn (Get $get): string => $get('template') === 'about'
                            ? 'Główna biografia (lewa kolumna, akapity oddziel pustą linią)'
                            : 'Treść strony (ogólna)')
                        ->rows(8)
                        ->columnSpanFull(),
                    TextInput::make('metadata.box_title')
                        ->label('Tytuł boksu pod zdjęciem (prawa kolumna)')
                        ->placeholder('Dlaczego malarstwo tradycyjne?')
                        ->default('Dlaczego malarstwo tradycyjne?')
                        ->visible(fn (Get $get): bool => $get('template') === 'about'),
                    Textarea::make('metadata.box_text')
                        ->label('Treść boksu pod zdjęciem (np. manifest o wartości tradycyjnego malarstwa w dobie AI)')
                        ->placeholder('Wierzę, że w dobie sztucznej inteligencji i fotografii cyfrowej to właśnie tradycyjne malarstwo...')
                        ->rows(4)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('template') === 'about'),
                    TextInput::make('metadata.cta_title')
                        ->label('Nagłówek dolnego boksu zachęty (CTA)')
                        ->placeholder('Podoba Ci się moja twórczość?')
                        ->default('Podoba Ci się moja twórczość?')
                        ->visible(fn (Get $get): bool => $get('template') === 'about'),
                    Textarea::make('metadata.cta_text')
                        ->label('Treść dolnego boksu zachęty (CTA)')
                        ->placeholder('Zamów swój własny, unikalny portret ze zdjęcia lub zapoznaj się z gotowymi dziełami malarskimi dostępnymi w moim sklepie.')
                        ->rows(2)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('template') === 'about'),
                ]),
            Section::make('Struktura paragrafów i sekcji (np. regulamin, polityka prywatności)')
                ->columnSpanFull()
                ->hidden(fn (Get $get): bool => $get('template') === 'about')
                ->description('Możesz dodać wyodrębnione paragrafy dla stron prawnych. Każdy paragraf tworzy sekcję w spisie treści oraz kotwicę URL.')
                ->schema([
                    \Filament\Forms\Components\Repeater::make('metadata.sections')
                        ->label('Paragrafy i sekcje strony')
                        ->columns(2)
                        ->schema([
                            TextInput::make('label')
                                ->label('Tytuł w spisie treści (np. § 1. Postanowienia ogólne)')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                                    if (($get('id') ?? '') !== Str::slug((string) $old)) {
                                        return;
                                    }
                                    $set('id', Str::slug((string) $state));
                                }),
                            TextInput::make('id')
                                ->label('Identyfikator kotwicy URL (id)')
                                ->required()
                                ->maxLength(100),
                            \Filament\Forms\Components\RichEditor::make('content')
                                ->label('Treść paragrafu (edytor tekstu)')
                                ->columnSpanFull()
                                ->required(),
                        ])
                        ->collapsible()
                        ->cloneable()
                        ->reorderable()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Nowy paragraf'),
                ]),
            Section::make('Obrazek wyróżniający i multimedia')->columnSpanFull()
                ->schema([
                    FileUpload::make('hero_image_path')
                        ->label('Obrazek wyróżniający strony / nagłówek')
                        ->disk('public')
                        ->directory('content-pages')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('1920')
                        ->imageResizeTargetHeight('1920')
                        ->imageResizeUpscale(false)
                        ->maxSize(15360)
                        ->columnSpanFull(),
                ]),
            Section::make('Ustawienia SEO i metadane wyszukiwarki')->columnSpanFull()
                ->schema([
                    TextInput::make('seo_title')
                        ->label('Tytuł SEO (meta title)')
                        ->placeholder('Tytuł wyświetlany w wynikach Google')
                        ->maxLength(255),
                    Textarea::make('seo_description')
                        ->label('Opis SEO (meta description)')
                        ->placeholder('Zwięzły opis zawartości strony dla wyszukiwarek')
                        ->rows(3),
                    Toggle::make('is_noindex')
                        ->label('Ukryj przed wyszukiwarkami (noindex)')
                        ->default(false),
                    FileUpload::make('metadata.og_image_path')
                        ->label('Obrazek udostępniania w mediach społecznościowych (og:image)')
                        ->disk('public')
                        ->directory('seo/og')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('1200')
                        ->imageResizeTargetHeight('630')
                        ->imageResizeUpscale(false)
                        ->maxSize(10240),
                ]),
        ]);
    }
}