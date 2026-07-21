<?php

namespace App\Domain\Commerce\Enums;

enum ProductType: string implements \Filament\Support\Contracts\HasLabel
{
    case Physical = 'physical';
    case Digital = 'digital';
    case Service = 'service';
    case Bundle = 'bundle';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Physical => 'Fizyczny',
            self::Digital => 'Cyfrowy',
            self::Service => 'Usługa',
            self::Bundle => 'Pakiet',
        };
    }
}