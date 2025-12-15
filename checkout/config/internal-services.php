<?php

return [

    'allowed_service_ids' => array_filter(
        array_map('trim', explode(',', env('INTERNAL_SERVICE_IDS', 'mail,catalog')))
    ),

    'secret' => env('INTERNAL_SERVICE_SECRET', 'cinch'),

    'timestamp_tolerance' => (int) env('INTERNAL_SERVICE_TIMESTAMP_TOLERANCE', 300),

    'service_id' => env('INTERNAL_SERVICE_ID', 'checkout'),

    'services' => [
        'catalog' => [
            'base_url' => env('CATALOG_SERVICE_URL', 'http://catalog:8000'),
        ],
        'email' => [
            'base_url' => env('EMAIL_SERVICE_URL', 'http://email:8000'),
        ],
    ],

];
