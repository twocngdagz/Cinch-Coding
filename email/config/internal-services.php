<?php

return [

    'allowed_service_ids' => array_filter(
        array_map('trim', explode(',', env('INTERNAL_SERVICE_IDS', 'checkout,catalog')))
    ),

    'secret' => env('INTERNAL_SERVICE_SECRET', 'cinch'),

    'timestamp_tolerance' => (int) env('INTERNAL_SERVICE_TIMESTAMP_TOLERANCE', 300),

    'service_id' => env('INTERNAL_SERVICE_ID', 'email'),

    'services' => [
        'catalog' => [
            'base_url' => env('CATALOG_SERVICE_URL', 'http://catalog:8000'),
        ],
        'checkout' => [
            'base_url' => env('CHECKOUT_SERVICE_URL', 'http://checkout:8000'),
        ],
    ],

];
