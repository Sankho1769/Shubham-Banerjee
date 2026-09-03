<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Helpers\Uuid;
use PDO;

final class ReminderRepository
{
    /** The system countdown, visible to every user regardless of login. */
    public function findSystemCountdown(): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM reminders WHERE is_system = 1 LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(string $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM reminders WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Active (non-trashed) reminders owned by a user, most-imminent first, paginated. */
    public function listForUser(string $userId, int $page = 1, int $limit = 20, array $filters = []): array
    {
        $pdo = Database::connection();
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;

        $where = ['user_id = :user_id', 'deleted_at IS NULL'];
        $params = [':user_id' => $userId];

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'category_id = :category_id';
            $params[':category_id'] = $filters['category_id'];
        }
        if (!empty($filters['priority'])) {
            $where[] = 'priority = :priority';
            $params[':priority'] = $filters['priority'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(title LIKE :search OR description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sort = match ($filters['sort'] ?? 'nearest') {
            'furthest' => 'target_datetime DESC',
            'recent_created' => 'created_at DESC',
            'recent_updated' => 'updated_at DESC',
            'alphabetical' => 'title ASC',
            'priority' => "FIELD(priority,'critical','high','normal','low') ASC",
            default => 'target_datetime ASC',
        };

        $sql = 'SELECT * FROM reminders WHERE ' . implode(' AND ', $where)
            . " ORDER BY $sort LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create(string $userId, array $data): array
    {
        $id = Uuid::v4();
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'INSERT INTO reminders (
                id, user_id, category_id, title, description, target_datetime, timezone,
                display_format, priority, design, text_color, background_color, accent_color,
                font, completion_message, notify_enabled, recurrence_type, recurrence_rule,
                recurrence_start, recurrence_end, is_system
            ) VALUES (
                :id, :user_id, :category_id, :title, :description, :target_datetime, :timezone,
                :display_format, :priority, :design, :text_color, :background_color, :accent_color,
                :font, :completion_message, :notify_enabled, :recurrence_type, :recurrence_rule,
                :recurrence_start, :recurrence_end, 0
            )'
        );

        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':category_id' => $data['category_id'] ?? null,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':target_datetime' => $data['target_datetime'],
            ':timezone' => $data['timezone'] ?? 'Asia/Kolkata',
            ':display_format' => $data['display_format'] ?? 'dhms',
            ':priority' => $data['priority'] ?? 'normal',
            ':design' => $data['design'] ?? 'classic',
            ':text_color' => $data['text_color'] ?? null,
            ':background_color' => $data['background_color'] ?? null,
            ':accent_color' => $data['accent_color'] ?? null,
            ':font' => $data['font'] ?? null,
            ':completion_message' => $data['completion_message'] ?? null,
            ':notify_enabled' => isset($data['notify_enabled']) ? (int) $data['notify_enabled'] : 1,
            ':recurrence_type' => $data['recurrence_type'] ?? 'none',
            ':recurrence_rule' => $data['recurrence_rule'] ?? null,
            ':recurrence_start' => $data['recurrence_start'] ?? null,
            ':recurrence_end' => $data['recurrence_end'] ?? null,
        ]);

        return $this->findById($id);
    }

    /** Raw update — callers MUST check is_system before calling this. */
    public function update(string $id, array $data): array
    {
        $allowed = [
            'category_id', 'title', 'description', 'target_datetime', 'timezone',
            'display_format', 'priority', 'design', 'text_color', 'background_color',
            'accent_color', 'font', 'completion_message', 'notify_enabled',
            'recurrence_type', 'recurrence_rule', 'recurrence_start', 'recurrence_end',
            'wallpaper_id', 'wallpaper_fit', 'wallpaper_position', 'wallpaper_brightness',
            'wallpaper_opacity', 'wallpaper_blur', 'wallpaper_overlay',
        ];

        $set = [];
        $params = [':id' => $id];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($set)) {
            return $this->findById($id);
        }

        $sql = 'UPDATE reminders SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    /** Soft delete (trash). Callers MUST check is_system before calling this. */
    public function trash(string $id): void
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $days = $config['trash_retention_days'];

        $stmt = Database::connection()->prepare(
            'UPDATE reminders SET deleted_at = NOW(), purge_at = (NOW() + INTERVAL :days DAY) WHERE id = :id'
        );
        $stmt->execute([':id' => $id, ':days' => $days]);
    }

    public function restore(string $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE reminders SET deleted_at = NULL, purge_at = NULL WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    /** Hard delete. Callers MUST check is_system before calling this. */
    public function forceDelete(string $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM reminders WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function complete(string $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE reminders SET status = 'completed', completed_at = NOW() WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    public function duplicate(string $id, string $userId): array
    {
        $original = $this->findById($id);
        if (!$original) {
            throw new \RuntimeException('Reminder not found.');
        }

        $newId = Uuid::v4();
        $stmt = Database::connection()->prepare(
            'INSERT INTO reminders (
                id, user_id, category_id, wallpaper_id, title, description, target_datetime, timezone,
                display_format, priority, design, text_color, background_color, accent_color, font,
                wallpaper_fit, wallpaper_position, wallpaper_brightness, wallpaper_opacity, wallpaper_blur,
                wallpaper_overlay, completion_message, notify_enabled, recurrence_type, recurrence_rule,
                recurrence_start, recurrence_end, is_system
            ) SELECT
                :new_id, :user_id, category_id, wallpaper_id, CONCAT(title, \' (Copy)\'), description,
                target_datetime, timezone, display_format, priority, design, text_color, background_color,
                accent_color, font, wallpaper_fit, wallpaper_position, wallpaper_brightness, wallpaper_opacity,
                wallpaper_blur, wallpaper_overlay, completion_message, notify_enabled, recurrence_type,
                recurrence_rule, recurrence_start, recurrence_end, 0
            FROM reminders WHERE id = :source_id'
        );
        $stmt->execute([':new_id' => $newId, ':user_id' => $userId, ':source_id' => $id]);

        return $this->findById($newId);
    }

    public function isOwnedBy(string $reminderId, string $userId): bool
    {
        $reminder = $this->findById($reminderId);
        return $reminder !== null && $reminder['user_id'] === $userId;
    }
}
