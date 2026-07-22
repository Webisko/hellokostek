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
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                        ->searchable()
                        ->preload(),

                    TextInput::make('title')
                        ->label('Tytuł')
                        ->required()
                        ->maxLength(255),

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
