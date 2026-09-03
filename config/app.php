<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'name' => env('APP_NAME', 'BOARDING'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost/boarding/public'),
    'key' => env('APP_KEY', ''),                     // used for CSRF token HMAC + misc signing
    'timezone_default' => env('APP_TIMEZONE', 'Asia/Kolkata'),

    'session' => [
        'name' => 'boarding_session',
        'lifetime_minutes' => 60 * 24 * 14,           // 14 days
        'secure' => (bool) env('SESSION_SECURE', false), // set true once served over HTTPS
    ],

    'uploads' => [
        'wallpaper_dir' => dirname(__DIR__) . '/storage/uploads/wallpapers',
        'profile_dir' => dirname(__DIR__) . '/storage/uploads/profiles',
        'max_bytes' => 8 * 1024 * 1024,               // 8 MB
        'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_dimension' => 6000,                       // px, guards against decompression bombs
    ],

    'rate_limits' => [
        // [max attempts, window in seconds]
        'login' => [5, 60],
        'register' => [5, 3600],
        'password_reset' => [3, 3600],
        'share_view' => [60, 60],
    ],

    'trash_retention_days' => 30,
];
