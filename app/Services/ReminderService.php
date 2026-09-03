<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Repositories\ReminderRepository;

final class SystemCountdownProtectedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('The system countdown cannot be modified.');
    }
}

final class NotFoundException extends \RuntimeException
{
}

final class ForbiddenException extends \RuntimeException
{
}

final class ReminderService
{
    public function __construct(private ReminderRepository $reminders = new ReminderRepository())
    {
    }

    public function systemCountdown(): ?array
    {
        return $this->reminders->findSystemCountdown();
    }

    public function listForUser(string $userId, int $page, int $limit, array $filters): array
    {
        return $this->reminders->listForUser($userId, $page, $limit, $filters);
    }

    public function create(string $userId, array $data): array
    {
        return $this->reminders->create($userId, $data);
    }

    /**
     * Every write path funnels through this. It is the single place that
     * decides whether a mutation may proceed: is_system is checked FIRST,
     * unconditionally, before ownership is even considered — per spec §43.
     */
    private function loadAndGuard(string $reminderId, string $userId): array
    {
        $reminder = $this->reminders->findById($reminderId);

        if ($reminder === null) {
            throw new NotFoundException('Reminder not found.');
        }

        if ((int) $reminder['is_system'] === 1) {
            Logger::warning('Blocked mutation attempt on system countdown', [
                'reminder_id' => $reminderId,
                'user_id' => $userId,
            ]);
            throw new SystemCountdownProtectedException();
        }

        if ($reminder['user_id'] !== $userId) {
            throw new ForbiddenException('You do not have access to this reminder.');
        }

        return $reminder;
    }

    public function update(string $reminderId, string $userId, array $data): array
    {
        $this->loadAndGuard($reminderId, $userId);
        return $this->reminders->update($reminderId, $data);
    }

    public function trash(string $reminderId, string $userId): void
    {
        $this->loadAndGuard($reminderId, $userId);
        $this->reminders->trash($reminderId);
    }

    public function restore(string $reminderId, string $userId): void
    {
        $this->loadAndGuard($reminderId, $userId);
        $this->reminders->restore($reminderId);
    }

    public function forceDelete(string $reminderId, string $userId): void
    {
        $this->loadAndGuard($reminderId, $userId);
        $this->reminders->forceDelete($reminderId);
    }

    public function complete(string $reminderId, string $userId): void
    {
        $this->loadAndGuard($reminderId, $userId);
        $this->reminders->complete($reminderId);
    }

    public function duplicate(string $reminderId, string $userId): array
    {
        $this->loadAndGuard($reminderId, $userId);
        return $this->reminders->duplicate($reminderId, $userId);
    }
}
