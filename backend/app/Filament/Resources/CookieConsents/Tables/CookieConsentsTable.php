<?php

namespace App\Filament\Resources\CookieConsents\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CookieConsentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('consent_token')
                    ->label('Token zgody')
                    ->searchable()
                    ->limit(20),
                TextColumn::make('banner_version')
                    ->label('Wersja banera')
                    ->sortable(),
                TextColumn::make('consent_choices')
                    ->label('Udzielone zgody')
                    ->formatStateUsing(function ($state): array {
                        if (!is_array($state)) {
                            return ['-'];
                        }
                        $choices = [];
                        if ($state['analytics'] ?? false) $choices[] = 'Analityka';
                        if ($state['functional'] ?? false) $choices[] = 'Funkcjonalne';
                        if ($state['marketing'] ?? false) $choices[] = 'Marketing';
                        return empty($choices) ? ['Tylko niezbędne'] : $choices;
                    })
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Zapisano')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Podgląd')->extraAttributes(['style' => 'display: none !important;'])
                    ->slideOver()
                    ->modalWidth('5xl'),
            ]);
    }
}
