<?php

namespace App\Filament\Resources\AdminActivityLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Podsumowanie')->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')->label('Data')->dateTime('Y-m-d H:i'),
                        TextEntry::make('event')->label('Zdarzenie')->badge(),
                        TextEntry::make('actor.name')->label('Operator')->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                        TextEntry::make('subject_type')->label('Typ obiektu'),
                        TextEntry::make('summary')->label('Podsumowanie')->columnSpanFull(),
                    ]),
                Section::make('Zmiany')->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        KeyValueEntry::make('old_values')->label('Przed zmianami'),
                        KeyValueEntry::make('new_values')->label('Po zmianach'),
                    ]),
            ]);
    }
}