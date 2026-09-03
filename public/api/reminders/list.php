<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\ReminderService;

$userId = AuthMiddleware::requireApi();

$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = max(1, min(100, (int) ($_GET['limit'] ?? 20)));

$filters = array_filter([
    'status' => $_GET['status'] ?? null,
    'category_id' => $_GET['category_id'] ?? null,
    'priority' => $_GET['priority'] ?? null,
    'search' => $_GET['search'] ?? null,
    'sort' => $_GET['sort'] ?? null,
]);

$reminders = (new ReminderService())->listForUser($userId, $page, $limit, $filters);

Response::success(['reminders' => $reminders, 'page' => $page, 'limit' => $limit]);
