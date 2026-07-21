<?php

namespace App\Filament\Resources\GalleryArtworks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
                        ->getOptionLabelFromRecordUsing(fn ($record) => is_array($record->name) ? ($record->name['pl'] ?? reset($record->name)) : $record->name)
                        ->searchable()
                        ->preload(),

                    TextInput::make('year')
                        ->label('Rok powstania')
                        ->required()
                        ->placeholder('np. 2024')
                        ->maxLength(32),

                    TextInput::make('original_url')
                        ->label('Opcjonalny link zewnętrzny')
                        ->placeholder('np. https://hellokostek.pl/stray/')
                        ->url()
                        ->maxLength(255),

                    TextInput::make('sort_order')
                        ->label('Kolejność sortowania')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Toggle::make('is_active')
                        ->label('Aktywny w portfolio')
                        ->default(true),

                    Tabs::make('Tłumaczenia')
                        ->tabs([
                            Tabs\Tab::make('Polski')
                                ->schema([
                                    TextInput::make('title.pl')
                                        ->label('Tytuł')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('technique.pl')
                                        ->label('Technika')
                                        ->required()
                                        ->placeholder('np. olej, akwarela, rysunek')
                                        ->maxLength(255),
                                ]),
                            Tabs\Tab::make('English')
                                ->schema([
                                    TextInput::make('title.en')
                                        ->label('Title (EN)')
                                        ->maxLength(255),
                                    TextInput::make('technique.en')
                                        ->label('Technique (EN)')
                                        ->placeholder('np. oil, watercolor, drawing')
                                        ->maxLength(255),
                                ]),
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make('Zdjęcie')
                ->columnSpanFull()
                ->schema([
                    FileUpload::make('image_path')
                        ->label('Zdjęcie dzieła')
                        ->disk('public')
                        ->directory('gallery')
                        ->image()
                        ->imageEditor()
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
