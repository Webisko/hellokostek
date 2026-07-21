<?php

namespace App\Filament\Resources\Orders\Pages;


use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            // Generate InPost Label Action
            Action::make('generate_inpost_label')
                ->label('Generuj etykietę InPost')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->visible(fn ($record): bool => empty($record->tracking_number) && !str_contains(strtolower($record->shipping_method_code ?? ''), 'orlen'))
                ->form([
                    \Filament\Forms\Components\Select::make('package_size')
                        ->label('Gabaryt paczki')
                        ->options([
                            'A' => 'Gabaryt A (mała paczka)',
                            'B' => 'Gabaryt B (średnia paczka)',
                            'C' => 'Gabaryt C (duża paczka)',
                        ])
                        ->default('B')
                        ->required()
                        ->native(false),
                ])
                ->action(function ($record, array $data): void {
                    try {
                        $inpostService = app(\App\Domain\Commerce\Logistics\InPostService::class);
                        $result = $inpostService->generateLabel($record, $data['package_size']);

                        $record->forceFill([
                            'tracking_number' => $result['tracking_number'],
                            'carrier' => 'InPost',
                            'status' => 'shipped',
                        ])->save();

                        Notification::make()
                            ->title('Sukces')
                            ->body('Paczka została wygenerowana w InPost. Status zamówienia zmieniono na Wysłane.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Błąd generowania etykiety')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Download InPost Label Action
            Action::make('download_inpost_label')
                ->label('Pobierz etykietę InPost')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->visible(fn ($record): bool => !empty($record->tracking_number) && $record->carrier === 'InPost')
                ->url(fn ($record): string => route('admin.orders.inpost-label', ['number' => $record->number]))
                ->openUrlInNewTab(),

            // Generate ORLEN Paczka Label Action
            Action::make('generate_orlen_paczka_label')
                ->label('Generuj etykietę ORLEN Paczka')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->visible(fn ($record): bool => empty($record->tracking_number) && str_contains(strtolower($record->shipping_method_code ?? ''), 'orlen'))
                ->form([
                    \Filament\Forms\Components\Select::make('package_size')
                        ->label('Gabaryt paczki')
                        ->options([
                            'S' => 'Gabaryt S (mała paczka)',
                            'M' => 'Gabaryt M (średnia paczka)',
                            'L' => 'Gabaryt L (duża paczka)',
                        ])
                        ->default('M')
                        ->required()
                        ->native(false),
                ])
                ->action(function ($record, array $data): void {
                    try {
                        $orlenService = app(\App\Domain\Commerce\Logistics\OrlenPaczkaService::class);
                        $result = $orlenService->generateLabel($record, $data['package_size']);

                        $record->forceFill([
                            'tracking_number' => $result['tracking_number'],
                            'carrier' => 'OrlenPaczka',
                            'status' => 'shipped',
                        ])->save();

                        Notification::make()
                            ->title('Sukces')
                            ->body('Paczka została wygenerowana w ORLEN Paczka. Status zamówienia zmieniono na Wysłane.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Błąd generowania etykiety')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Download ORLEN Paczka Label Action
            Action::make('download_orlen_paczka_label')
                ->label('Pobierz etykietę ORLEN Paczka')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->visible(fn ($record): bool => !empty($record->tracking_number) && $record->carrier === 'OrlenPaczka')
                ->url(fn ($record): string => route('admin.orders.orlen-label', ['number' => $record->number]))
                ->openUrlInNewTab(),
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }
}
