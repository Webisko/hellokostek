<?php

namespace App\Support;

class TaxHelper
{
    /**
     * Convert an amount in base currency (PLN) to the target currency based on store settings.
     *
     * @param int $amountInBaseCurrency (in cents/grosze)
     * @param string $targetCurrency
     * @return int (in cents/grosze of target currency)
     */
    public static function convertAmount(int $amountInBaseCurrency, string $targetCurrency): int
    {
        $targetCurrency = strtoupper(trim($targetCurrency));
        if ($targetCurrency === 'PLN') {
            return $amountInBaseCurrency;
        }

        $rates = app(\App\Support\StoreSettings::class)->exchangeRates();
        $rate = $rates[$targetCurrency] ?? null;

        if (!$rate) {
            // Fallback default rates if not configured
            $defaultRates = [
                'EUR' => 0.23,
                'GBP' => 0.20,
                'NOK' => 2.70,
                'USD' => 0.25,
            ];
            $rate = $defaultRates[$targetCurrency] ?? 1.0;
        }

        return (int) round($amountInBaseCurrency * $rate);
    }
}
