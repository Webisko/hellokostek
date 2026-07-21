<?php

return [
    'store' => [
        'name' => env('APP_NAME', 'Generic Shop'),
        'currency' => 'PLN',
        'free_shipping_threshold' => 25000, // 250.00 PLN
    ],

    'product_types' => [
        'physical',
        'digital',
        'service',
    ],

    'customer_segments' => [
        'regular',
        'loyal_5',
        'loyal_8',
        'wholesale_30',
    ],

    'loyalty' => [
        'tiers' => [
            [
                'segment' => 'loyal_5',
                'completed_orders' => 3,
                'discount_percent' => 5,
            ],
            [
                'segment' => 'loyal_8',
                'completed_orders' => 6,
                'discount_percent' => 8,
            ],
        ],
    ],

    'wholesale' => [
        'segment' => 'wholesale_30',
        'minimum_regular_price_multiplier' => 0.70,
    ],

    'shipping' => [
        'cod_only_method' => 'flat_rate:cod',
        'primary_methods' => [
            'flexible_shipping:paczkomat',
            'flexible_shipping:orlen',
            'flexible_shipping:pickup',
            'flat_rate:courier',
            'flat_rate:cod',
        ],
        'methods' => [
            'flexible_shipping:paczkomat' => [
                'name' => 'Paczkomat',
                'amount' => 1599,
                'supports_cod' => false,
                'requires_delivery_point' => true,
            ],
            'flexible_shipping:orlen' => [
                'name' => 'ORLEN Paczka',
                'amount' => 1199,
                'supports_cod' => false,
                'requires_delivery_point' => true,
            ],
            'flexible_shipping:pickup' => [
                'name' => 'Odbiór w punkcie',
                'amount' => 1350,
                'supports_cod' => false,
                'requires_delivery_point' => true,
            ],
            'flat_rate:courier' => [
                'name' => 'Kurier',
                'amount' => 1750,
                'supports_cod' => false,
            ],
            'flat_rate:cod' => [
                'name' => 'Kurier - pobranie',
                'amount' => 1999,
                'supports_cod' => true,
                'requires_delivery_point' => false,
            ],
        ],
    ],

    'integrations' => [
        'przelewy24' => [
            'enabled' => env('PRZELEWY24_ENABLED', false),
        ],
        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
        ],
        'newsletter' => [
            'mode' => 'local_lead_storage',
        ],
        'reviews' => [
            'enabled' => true,
            'primary_source' => 'google',
            'sources' => ['google'],
            'google' => [
                'business_name' => env('GOOGLE_PLACES_BUSINESS_NAME', 'Generic Shop'),
                'place_id' => env('GOOGLE_PLACES_PLACE_ID', null),
            ],
        ],
    ],

    'abandoned_cart' => [
        'enabled' => env('ABANDONED_CART_ENABLED', true),
        'hours_threshold' => (int) env('ABANDONED_CART_HOURS_THRESHOLD', 2),
    ],

    'seo' => [
        'track_redirect_hits' => (bool) env('TRACK_REDIRECT_HITS', true),
    ],
];
