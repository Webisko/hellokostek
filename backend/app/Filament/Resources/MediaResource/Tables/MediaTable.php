<?php

namespace App\Filament\Resources\MediaResource\Tables;

use App\Models\Media;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('file_path')
                    ->label('Podgląd')
                    ->disk('public')
                    ->square()
                    ->size(60),

                TextColumn::make('title')
                    ->label('Tytuł / Nazwa')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Media $record): string => $record->file_name ?? ''),

                TextColumn::make('category')
                    ->label('Kategoria')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'products' => 'Produkty',
                        'gallery' => 'Galeria',
                        'branding' => 'System / Branding',
                        default => 'Ogólne',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'products' => 'info',
                        'gallery' => 'success',
                        'branding' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('formatted_size')
                    ->label('Rozmiar')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('file_size', $direction)),

                TextColumn::make('created_at')
                    ->label('Data dodania')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategoria')
                    ->options([
                        'general' => 'Ogólne',
                        'products' => 'Produkty',
                        'gallery' => 'Galeria',
                        'branding' => 'System / Branding',
                    ]),
            ])
            ->recordActions([
                Action::make('copy_url')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedClipboardDocument)
                    ->tooltip('Kopiuj URL do schowka')
                    ->color('violet')
                    ->extraAttributes(fn (Media $record): array => [
                        'onclick' => 'navigator.clipboard.writeText("' . addslashes($record->url) . '"); alert("Skopiowano URL pliku do schowka!"); return false;',
                    ]),

                Action::make('download')
                    ->iconButton()
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->tooltip('Pobierz plik')
                    ->color('gray')
                    ->url(fn (Media $record): string => $record->url)
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edytuj')
                    ->color('violet')
                    ->slideOver()
                    ->modalWidth('2xl'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Usuń'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
