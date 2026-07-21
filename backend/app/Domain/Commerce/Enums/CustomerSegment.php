<?php

namespace App\Domain\Commerce\Enums;

enum CustomerSegment: string implements \Filament\Support\Contracts\HasLabel
{
    case Regular = 'regular';
    case LoyalFive = 'staly_klient_5';
    case LoyalEight = 'staly_klient_8';
    case WholesaleThirty = 'hurt_30';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Regular => 'Standardowy',
            self::LoyalFive => 'Stały klient (5+)',
            self::LoyalEight => 'Stały klient (8+)',
            self::WholesaleThirty => 'Hurtownik (30%)',
        };
    }
}