<?php

return [

    'allowed_service_ids' => array_filter(
        array_map('trim', explode(',', env('INTERNAL_SERVICE_IDS', 'checkout,email')))
    ),

    'secret' => env('INTERNAL_SERVICE_SECRET', 'cinch'),

    'timestamp_tolerance' => (int) env('INTERNAL_SERVICE_TIMESTAMP_TOLERANCE', 300),

    'service_id' => env('INTERNAL_SERVICE_ID', 'catalog'),

    'services' => [
        'checkout' => [
            'base_url' => env('CHECKOUT_SERVICE_URL', 'http://checkout:8000'),
        ],
        'email' => [
            'base_url' => env('EMAIL_SERVICE_URL', 'http://email:8000'),
        ],
    ],

];
