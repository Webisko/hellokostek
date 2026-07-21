<?php

namespace App\Filament\Resources\QuestionnaireSubmissions\Schemas;

use App\Models\QuestionnaireSubmission;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionnaireSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Zgloszenie')
                ->columns(3)
                ->schema([
                    TextEntry::make('created_at')->label('Data')->dateTime('Y-m-d H:i'),
                    TextEntry::make('questionnaire_key')->label('Ankieta')->badge(),
                    TextEntry::make('source')->label('Zrodlo')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('name')->label('Imie'),
                    TextEntry::make('email')->label('E-mail'),
                    TextEntry::make('consented_to_marketing')->label('Zgoda marketingowa')->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('consented_at')->label('Zgoda od')->dateTime('Y-m-d H:i')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('coupon_code')->label('Kod rabatowy')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                    TextEntry::make('result_email_status')
                        ->label('Status maila z wynikiem')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => QuestionnaireSubmission::emailStatusLabel($state))
                        ->color(fn (?string $state): string => QuestionnaireSubmission::emailStatusColor($state)),
                    TextEntry::make('admin_notification_status')
                        ->label('Status powiadomienia admina')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => QuestionnaireSubmission::emailStatusLabel($state))
                        ->color(fn (?string $state): string => QuestionnaireSubmission::emailStatusColor($state)),
                    TextEntry::make('recommended_products')
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
                        ->columnSpanFull(),
                    TextEntry::make('answers')
                        ->label('Odpowiedzi')
                        ->formatStateUsing(function ($state, $record): string {
                            $answers = $record->answers;

                            if (is_string($answers)) {
                                $answers = json_decode($answers, true);
                            }

                            return is_array($answers)
                                ? json_encode($answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : '-';
                        })
                        ->columnSpanFull()
                        ->prose(),
                    KeyValueEntry::make('metadata')->label('Metadata')->columnSpanFull(),
                ]),
        ]);
    }
}