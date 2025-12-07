<?php

return [

    'allowed_service_ids' => array_filter(
        array_map('trim', explode(',', env('INTERNAL_SERVICE_IDS', '')))
    ),

    'secret' => env('INTERNAL_SERVICE_SECRET'),

    'timestamp_tolerance' => env('INTERNAL_SERVICE_TIMESTAMP_TOLERANCE', 300),

];
