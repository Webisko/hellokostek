<?php

namespace App\Filament\Resources\GalleryArtworks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GalleryArtworkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dzieło sztuki')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('category_id')
                        ->label('Kategoria')
                        ->relationship('category', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                        ->searchable()
                        ->preload(),

                    TextInput::make('title')
                        ->label('Tytuł')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('technique')
                        ->label('Technika wykonania (pierwszy punkt w powiększeniu)')
                        ->placeholder('np. Cienkopisy i akwarela na papierze')
                        ->datalist([
                            'Olej na płótnie',
                            'Olej na płótnie owalnym',
                            'Akryl na płótnie',
                            'Rysunek ołówkiem na papierze',
                            'Cienkopisy i akwarela na papierze',
                            'Suchy pastel i ołówek na papierze',
                            'Akwarela na papierze bawełnianym',
                        ])
                        ->dehydrateStateUsing(fn ($state) => $state ?? '')
                        ->maxLength(255),

                    TextInput::make('format')
                        ->label('Format')
                        ->placeholder('np. 30x40 cm')
                        ->datalist([
                            '30x40 cm',
                            '40x50 cm',
                            '50x70 cm',
                            'A4',
                            'A3 (297 x 420 mm)',
                        ])
                        ->maxLength(100),

                    TextInput::make('year')
                        ->label('Rok powstania')
                        ->placeholder('np. 2024')
                        ->default((string) date('Y'))
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? (string) $state : (string) date('Y'))
                        ->maxLength(32),

                    TextInput::make('original_url')
                        ->label('Opcjonalny link zewnętrzny')
                        ->placeholder('np. https://hellokostek.pl/stray/')
                        ->url()
                        ->maxLength(255),

                    Textarea::make('metadata.bullet_points')
                        ->label('Dodatkowe podpunkty opisu (w powiększeniu zdjęcia)')
                        ->placeholder("Każda nowa linia to osobny punkt pod zdjęciem.\nZostaw puste, jeśli chcesz wyświetlić tylko technikę wykonania (lub domyślne punkty kategorii).")
                        ->helperText('Wpisz każdy podpunkt w nowej linii. Jeśli pole jest puste, wyświetlone zostanie tylko pole „Technika wykonania” powyżej.')
                        ->rows(4)
                        ->columnSpanFull(),

                    \Filament\Forms\Components\Hidden::make('sort_order')
                        ->default(fn () => (\App\Models\GalleryArtwork::max('sort_order') ?? 0) + 10),

                    Toggle::make('is_active')
                        ->label('Aktywny w portfolio')
                        ->default(true),
                ]),

            Section::make('Zdjęcie')
                ->columnSpanFull()
                ->schema([
                    FileUpload::make('image_path')
                        ->label('Zdjęcie dzieła')
                        ->disk('public')
                        ->directory('gallery')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('1920')
                        ->imageResizeTargetHeight('1920')
                        ->imageResizeUpscale(false)
                        ->maxSize(15360)
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
