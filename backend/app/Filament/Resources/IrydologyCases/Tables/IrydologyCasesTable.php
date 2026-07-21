<?php

namespace App\Filament\Resources\IrydologyCases\Tables;

use App\Models\IrydologyCase;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IrydologyCasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Data')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('order.number')->label('Zamowienie')->searchable()->sortable(),
                TextColumn::make('package_name')->label('Pakiet')->searchable()->sortable(),
                TextColumn::make('customer_email')->label('E-mail')->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => IrydologyCase::statusLabel($state))
                    ->color(fn (?string $state): string => IrydologyCase::statusColor($state))
                    ->sortable(),
                TextColumn::make('analysis_due_at')
                    ->label('Termin analizy')
                    ->dateTime('Y-m-d H:i')
                    ->color(fn (IrydologyCase $record): string => $record->isOverdue() ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('completed_at')->label('Zakonczono')->dateTime('Y-m-d H:i')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(IrydologyCase::statusOptions()),
                SelectFilter::make('analysis_window')
                    ->label('Termin analizy')
                    ->options([
                        'overdue' => 'Po terminie',
                        'scheduled' => 'Zaplanowane i nieprzeterminowane',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'overdue' => $query
                                ->whereNull('completed_at')
                                ->whereNotNull('analysis_due_at')
                                ->where('analysis_due_at', '<', now()),
                            'scheduled' => $query
                                ->whereNull('completed_at')
                                ->whereNotNull('analysis_due_at')
                                ->where('analysis_due_at', '>=', now()),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}