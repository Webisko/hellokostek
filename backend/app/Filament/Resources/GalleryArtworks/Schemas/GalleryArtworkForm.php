<?php

namespace App\Filament\Resources\GalleryArtworks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class GalleryArtworkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Group::make()
                ->columnSpanFull()
                ->columns(['default' => 1, 'lg' => 12])
                ->schema([
                    Section::make('Szczegóły pracy')
                        ->columnSpan(['default' => 1, 'lg' => 7])
                        ->columns(['default' => 1, 'md' => 2])
                        ->extraAttributes(['class' => 'h-full'])
                        ->schema([
                            TextInput::make('title')
                                ->label('Tytuł')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Select::make('category_id')
                                ->label('Kategoria')
                                ->relationship('category', 'name')
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                                ->preload()
                                ->native(false)
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Nazwa kategorii')
                                        ->placeholder('np. Pastel')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->createOptionModalHeading('Dodaj nową kategorię')
                                ->createOptionAction(fn (\Filament\Actions\Action $action) => $action->tooltip('Dodaj nową kategorię'))
                                ->createOptionUsing(fn (array $data): int => \App\Models\ProductCategory::createQuickByName((string) $data['name'])->getKey()),

                            TextInput::make('year')
                                ->label('Rok powstania')
                                ->placeholder('np. 2024')
                                ->default((string) date('Y'))
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? (string) $state : (string) date('Y'))
                                ->maxLength(32),

                            Select::make('technique')
                                ->label('Technika wykonania (pierwszy punkt w powiększeniu)')
                                ->placeholder('Wybierz technikę wykonania')
                                ->options(fn ($state): array => app(\App\Support\StoreSettings::class)->artworkTechniqueOptions(is_string($state) ? $state : null))
                                ->native(false)
                                ->dehydrateStateUsing(fn ($state) => $state ?? '')
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Nazwa techniki')
                                        ->placeholder('np. Pastel suchy na papierze')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->createOptionModalHeading('Dodaj nową technikę')
                                ->createOptionAction(fn (\Filament\Actions\Action $action) => $action->tooltip('Dodaj nową technikę do listy'))
                                ->createOptionUsing(fn (array $data): string => app(\App\Support\StoreSettings::class)->addArtworkTechnique((string) $data['name']))
                                ->columnSpanFull(),

                            Select::make('format')
                                ->label('Format')
                                ->placeholder('Wybierz format')
                                ->options(fn ($state): array => app(\App\Support\StoreSettings::class)->artworkFormatOptions(is_string($state) ? $state : null))
                                ->native(false)
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Format / wymiary')
                                        ->placeholder('np. 60x80 cm')
                                        ->required()
                                        ->maxLength(100),
                                ])
                                ->createOptionModalHeading('Dodaj nowy format')
                                ->createOptionAction(fn (\Filament\Actions\Action $action) => $action->tooltip('Dodaj nowy format do listy'))
                                ->createOptionUsing(fn (array $data): string => app(\App\Support\StoreSettings::class)->addArtworkFormat((string) $data['name'])),

                            TextInput::make('original_url')
                                ->label('Opcjonalny link zewnętrzny')
                                ->placeholder('np. https://hellokostek.pl/stray/')
                                ->url()
                                ->maxLength(255),

                            Textarea::make('metadata.bullet_points')
                                ->label('Dodatkowe podpunkty opisu (w powiększeniu zdjęcia)')
                                ->placeholder("Każda nowa linia to osobny punkt pod zdjęciem.\nZostaw puste, jeśli chcesz wyświetlić tylko technikę wykonania (lub domyślne punkty kategorii).")
                                ->helperText('Wpisz każdy podpunkt w nowej linii. Jeśli pole jest puste, wyświetlone zostanie tylko pole „Technika wykonania” powyżej.')
                                ->rows(3)
                                ->columnSpanFull(),

                            \Filament\Forms\Components\Hidden::make('sort_order')
                                ->default(fn () => (\App\Models\GalleryArtwork::max('sort_order') ?? 0) + 10),
                        ]),

                    Section::make('Publikacja i zdjęcie')
                        ->columnSpan(['default' => 1, 'lg' => 5])
                        ->extraAttributes(['class' => 'h-full'])
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Widoczne w galerii')
                                ->default(true),

                            FileUpload::make('image_path')
                                ->label('Zdjęcie pracy')
                                ->helperText('Kliknij ikonę ołówka na zdjęciu, aby przyciąć lub obrócić kadr.')
                                ->disk('public')
                                ->directory('gallery')
                                ->visibility('public')
                                ->image()
                                ->imageEditor()
                                ->imagePreviewHeight('400')
                                ->imageResizeMode('contain')
                                ->imageResizeTargetWidth('1920')
                                ->imageResizeTargetHeight('1920')
                                ->imageResizeUpscale(false)
                                ->maxSize(15360)
                                ->required()
                                ->extraAttributes(['class' => 'fi-artwork-featured-upload']),
                        ]),
                ]),
        ]);
    }
}
