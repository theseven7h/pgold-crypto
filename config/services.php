<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'coingecko' => [
        'url' => env('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3'),
        'cache_ttl' => env('COINGECKO_CACHE_TTL', 120), // 2 minutes in seconds
        'timeout' => env('COINGECKO_TIMEOUT', 10), // Request timeout in seconds
        'retry_times' => env('COINGECKO_RETRY_TIMES', 3),
        'retry_sleep' => env('COINGECKO_RETRY_SLEEP', 1000), // Milliseconds
    ],
];
