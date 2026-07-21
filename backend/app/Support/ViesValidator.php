<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ViesValidator
{
    private const API_URL = 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number';

    /**
     * Regex patterns for validation of VAT numbers syntax in EU member states.
     */
    private static array $vatRegexPatterns = [
        'AT' => '/^U[0-9]{8}$/',                           // Austria
        'BE' => '/^(0|1)[0-9]{9}$/',                       // Belgium
        'BG' => '/^[0-9]{9,10}$/',                         // Bulgaria
        'CY' => '/^[0-9]{8}[A-Z]$/',                       // Cyprus
        'CZ' => '/^[0-9]{8,10}$/',                         // Czech Republic
        'DE' => '/^[0-9]{9}$/',                            // Germany
        'DK' => '/^[0-9]{8}$/',                            // Denmark
        'EE' => '/^[0-9]{9}$/',                            // Estonia
        'EL' => '/^[0-9]{9}$/',                            // Greece (EL)
        'GR' => '/^[0-9]{9}$/',                            // Greece (GR fallback)
        'ES' => '/^[A-Z0-9][0-9]{7}[A-Z0-9]$/',           // Spain
        'FI' => '/^[0-9]{8}$/',                            // Finland
        'FR' => '/^[A-Z0-9]{2}[0-9]{9}$/',                 // France
        'HR' => '/^[0-9]{11}$/',                           // Croatia
        'HU' => '/^[0-9]{8}$/',                            // Hungary
        'IE' => '/^[0-9]{7}[A-W][A-I]?$/',                 // Ireland
        'IT' => '/^[0-9]{11}$/',                           // Italy
        'LT' => '/^([0-9]{9}|[0-9]{12})$/',                // Lithuania
        'LU' => '/^[0-9]{8}$/',                            // Luxembourg
        'LV' => '/^[0-9]{11}$/',                           // Latvia
        'MT' => '/^[0-9]{8}$/',                            // Malta
        'NL' => '/^[0-9]{9}B[0-9]{2}$/',                   // Netherlands
        'PL' => '/^[0-9]{10}$/',                           // Poland
        'PT' => '/^[0-9]{9}$/',                            // Portugal
        'RO' => '/^[0-9]{2,10}$/',                         // Romania
        'SE' => '/^[0-9]{12}$/',                           // Sweden
        'SI' => '/^[0-9]{8}$/',                            // Slovenia
        'SK' => '/^[0-9]{10}$/',                           // Slovakia
    ];

    public function validate(string $vatNumber, string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));
        // Normalize: uppercase, remove non-alphanumeric characters
        $normalizedVat = preg_replace('/[^A-Z0-9]/', '', strtoupper($vatNumber));

        // If the normalized number starts with the country code, strip it
        if (str_starts_with($normalizedVat, $countryCode)) {
            $vatNumberClean = substr($normalizedVat, strlen($countryCode));
        } else {
            $vatNumberClean = $normalizedVat;
        }

        // Validate syntax offline
        $pattern = self::$vatRegexPatterns[$countryCode] ?? null;
        if ($pattern && !preg_match($pattern, $vatNumberClean)) {
            return [
                'isValid' => false,
                'status' => 'invalid_syntax',
                'message' => 'Niepoprawny format numeru VAT dla kraju ' . $countryCode,
                'traderName' => null,
                'traderAddress' => null,
            ];
        }

        // Check if online VIES check is enabled in settings
        $settings = app(StoreSettings::class);
        $viesEnabled = (bool) ($settings->model()->metadata['vies_enabled'] ?? true);
        if (!$viesEnabled) {
            return [
                'isValid' => true,
                'status' => 'vies_disabled',
                'message' => 'Walidacja VIES wyłączona - poprawna składnia.',
                'traderName' => null,
                'traderAddress' => null,
            ];
        }

        try {
            $response = Http::timeout(5)
                ->post(self::API_URL, [
                    'countryCode' => $countryCode === 'GR' ? 'EL' : $countryCode, // VIES uses EL for Greece
                    'vatNumber' => $vatNumberClean,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $isValid = (bool) ($data['valid'] ?? false);

                return [
                    'isValid' => $isValid,
                    'status' => $isValid ? 'valid' : 'invalid',
                    'message' => $isValid ? 'Aktywny numer VAT w systemie VIES.' : 'Nieaktywny numer VAT w systemie VIES.',
                    'traderName' => $data['name'] ?? null,
                    'traderAddress' => $data['address'] ?? null,
                ];
            }

            Log::warning('VIES validation API request failed: ' . $response->status());
        } catch (\Throwable $e) {
            Log::error('VIES validation error: ' . $e->getMessage());
        }

        // Fallback if VIES API is down/timeout
        $viesStrict = (bool) ($settings->model()->metadata['vies_strict_mode'] ?? false);
        if ($viesStrict) {
            return [
                'isValid' => false,
                'status' => 'vies_down_strict',
                'message' => 'Serwer walidacji VIES jest niedostępny (tryb restrykcyjny).',
                'traderName' => null,
                'traderAddress' => null,
            ];
        }

        // If not strict mode, allow checkouts for valid syntax if VIES is down
        return [
            'isValid' => true,
            'status' => 'vies_down_fallback',
            'message' => 'Serwer VIES niedostępny - numer zaakceptowany na podstawie składni.',
            'traderName' => null,
            'traderAddress' => null,
        ];
    }
}
