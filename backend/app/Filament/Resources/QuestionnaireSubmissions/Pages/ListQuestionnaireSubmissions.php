<?php

namespace App\Filament\Resources\QuestionnaireSubmissions\Pages;

use App\Filament\Resources\QuestionnaireSubmissions\QuestionnaireSubmissionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListQuestionnaireSubmissions extends ListRecords
{
    protected static string $resource = QuestionnaireSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportQuestionnaireSubmissions')
                ->label('Eksport CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('admin.exports.questionnaire-submissions'), shouldOpenInNewTab: true),
        ];
    }
}