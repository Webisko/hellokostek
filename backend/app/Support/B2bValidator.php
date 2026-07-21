<?php

namespace App\Support;

class B2bValidator
{
    public static function isValidPolishNip(string $nip): bool
    {
        // Remove non-digit characters (hyphens, spaces)
        $nip = preg_replace('/\D/', '', $nip);

        if (strlen($nip) !== 10) {
            return false;
        }

        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$nip[$i] * $weights[$i];
        }

        $control = $sum % 11;
        $controlDigit = $control === 10 ? 0 : $control;

        return $controlDigit === (int)$nip[9];
    }
}
