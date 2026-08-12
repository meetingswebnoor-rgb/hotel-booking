<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Hotezo'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),
    'key' => env('APP_KEY', ''),
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),
    'locale' => 'en_IN',
    'currency' => 'INR',

    // Redirect HTTP -> HTTPS. Defaults on for every env except local
    // (dev servers rarely have TLS) — set APP_FORCE_HTTPS explicitly if
    // a host's SSL cert isn't provisioned yet and this would lock out
    // access, or to force it on locally against an HTTPS dev proxy.
    'force_https' => (bool) env('APP_FORCE_HTTPS', env('APP_ENV', 'production') !== 'local'),
];
