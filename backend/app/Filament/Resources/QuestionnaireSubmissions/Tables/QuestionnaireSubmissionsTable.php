<?php

namespace App\Filament\Resources\QuestionnaireSubmissions\Tables;

use App\Models\QuestionnaireSubmission;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class QuestionnaireSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Data')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('questionnaire_key')->label('Ankieta')->badge()->searchable()->sortable(),
                TextColumn::make('source')->label('Zrodlo')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label('Imie')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->sortable(),
                TextColumn::make('recommended_products')
                    ->label('Rekomendacje')
                    ->formatStateUsing(function ($state, $record): string {
                        $products = $record->recommended_products;

                        if (is_string($products)) {
                            $products = json_decode($products, true);
                        }

                        if (! filled($products) || ! is_array($products)) {
                            return '-';
                        }

                        return implode(', ', $record->recommendedProductLabels());
                    })
                    ->wrap(),
                TextColumn::make('coupon_code')->label('Kod rabatowy')->toggleable(),
                IconColumn::make('consented_to_marketing')->label('Zgoda marketingowa')->boolean(),
                TextColumn::make('result_email_status')
                    ->label('Mail z wynikiem')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => QuestionnaireSubmission::emailStatusLabel($state))
                    ->color(fn (?string $state): string => QuestionnaireSubmission::emailStatusColor($state))
                    ->sortable(),
                TextColumn::make('admin_notification_status')
                    ->label('Powiadomienie admina')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => QuestionnaireSubmission::emailStatusLabel($state))
                    ->color(fn (?string $state): string => QuestionnaireSubmission::emailStatusColor($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('health_window')
                    ->label('Zakres health view')
                    ->options([
                        'delivery_issue' => 'Problemy z dostarczeniem',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'delivery_issue' => $query->where(function (Builder $query): void {
                                $query
                                    ->where('result_email_status', 'failed')
                                    ->orWhere('admin_notification_status', 'failed');
                            }),
                            default => $query,
                        };
                    }),
                TernaryFilter::make('consented_to_marketing')->label('Zgoda marketingowa'),
                SelectFilter::make('questionnaire_key')
                    ->label('Ankieta')
                    ->options(fn (): array => \App\Models\QuestionnaireSubmission::query()
                        ->orderBy('questionnaire_key')
                        ->distinct()
                        ->pluck('questionnaire_key', 'questionnaire_key')
                        ->all()),
                SelectFilter::make('result_email_status')
                    ->label('Mail z wynikiem')
                    ->options(QuestionnaireSubmission::emailStatusOptions()),
                SelectFilter::make('admin_notification_status')
                    ->label('Powiadomienie admina')
                    ->options(QuestionnaireSubmission::emailStatusOptions()),
                SelectFilter::make('source')
                    ->label('Zrodlo')
                    ->options(fn (): array => \App\Models\QuestionnaireSubmission::query()
                        ->whereNotNull('source')
                        ->orderBy('source')
                        ->distinct()
                        ->pluck('source', 'source')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}