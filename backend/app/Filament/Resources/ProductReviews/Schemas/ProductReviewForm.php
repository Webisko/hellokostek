<?php

namespace App\Filament\Resources\ProductReviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Szczegóły opinii')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('emoji')
                        ->label('Wybór Emoji')
                        ->placeholder('np. 🐶, ✨, ❤️, 🎨')
                        ->maxLength(16),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'publiczny' => 'Publiczny (widoczna na stronie)',
                            'szkic' => 'Szkic (niewidoczna)',
                        ])
                        ->default('publiczny')
                        ->required()
                        ->native(false),
                    TextInput::make('customer_name')
                        ->label('Autor')
                        ->placeholder('np. Kasia')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('meta')
                        ->label('Opis / Kontekst')
                        ->placeholder('np. Portret z trzema psami')
                        ->maxLength(255),
                    Textarea::make('comment')
                        ->label('Treść opinii')
                        ->rows(5)
                        ->columnSpanFull()
                        ->required()
                        ->maxLength(2000),
                ]),
        ]);
    }
}
