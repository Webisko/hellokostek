<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Statystyki klienta')->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('ltv')
                            ->label('Suma wydana')
                            ->state(fn ($record): float => $record->orders()->where('payment_status', 'paid')->sum('total_amount'))
                            ->money('PLN', divideBy: 100)
                            ->weight('bold')
                            ->icon('heroicon-o-currency-dollar')
                            ->iconColor('success'),
                        TextEntry::make('orders_count')
                            ->label('Ukończone zamówienia')
                            ->state(fn ($record): int => $record->customerProfile?->completed_orders_count ?? $record->orders()->where('status', 'completed')->count())
                            ->weight('bold')
                            ->icon('heroicon-o-shopping-bag')
                            ->iconColor('info'),
                        TextEntry::make('customerProfile.segment')
                            ->label('Segment klienta')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => CustomerResource::formatStateLabel($state))
                            ->color(fn ($state): string => CustomerResource::segmentColor($state))
                            ->icon(fn ($state): ?string => match ($state instanceof \BackedEnum ? $state->value : (string) $state) {
                                'loyal_five' => 'heroicon-o-sparkles',
                                'loyal_eight' => 'heroicon-o-trophy',
                                'wholesale_thirty' => 'heroicon-o-briefcase',
                                'regular' => 'heroicon-o-user',
                                default => null,
                            }),
                    ]),

                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            Section::make('Historia zamówień')->columnSpanFull()
                                ->schema([
                                    RepeatableEntry::make('orders')
                                        ->label('Zamówienia')
                                        ->contained(false)
                                        ->table([
                                            TableColumn::make('Numer'),
                                            TableColumn::make('Status'),
                                            TableColumn::make('Płatność'),
                                            TableColumn::make('Suma'),
                                            TableColumn::make('Data'),
                                        ])
                                        ->schema([
                                            TextEntry::make('number')
                                                ->label('Numer')
                                                ->icon('heroicon-o-document-text')
                                                ->weight('bold')
                                                ->color('primary')
                                                ->url(fn ($record) => \App\Filament\Resources\Orders\OrderResource::getUrl('index', ['record' => $record->id])),
                                            TextEntry::make('status')
                                                ->label('Status')
                                                ->badge()
                                                ->formatStateUsing(fn ($state): string => \App\Filament\Resources\Orders\OrderResource::formatStateLabel($state, \App\Filament\Resources\Orders\OrderResource::statusOptions()))
                                                ->color(fn (?string $state): string => match ($state) {
                                                    'placed', 'completed' => 'success',
                                                    'shipped' => 'info',
                                                    'cancelled' => 'danger',
                                                    'draft' => 'gray',
                                                    default => 'gray',
                                                })
                                                ->icon(fn (?string $state): ?string => match ($state) {
                                                    'draft' => 'heroicon-o-pencil',
                                                    'placed' => 'heroicon-o-clock',
                                                    'shipped' => 'heroicon-o-truck',
                                                    'completed' => 'heroicon-o-check-circle',
                                                    'cancelled' => 'heroicon-o-x-circle',
                                                    default => null,
                                                }),
                                            TextEntry::make('payment_status')
                                                ->label('Płatność')
                                                ->badge()
                                                ->formatStateUsing(fn ($state): string => \App\Filament\Resources\Orders\OrderResource::formatStateLabel($state, \App\Filament\Resources\Orders\OrderResource::paymentStatusOptions()))
                                                ->color(fn (?string $state): string => match ($state) {
                                                    'paid' => 'success',
                                                    'pending', 'awaiting_payment', 'initiated', 'pending_gateway', 'pending_collection' => 'warning',
                                                    'failed', 'configuration_required' => 'danger',
                                                    default => 'gray',
                                                })
                                                ->icon(fn (?string $state): ?string => match ($state) {
                                                    'paid' => 'heroicon-o-check-circle',
                                                    'pending', 'awaiting_payment', 'initiated', 'pending_gateway', 'pending_collection' => 'heroicon-o-clock',
                                                    'failed', 'configuration_required' => 'heroicon-o-exclamation-circle',
                                                    default => null,
                                                }),
                                            TextEntry::make('total_amount')
                                                ->label('Suma')
                                                ->money(fn ($record): string => $record->currency ?: 'PLN', divideBy: 100)
                                                ->weight('bold'),
                                            TextEntry::make('placed_at')
                                                ->label('Data')
                                                ->dateTime('Y-m-d H:i'),
                                        ]),
                                ]),
                        ])->columnSpan(2),

                        Group::make([
                            Section::make('Dane podstawowe')->columnSpanFull()
                                ->columns(1)
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Imię i nazwisko')
                                        ->icon('heroicon-o-user')
                                        ->weight('bold'),
                                    TextEntry::make('email')
                                        ->label('E-mail')
                                        ->icon('heroicon-o-envelope')
                                        ->copyable(),
                                ]),
                            Section::make('Profil klienta')->columnSpanFull()
                                ->columns(1)
                                ->schema([
                                    TextEntry::make('customerProfile.phone')
                                        ->label('Telefon')
                                        ->icon('heroicon-o-phone')
                                        ->copyable()
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                    TextEntry::make('customerProfile.marketing_consent_at')
                                        ->label('Zgoda marketingowa')
                                        ->dateTime('Y-m-d H:i')
                                        ->icon('heroicon-o-shield-check')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                    TextEntry::make('customerProfile.last_order_at')
                                        ->label('Ostatnie zamówienie')
                                        ->dateTime('Y-m-d H:i')
                                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-'),
                                ]),
                        ])->columnSpan(1),
                    ])
            ]);
    }
}