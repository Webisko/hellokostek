<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    #[Url]
    public ?string $record = null;

    public function mount(): void
    {
        parent::mount();

        if ($this->record && Order::query()->withTrashed()->whereKey($this->record)->exists()) {
            $this->bootedInteractsWithTable();
            $this->mountTableAction('view', $this->record);
        }
    }

    public function getTabs(): array
    {
        $allCount = Order::query()->count();
        $toShipCount = Order::query()
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['shipped', 'completed', 'cancelled', 'draft'])
            ->count();
        $awaitingPaymentCount = Order::query()
            ->whereIn('payment_status', ['pending', 'awaiting_payment', 'initiated', 'pending_gateway', 'pending_collection'])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->count();
        $shippedCount = Order::query()
            ->whereIn('status', ['shipped', 'completed'])
            ->count();

        return [
            'wszystkie' => Tab::make('Wszystkie')
                ->badge($allCount),
            'do_wysylki' => Tab::make('Do wysyłki')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('payment_status', 'paid')
                    ->whereNotIn('status', ['shipped', 'completed', 'cancelled', 'draft']))
                ->badge($toShipCount > 0 ? $toShipCount : null)
                ->badgeColor('warning'),
            'oczekujace' => Tab::make('Oczekują na płatność')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereIn('payment_status', ['pending', 'awaiting_payment', 'initiated', 'pending_gateway', 'pending_collection'])
                    ->whereNotIn('status', ['cancelled', 'draft']))
                ->badge($awaitingPaymentCount > 0 ? $awaitingPaymentCount : null)
                ->badgeColor('gray'),
            'wyslane' => Tab::make('Wysłane i zrealizowane')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', ['shipped', 'completed']))
                ->badge($shippedCount > 0 ? $shippedCount : null)
                ->badgeColor('success'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus'),
            Action::make('exportOrders')
                ->label('Eksport CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('lime')
                ->url(route('admin.exports.orders'), shouldOpenInNewTab: true),
        ];
    }
}
