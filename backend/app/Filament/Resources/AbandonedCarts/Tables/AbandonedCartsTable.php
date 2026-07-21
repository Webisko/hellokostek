<?php

namespace App\Filament\Resources\AbandonedCarts\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class AbandonedCartsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->recordAction('view')
            ->columns([
                TextColumn::make('number')
                    ->label('Numer szkicu')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Klient')
                    ->state(fn (Order $record): string => trim(($record->customer_first_name ?? '') . ' ' . ($record->customer_last_name ?? '')))
                    ->searchable(['customer_first_name', 'customer_last_name']),
                TextColumn::make('customer_email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('items.name')
                    ->label('Produkty')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3),
                TextColumn::make('total_amount')
                    ->label('Suma koszyka')
                    ->money(fn ($record) => $record->currency ?? 'PLN', divideBy: 100)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Data porzucenia')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('metadata.abandoned_email_sent')
                    ->label('Przypomnienie')
                    ->state(fn (Order $record) => data_get($record->metadata, 'abandoned_email_sent') ? 'Wysłano' : 'Oczekuje')
                    ->badge()
                    ->color(fn ($state) => $state === 'Wysłano' ? 'success' : 'warning'),
            ])
            ->actions([
                ViewAction::make()->extraAttributes(['style' => 'display: none !important;'])
                    ->label('Podgląd')
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->infolist([
                        InfoSection::make('Szczegóły porzuconego koszyka')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('number')->label('Numer szkicu'),
                                TextEntry::make('customer_email')->label('Email klienta'),
                                TextEntry::make('customer_first_name')->label('Imię'),
                                TextEntry::make('customer_last_name')->label('Nazwisko'),
                                TextEntry::make('updated_at')->label('Data porzucenia')->dateTime('Y-m-d H:i:s'),
                                TextEntry::make('total_amount')->label('Suma brutto')->money(fn ($record) => $record->currency ?? 'PLN', divideBy: 100),
                                TextEntry::make('metadata.recovery_coupon_code')->label('Kod rabatowy')->default('-'),
                                TextEntry::make('metadata.recovery_link')
                                    ->label('Link odzyskiwania')
                                    ->url(fn ($record) => data_get($record->metadata, 'recovery_link'))
                                    ->openUrlInNewTab()
                                    ->default('-')
                                    ->columnSpanFull(),
                            ]),
                        InfoSection::make('Pozycje w koszyku')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->label('')
                                    ->schema([
                                        TextEntry::make('name')->label('Produkt'),
                                        TextEntry::make('quantity')->label('Ilość'),
                                        TextEntry::make('total_amount')->label('Suma')->money(fn ($record) => $record->order?->currency ?? 'PLN', divideBy: 100),
                                    ])
                                    ->columns(3)
                            ])
                    ]),
                Action::make('send_reminder')
                    ->label('Wyślij przypomnienie')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        try {
                            $settings = app(\App\Support\StoreSettings::class);
                            $emailService = app(\App\Domain\Communication\TransactionalEmailService::class);

                            $couponCode = null;
                            $discountPercent = 0;
                            $discountDurationDays = 0;
                            $recoveryUrl = $settings->abandonedCartRecoveryUrl();

                            if ($settings->abandonedCartRecoveryDiscountEnabled()) {
                                $discountPercent = $settings->abandonedCartRecoveryDiscountPercentage();
                                $discountDurationDays = $settings->abandonedCartRecoveryDiscountDurationDays();
                                $couponCode = 'WROC-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6));

                                \App\Models\Coupon::query()->create([
                                    'code' => $couponCode,
                                    'name' => 'Odzyskiwanie koszyka dla ' . $record->number,
                                    'discount_type' => 'percentage',
                                    'value' => $discountPercent,
                                    'starts_at' => now(),
                                    'ends_at' => now()->addDays($discountDurationDays),
                                    'is_active' => true,
                                    'usage_limit' => 1,
                                    'usage_limit_per_customer' => 1,
                                    'metadata' => [
                                        'source' => 'abandoned_cart_recovery',
                                        'order_id' => $record->id,
                                    ],
                                ]);
                            }

                            $resumeLink = str_replace('{number}', $record->number, $recoveryUrl);
                            if ($couponCode) {
                                $resumeLink .= (str_contains($resumeLink, '?') ? '&' : '?') . 'coupon_code=' . $couponCode;
                            }

                            $metadata = $record->metadata ?? [];
                            $metadata['abandoned_email_sent'] = true;
                            $metadata['abandoned_email_sent_at'] = now()->toIso8601String();
                            $metadata['recovery_link'] = $resumeLink;
                            if ($couponCode) {
                                $metadata['recovery_coupon_code'] = $couponCode;
                                $metadata['recovery_discount_percent'] = $discountPercent;
                                $metadata['recovery_discount_ends_at'] = now()->addDays($discountDurationDays)->toIso8601String();
                            }

                            $record->forceFill(['metadata' => $metadata])->save();

                            $emailService->sendAbandonedCartEmail($record);

                            \Filament\Notifications\Notification::make()
                                ->title('Sukces')
                                ->body('Wiadomość e-mail przypominająca została wysłana do klienta.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Błąd wysyłki przypomnienia')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make()
                    ->label('Usuń')
            ]);
    }
}
