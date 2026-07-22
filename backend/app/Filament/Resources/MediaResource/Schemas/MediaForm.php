<?php

namespace App\Filament\Resources\MediaResource\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Plik multimedialny')
                ->columnSpanFull()
                ->schema([
                    FileUpload::make('file_path')
                        ->label('Wgraj plik')
                        ->disk('public')
                        ->directory('media')
                        ->visibility('public')
                        ->preserveFilenames()
                        ->required()
                        ->columnSpanFull()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if (! $state) {
                                return;
                            }

                            $filePath = is_array($state) ? reset($state) : $state;
                            if (is_string($filePath)) {
                                $fileName = basename($filePath);
                                $set('file_name', $fileName);
                                $set('title', pathinfo($fileName, PATHINFO_FILENAME));

                                $fullPath = Storage::disk('public')->path($filePath);
                                if (file_exists($fullPath)) {
                                    $set('file_size', filesize($fullPath));
                                    $set('mime_type', mime_content_type($fullPath));

                                    $imageSize = @getimagesize($fullPath);
                                    if ($imageSize) {
                                        $set('width', $imageSize[0]);
                                        $set('height', $imageSize[1]);
                                    }
                                }
                            }
                        }),
                ]),

            Section::make('Metadane i SEO')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Tytuł / Nazwa pliku')
                        ->required()
                        ->maxLength(255),

                    Select::make('category')
                        ->label('Kategoria / Przeznaczenie')
                        ->options([
                            'general' => 'Ogólne',
                            'products' => 'Produkty',
                            'gallery' => 'Galeria',
                            'branding' => 'System / Branding',
                        ])
                        ->default('general')
                        ->required(),

                    TextInput::make('alt_text')
                        ->label('Tekst alternatywny (ALT)')
                        ->placeholder('Opis pliku dla SEO i czytników ekrany')
                        ->columnSpanFull()
                        ->maxLength(255),

                    TextInput::make('file_name')
                        ->label('Fizyczna nazwa pliku')
                        ->readOnly(),

                    TextInput::make('mime_type')
                        ->label('Typ MIME')
                        ->readOnly(),
                ]),
        ]);
    }
}
