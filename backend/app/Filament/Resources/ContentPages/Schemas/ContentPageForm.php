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
                    TextInput::make('title.pl')
                        ->label('Tytuł strony')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
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
                        ->native(false),
                    Toggle::make('is_active')
                        ->label('Strona aktywna')
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label('Kolejność w menu')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    DateTimePicker::make('published_at')
                        ->label('Data publikacji'),
                    Textarea::make('excerpt.pl')
                        ->label('Krótki opis / Zajawka')
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('content.pl')
                        ->label('Treść strony (ogólna)')
                        ->rows(6)
                        ->columnSpanFull(),
                ]),
            Section::make('Struktura Paragrafów i Sekcji (np. Regulamin, Polityka Prywatności)')
                ->columnSpanFull()
                ->description('Możesz dodać wyodrębnione paragrafy dla stron prawnych. Każdy paragraf tworzy sekcję w spisie treści oraz kotwicę URL.')
                ->schema([
                    \Filament\Forms\Components\Repeater::make('metadata.sections')
                        ->label('Paragrafy / Sekcje strony')
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
                                ->label('Treść paragrafu (WYSIWYG)')
                                ->columnSpanFull()
                                ->required(),
                        ])
                        ->collapsible()
                        ->cloneable()
                        ->reorderable()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Nowy paragraf'),
                ]),
            Section::make('Obrazek wyróżniający i Media')->columnSpanFull()
                ->schema([
                    FileUpload::make('hero_image_path')
                        ->label('Obrazek wyróżniający strony / Nagłówek')
                        ->disk('public')
                        ->directory('content-pages')
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),
            Section::make('Ustawienia SEO i Metadane wyszukiwarki')->columnSpanFull()
                ->schema([
                    TextInput::make('seo_title')
                        ->label('Tytuł SEO (Meta Title)')
                        ->placeholder('Tytuł wyświetlany w wynikach Google')
                        ->maxLength(255),
                    Textarea::make('seo_description')
                        ->label('Opis SEO (Meta Description)')
                        ->placeholder('Zwięzły opis zawartości strony dla wyszukiwarek')
                        ->rows(3),
                    Toggle::make('is_noindex')
                        ->label('Ukryj przed wyszukiwarkami (noindex)')
                        ->default(false),
                    FileUpload::make('metadata.og_image_path')
                        ->label('Dedykowany obrazek Social Media (og:image)')
                        ->disk('public')
                        ->directory('seo/og')
                        ->image()
                        ->imageEditor(),
                ]),
        ]);
    }
}