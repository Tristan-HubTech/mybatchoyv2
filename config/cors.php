<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(
        array_map(
            static fn (string $origin): string => rtrim(trim($origin), '/'),
            explode(',', (string) env('CORS_ALLOWED_ORIGINS', (string) env('APP_URL', 'http://localhost')))
        ),
        static fn (string $origin): bool => $origin !== '' && $origin !== '*'
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Idempotency-Key', 'X-Requested-With'],

    'exposed_headers' => ['X-Idempotent-Replayed', 'Content-Disposition'],

    'max_age' => 600,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
