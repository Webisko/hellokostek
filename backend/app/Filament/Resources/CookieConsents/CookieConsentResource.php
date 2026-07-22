<?php

namespace App\Filament\Resources\CookieConsents;

use App\Traits\HasDynamicNavigation;

use App\Filament\Resources\CookieConsents\Pages\ListCookieConsents;
use App\Filament\Resources\CookieConsents\Pages\ViewCookieConsent;
use App\Filament\Resources\CookieConsents\Schemas\CookieConsentInfolist;
use App\Filament\Resources\CookieConsents\Tables\CookieConsentsTable;
use App\Models\CookieConsent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CookieConsentResource extends Resource
{
    protected static ?string $slug = 'zgody-na-ciasteczka';
    use HasDynamicNavigation;
    protected static ?string $model = CookieConsent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $modelLabel = 'log zgody cookies';

    protected static ?string $pluralModelLabel = 'zgody na cookies';

    protected static ?string $navigationLabel = 'Zgody na cookies';

    protected static \UnitEnum|string|null $navigationGroup = 'System & ustawienia';

    protected static ?int $navigationSort = 5;

    public static function infolist(Schema $schema): Schema
    {
        return CookieConsentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CookieConsentsTable::configure($table);
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
            'index' => ListCookieConsents::route('/'),
        ];
    }
}

