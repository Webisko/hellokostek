<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Podstawowe')->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('slug')
                            ->label('Adres URL (slug)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Tabs::make('Language')
                            ->tabs([
                                Tabs\Tab::make('Polski')
                                    ->schema([
                                        TextInput::make('name.pl')
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
                                        Textarea::make('description.pl')
                                            ->label('Opis')
                                            ->rows(4),
                                    ]),
                                Tabs\Tab::make('English')
                                    ->schema([
                                        TextInput::make('name.en')
                                            ->label('Name (EN)')
                                            ->maxLength(255),
                                        Textarea::make('description.en')
                                            ->label('Description (EN)')
                                            ->rows(4),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('SEO')->columnSpanFull()
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('Tytul SEO')
                            ->maxLength(255),
                        Textarea::make('seo_description')
                            ->label('Opis SEO')
                            ->rows(3),
                        Toggle::make('is_noindex')
                            ->label('Zablokuj indeksowanie tej kategorii (noindex)')
                            ->default(false),
                    ]),
                Section::make('Atrybuty kategorii')->columnSpanFull()
                    ->schema([
                        CheckboxList::make('attributes')
                            ->label('Dostepne atrybuty')
                            ->relationship(
                                name: 'attributes',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}