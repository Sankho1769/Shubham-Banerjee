<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/autoload.php';

use App\Middleware\SessionMiddleware;

$appConfig = require __DIR__ . '/../config/app.php';
date_default_timezone_set('UTC'); // server/internal calculations always in UTC

error_reporting(E_ALL);
ini_set('display_errors', $appConfig['debug'] ? '1' : '0');
ini_set('log_errors', '1');

SessionMiddleware::start();
