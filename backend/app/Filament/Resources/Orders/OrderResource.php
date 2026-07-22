<?php

namespace App\Filament\Resources\Orders;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $slug = 'zamowienia';
    use HasDynamicNavigation;
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'zamówienie';
    protected static ?string $pluralModelLabel = 'zamówienia';
    protected static ?string $navigationLabel = 'Zamówienia';
    protected static \UnitEnum|string|null $navigationGroup = 'Sprzedaż & zapytania';
    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'customer_email', 'customer.name'];
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ])
            ->with([
                'customer',
                'items.fulfillmentActions',
                'paymentTransactions',
                'integrationLogs',
                'fulfillmentActions.orderItem',
                'adminActivityLogs.actor',
            ]);
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'draft' => 'Szkic',
            'placed' => 'Złożone',
            'shipped' => 'Wysłane',
            'completed' => 'Zakończone',
            'cancelled' => 'Anulowane',
        ];
    }

    public static function paymentStatusOptions(): array
    {
        return [
            'pending' => 'Oczekuje',
            'awaiting_payment' => 'Oczekuje na płatność',
            'paid' => 'Opłacone',
            'failed' => 'Niepowodzenie',
            'initiated' => 'Zainicjowane',
            'configuration_required' => 'Wymaga konfiguracji',
            'pending_gateway' => 'Oczekuje na bramkę',
            'pending_collection' => 'Oczekuje na pobranie',
        ];
    }

    public static function fulfillmentStatusOptions(): array
    {
        return [
            'pending' => 'Oczekuje',
            'fulfilled' => 'Zrealizowane',
        ];
    }

    public static function fulfillmentActionOptions(): array
    {
        return [
            'physical_shipping' => 'Wysyłka fizyczna',
            'digital_delivery' => 'Dostawa cyfrowa',
            'service_followup' => 'Dalsza obsługa usługi',
        ];
    }

    public static function formatStateLabel(mixed $state, ?array $options = null): string
    {
        if (blank($state)) {
            return '-';
        }

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        if ($options && isset($options[$state])) {
            return $options[$state];
        }

        $labels = [
            'physical' => 'Fizyczny',
            'digital' => 'Cyfrowy',
            'service' => 'Usługa',
            'bundle' => 'Pakiet',
        ];

        return $labels[$state] ?? Str::headline(str_replace(['-', '_'], ' ', $state));
    }

    public static function orderStatusColor(?string $state): string
    {
        return match ($state) {
            'placed', 'completed' => 'success',
            'shipped' => 'info',
            'cancelled' => 'danger',
            'draft' => 'gray',
            default => 'gray',
        };
    }

    public static function paymentStatusColor(?string $state): string
    {
        return match ($state) {
            'paid' => 'success',
            'pending', 'awaiting_payment', 'initiated', 'pending_gateway', 'pending_collection' => 'warning',
            'configuration_required', 'failed' => 'danger',
            default => 'gray',
        };
    }

    public static function fulfillmentStatusColor(?string $state): string
    {
        return match ($state) {
            'fulfilled' => 'success',
            'pending' => 'warning',
            default => 'gray',
        };
    }

    public static function fulfillmentActionColor(?string $state): string
    {
        return match ($state) {
            'physical_shipping' => 'info',
            'digital_delivery' => 'success',
            'service_followup' => 'warning',
            default => 'gray',
        };
    }
}

