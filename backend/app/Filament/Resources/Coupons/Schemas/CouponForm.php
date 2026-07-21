<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Filament\Resources\Coupons\CouponResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use App\Support\StoreSettings;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Podstawowe')->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Kod')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? mb_strtoupper(trim($state)) : null)
                            ->suffixAction(
                                \Filament\Actions\Action::make('generateCode')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Generuj losowy kod')
                                    ->action(function (Set $set) {
                                        $randomCode = 'DISC-' . strtoupper(\Illuminate\Support\Str::random(8));
                                        $set('code', $randomCode);
                                    })
                            ),
                        TextInput::make('name')
                            ->label('Nazwa robocza')
                            ->maxLength(255),
                        Select::make('discount_type')
                            ->label('Typ rabatu')
                            ->options(CouponResource::discountTypeOptions())
                            ->required()
                            ->default('percentage')
                            ->live()
                            ->native(false),
                        TextInput::make('value')
                            ->label('Wartość')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->formatStateUsing(function ($state, Get $get) {
                                if ($state === null) return null;
                                return $get('discount_type') === 'percentage' ? $state : $state / 100;
                            })
                            ->dehydrateStateUsing(function ($state, Get $get) {
                                if ($state === null) return null;
                                return $get('discount_type') === 'percentage' ? (int)$state : (int)round($state * 100);
                            })
                            ->helperText(fn (Get $get): string => $get('discount_type') === 'percentage'
                                ? 'Dla rabatu procentowego podaj wartość 1-100.'
                                : 'Dla rabatu kwotowego podaj wartość w PLN.'),
                    ]),
                Section::make('Ograniczenia')->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('currency')
                            ->label('Waluta')
                            ->required()
                            ->default(app(StoreSettings::class)->currency())
                            ->minLength(3)
                            ->maxLength(3)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? mb_strtoupper(trim($state)) : null),
                        TextInput::make('minimum_subtotal_amount')
                            ->label('Minimalny subtotal (PLN)')
                            ->numeric()
                            ->minValue(0)
                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                        TextInput::make('usage_limit')
                            ->label('Limit uzyc lacznie')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('usage_limit_per_customer')
                            ->label('Limit uzyc na klienta')
                            ->numeric()
                            ->minValue(1),
                        DateTimePicker::make('starts_at')
                            ->label('Aktywny od'),
                        DateTimePicker::make('ends_at')
                            ->label('Aktywny do')
                            ->after('starts_at'),
                        Toggle::make('is_active')
                            ->label('Aktywny')
                            ->default(true),
                    ]),
                Section::make('Metadata')->columnSpanFull()
                    ->schema([
                        KeyValue::make('metadata')
                            ->label('Metadata')
                            ->keyLabel('Klucz')
                            ->valueLabel('Wartosc'),
                    ]),
            ]);
    }
}