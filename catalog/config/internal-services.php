<?php

return [

    'allowed_service_ids' => array_filter(
        array_map('trim', explode(',', env('INTERNAL_SERVICE_IDS', '')))
    ),

    'secret' => env('INTERNAL_SERVICE_SECRET'),

    'timestamp_tolerance' => env('INTERNAL_SERVICE_TIMESTAMP_TOLERANCE', 300),

    'service_id' => env('INTERNAL_SERVICE_ID', 'catalog'),

    'services' => [
        'checkout' => [
            'base_url' => env('CHECKOUT_SERVICE_URL', 'http://checkout'),
        ],
        'email' => [
            'base_url' => env('EMAIL_SERVICE_URL', 'http://email'),
        ],
    ],

];
