<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Middleware\SessionMiddleware;

SessionMiddleware::destroy();
header('Location: /login.php');
exit;
