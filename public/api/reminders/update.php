<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Helpers\ApiRequest;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\ForbiddenException;
use App\Services\NotFoundException;
use App\Services\ReminderService;
use App\Services\SystemCountdownProtectedException;
use App\Validators\ReminderValidator;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

$userId = AuthMiddleware::requireApi();
ApiRequest::requireCsrf();

$data = ApiRequest::json();
$id = $data['id'] ?? '';
if (!$id) {
    Response::error('Reminder id is required.', 422);
}

try {
    $reminder = (new ReminderService())->update($id, $userId, $data);
    Response::success(['reminder' => $reminder]);
} catch (SystemCountdownProtectedException $e) {
    Response::systemProtected();
} catch (NotFoundException $e) {
    Response::error('Reminder not found.', 404);
} catch (ForbiddenException $e) {
    Response::error('You do not have access to this reminder.', 403);
} catch (\Throwable $e) {
    \App\Helpers\Logger::error('Reminder update failed', ['error' => $e->getMessage()]);
    Response::error('Could not update the reminder.', 500);
}
