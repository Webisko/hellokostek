<?php

namespace App\Filament\Resources\ProductAttributes\Schemas;

use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductAttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Podstawowe')->columnSpanFull()
                    ->columns(2)
                    ->schema([
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
                            }),
                        TextInput::make('slug')
                            ->label('Adres URL (slug)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('sort_order')
                            ->label('Kolejnosc')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('value_type')
                            ->label('Typ wartosci')
                            ->datalist(array_values(ProductAttributeResource::valueTypeOptions()))
                            ->default('text')
                            ->required()
                            ->maxLength(32),
                        Toggle::make('is_active')
                            ->label('Aktywny')
                            ->default(true),
                    ]),
                Section::make('Kategorie')->columnSpanFull()
                    ->schema([
                        CheckboxList::make('categories')
                            ->label('Dostepne w kategoriach')
                            ->relationship(titleAttribute: 'name')
                            ->searchable()
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}