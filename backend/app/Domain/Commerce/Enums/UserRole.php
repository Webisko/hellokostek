<?php

namespace App\Domain\Commerce\Enums;

enum UserRole: string implements \Filament\Support\Contracts\HasLabel
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Employee = 'employee';
    case Customer = 'customer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Menedżer',
            self::Employee => 'Pracownik',
            self::Customer => 'Klient',
        };
    }
}
