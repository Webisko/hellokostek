<?php

namespace App\Filament\Resources\QuestionnaireSubmissions;

use App\Filament\Resources\QuestionnaireSubmissions\Pages\ListQuestionnaireSubmissions;
use App\Filament\Resources\QuestionnaireSubmissions\Pages\ViewQuestionnaireSubmission;
use App\Filament\Resources\QuestionnaireSubmissions\Schemas\QuestionnaireSubmissionInfolist;
use App\Filament\Resources\QuestionnaireSubmissions\Tables\QuestionnaireSubmissionsTable;
use App\Models\QuestionnaireSubmission;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuestionnaireSubmissionResource extends Resource
{
    protected static ?string $model = QuestionnaireSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $modelLabel = 'zgloszenie z ankiety';

    protected static ?string $pluralModelLabel = 'ankiety';

    protected static ?string $navigationLabel = 'Ankiety';

    protected static ?int $navigationSort = 44;

    public static function infolist(Schema $schema): Schema
    {
        return QuestionnaireSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionnaireSubmissionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestionnaireSubmissions::route('/'),
            'view' => ViewQuestionnaireSubmission::route('/{record}'),
        ];
    }
}