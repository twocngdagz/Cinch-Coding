<?php

return [

    'allowed_service_ids' => array_filter(
        array_map('trim', explode(',', env('INTERNAL_SERVICE_IDS', '')))
    ),

    'secret' => env('INTERNAL_SERVICE_SECRET'),

    'timestamp_tolerance' => env('INTERNAL_SERVICE_TIMESTAMP_TOLERANCE', 300),

    'service_id' => env('INTERNAL_SERVICE_ID', 'email'),

    'services' => [
        'catalog' => [
            'base_url' => env('CATALOG_SERVICE_URL', 'http://catalog'),
        ],
        'checkout' => [
            'base_url' => env('CHECKOUT_SERVICE_URL', 'http://checkout'),
        ],
    ],

];
