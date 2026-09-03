<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Helpers\ApiRequest;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\ReminderService;
use App\Validators\ReminderValidator;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

$userId = AuthMiddleware::requireApi();
ApiRequest::requireCsrf();

$data = ApiRequest::json();
$errors = ReminderValidator::validateCreate($data);
if ($errors) {
    Response::error('Please fix the highlighted fields.', 422, ['errors' => $errors]);
}

try {
    $reminder = (new ReminderService())->create($userId, $data);
} catch (\Throwable $e) {
    \App\Helpers\Logger::error('Reminder create failed', ['error' => $e->getMessage()]);
    Response::error('Could not create the reminder.', 500);
}

Response::success(['reminder' => $reminder], 201);
