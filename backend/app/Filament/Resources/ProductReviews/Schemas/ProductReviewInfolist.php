<?php

namespace App\Filament\Resources\ProductReviews\Schemas;

use App\Models\ProductReview;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Szczegóły opinii')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('product.name')
                        ->label('Produkt'),
                    TextEntry::make('rating')
                        ->label('Ocena')
                        ->badge()
                        ->formatStateUsing(fn ($state) => str_repeat('★', $state) . ' (' . $state . '/5)')
                        ->color('warning'),
                    TextEntry::make('customer_name')
                        ->label('Nazwa klienta'),
                    TextEntry::make('customer_email')
                        ->label('E-mail klienta'),
                    TextEntry::make('is_verified_purchase')
                        ->label('Zakup zweryfikowany')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state ? 'Tak' : 'Nie')
                        ->color(fn ($state) => $state ? 'success' : 'gray'),
                    TextEntry::make('is_approved')
                        ->label('Status zatwierdzenia')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state ? 'Zatwierdzona' : 'Oczekująca')
                        ->color(fn ($state) => $state ? 'success' : 'warning'),
                    TextEntry::make('comment')
                        ->label('Komentarz / Recenzja')
                        ->columnSpanFull()
                        ->formatStateUsing(fn ($state) => filled($state) ? $state : '-'),
                    TextEntry::make('created_at')
                        ->label('Utworzono')
                        ->dateTime('Y-m-d H:i'),
                ]),
        ]);
    }
}
