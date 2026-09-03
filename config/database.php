<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_NAME', 'boarding'),
    'username' => env('DB_USER', 'boarding_app'),   // least-privilege user; never root in production
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
];
