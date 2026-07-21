<?php

namespace App\Filament\Resources\RedirectRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RedirectRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('source_path')->label('Stary adres')->searchable()->sortable(),
                TextColumn::make('target_path')->label('Nowy adres')->searchable(),
                TextColumn::make('status_code')->label('HTTP')->sortable(),
                IconColumn::make('is_active')->label('Aktywny')->boolean(),
                TextColumn::make('hit_count')->label('Trafienia')->sortable(),
                TextColumn::make('last_hit_at')->label('Ostatnie trafienie')->dateTime('Y-m-d H:i')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status_code')
                    ->label('Status HTTP')
                    ->options([
                        301 => '301',
                        302 => '302',
                    ]),
                TernaryFilter::make('is_active')->label('Aktywny'),
            ])
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Podgląd')->extraAttributes(['style' => 'display: none !important;'])
                    ->slideOver()
                    ->extraModalFooterActions([
                        EditAction::make()
                            ->button()
                            ->label('Edytuj')
                            ->slideOver()
                            ->cancelParentActions(),
                    ]),
                EditAction::make()->iconButton()->tooltip('Edytuj')->color('violet')->slideOver(),
                DeleteAction::make()->iconButton()->tooltip('Usuń'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}