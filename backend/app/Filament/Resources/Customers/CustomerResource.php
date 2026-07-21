<?php

namespace App\Filament\Resources\Customers;

use App\Traits\HasDynamicNavigation;

use App\Domain\Commerce\Enums\CustomerSegment;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Customers\Schemas\CustomerInfolist;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    use HasDynamicNavigation;
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'klient';

    protected static ?string $pluralModelLabel = 'klienci';

    protected static ?string $navigationLabel = 'Klienci';

    protected static ?string $slug = 'klienci';

    protected static \UnitEnum|string|null $navigationGroup = 'Sprzedaż & Zapytania';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
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
            ->where('is_admin', false)
            ->with('customerProfile')
            ->withCount('orders')
            ->withSum(['orders as orders_sum_total_amount' => fn (Builder $query) => $query->where('payment_status', 'paid')], 'total_amount');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function segmentOptions(): array
    {
        return collect(CustomerSegment::cases())
            ->mapWithKeys(fn (CustomerSegment $segment): array => [$segment->value => static::formatStateLabel($segment)])
            ->all();
    }

    public static function formatStateLabel(mixed $state): string
    {
        if (blank($state)) {
            return '-';
        }

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        $labels = [
            'regular' => 'Standardowy',
            'staly_klient_5' => 'Stały klient (5+)',
            'staly_klient_8' => 'Stały klient (8+)',
            'hurt_30' => 'Hurtownik (30%)',
        ];

        return $labels[$state] ?? Str::headline(str_replace(['-', '_'], ' ', (string) $state));
    }

    public static function segmentColor(mixed $state): string
    {
        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        return match ($state) {
            CustomerSegment::Regular->value => 'gray',
            CustomerSegment::LoyalFive->value => 'info',
            CustomerSegment::LoyalEight->value => 'success',
            CustomerSegment::WholesaleThirty->value => 'warning',
            default => 'gray',
        };
    }
}
