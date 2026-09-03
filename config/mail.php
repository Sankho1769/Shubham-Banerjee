<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'driver' => env('MAIL_DRIVER', 'log'),   // 'log' (dev-safe, writes to storage/logs) | 'smtp'
    'host' => env('MAIL_HOST', ''),
    'port' => (int) env('MAIL_PORT', 587),
    'username' => env('MAIL_USERNAME', ''),
    'password' => env('MAIL_PASSWORD', ''),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@boarding.app'),
    'from_name' => env('MAIL_FROM_NAME', 'BOARDING'),
];
