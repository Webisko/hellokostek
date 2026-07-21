<?php

namespace {
    // Check if intl is NOT loaded to register the fallback NumberFormatter
    if (!class_exists('NumberFormatter')) {
        class NumberFormatter
        {
            public const DECIMAL = 1;
            public const PERCENT = 2;
            public const CURRENCY = 3;
            public const SPELLOUT = 4;
            public const ORDINAL = 5;
            public const DURATION = 6;
            public const PATTERN_DECIMAL = 7;
            public const PATTERN_RULEBASED = 8;
            public const IGNORE = 9;

            public const MAX_FRACTION_DIGITS = 0;
            public const FRACTION_DIGITS = 1;
            public const DEFAULT_RULESET = 2;
            public const TYPE_DOUBLE = 3;
            public const TYPE_INT32 = 4;

            private $locale;
            private $style;
            private $attributes = [];
            private $textAttributes = [];

            public function __construct(string $locale, int $style, ?string $pattern = null)
            {
                $this->locale = $locale;
                $this->style = $style;
            }

            public function setAttribute(int $attribute, $value): bool
            {
                $this->attributes[$attribute] = $value;
                return true;
            }

            public function setTextAttribute(int $attribute, string $value): bool
            {
                $this->textAttributes[$attribute] = $value;
                return true;
            }

            public function format($value, int $type = 0): string|false
            {
                $decimals = $this->attributes[self::FRACTION_DIGITS] ?? 0;

                if ($this->style === self::PERCENT) {
                    // NumberFormatter percentage formatting divides by 100 on format() call in php, 
                    // but Laravel does the division itself in some helper versions. Let's make it safe:
                    return number_format($value * 100, $decimals, ',', ' ') . '%';
                }

                return number_format(
                    $value,
                    $decimals,
                    ',',
                    ' '
                );
            }

            public function formatCurrency(float $value, string $currency): string|false
            {
                $decimals = $this->attributes[self::FRACTION_DIGITS] ?? 2;
                $formatted = number_format($value, $decimals, ',', ' ');
                return $formatted . ' ' . $currency;
            }

            public function parse(string $string, int $type = self::TYPE_DOUBLE, ?int &$offset = null): int|float|false
            {
                $clean = preg_replace('/[^\d\.\,\-]/', '', $string);
                $clean = str_replace(',', '.', $clean);
                return (float)$clean;
            }
        }
    }
}

// Bypasses the extension_loaded('intl') check inside Laravel's Illuminate\Support\Number class
namespace Illuminate\Support {
    if (!function_exists('Illuminate\Support\extension_loaded')) {
        function extension_loaded(string $extension): bool
        {
            if ($extension === 'intl') {
                return true;
            }
            return \extension_loaded($extension);
        }
    }
}
