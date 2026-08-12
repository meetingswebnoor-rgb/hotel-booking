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

    // The landing page's Live Demo section (App\Controllers\AuthController::demoLogin())
    // is entirely inert unless this is true — flip off in production once
    // real customers exist, or any time before that's true.
    'demo_mode' => (bool) env('DEMO_MODE', false),

    // Neither /register nor /contact exist yet — the landing page's
    // "List Your Hotel" and Contact links fall back to /login and a
    // mailto: respectively until these flip true and the real routes
    // land, at which point no template changes are needed.
    'register_route_enabled' => (bool) env('REGISTER_ENABLED', false),
    'contact_route_enabled' => (bool) env('CONTACT_PAGE_ENABLED', false),
    'contact_email' => env('MARKETING_CONTACT_EMAIL', 'hello@hotezo.com'),
];
