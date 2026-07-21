<?php

namespace App\Support;

class VatOssHelper
{
    private static array $euVatRates = [
        'AT' => 20, // Austria
        'BE' => 21, // Belgium
        'BG' => 20, // Bulgaria
        'HR' => 25, // Croatia
        'CY' => 19, // Cyprus
        'CZ' => 21, // Czech Republic
        'DK' => 25, // Denmark
        'EE' => 22, // Estonia
        'FI' => 24, // Finland
        'FR' => 20, // France
        'DE' => 19, // Germany
        'GR' => 24, // Greece
        'HU' => 27, // Hungary
        'IE' => 23, // Ireland
        'IT' => 22, // Italy
        'LV' => 21, // Latvia
        'LT' => 21, // Lithuania
        'LU' => 17, // Luxembourg
        'MT' => 18, // Malta
        'NL' => 21, // Netherlands
        'PT' => 23, // Portugal
        'RO' => 19, // Romania
        'SK' => 20, // Slovakia
        'SI' => 22, // Slovenia
        'ES' => 21, // Spain
        'SE' => 25, // Sweden
    ];

    /**
     * Resolve the country code from billing or shipping address.
     */
    public static function resolveCountryCode(?array $address): string
    {
        if (!$address) {
            return 'PL';
        }

        $country = strtoupper(trim($address['country_code'] ?? $address['country'] ?? 'PL'));

        if (in_array($country, ['POLSKA', 'POL', 'PL'])) {
            return 'PL';
        }

        return $country;
    }

    /**
     * Determine if a country code is subject to EU VAT OSS (EU countries except Poland).
     */
    public static function isEuCountryOtherThanPoland(string $countryCode): bool
    {
        $countryCode = strtoupper(trim($countryCode));
        return $countryCode !== 'PL' && array_key_exists($countryCode, self::$euVatRates);
    }

    /**
     * Get the standard VAT rate for the given EU country.
     */
    public static function getVatRateForCountry(string $countryCode, int $defaultRate = 23): int
    {
        $countryCode = strtoupper(trim($countryCode));
        return self::$euVatRates[$countryCode] ?? $defaultRate;
    }
}
