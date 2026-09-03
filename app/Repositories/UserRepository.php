<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Helpers\Uuid;
use PDO;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(string $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, string $email, string $passwordHash): array
    {
        $id = Uuid::v4();
        $pdo = Database::connection();

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (id, name, email, password_hash) VALUES (:id, :name, :email, :hash)'
            );
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':email' => $email,
                ':hash' => $passwordHash,
            ]);

            $prefStmt = $pdo->prepare(
                'INSERT INTO notification_preferences (user_id) VALUES (:id)'
            );
            $prefStmt->execute([':id' => $id]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $this->findById($id);
    }

    public function recordFailedLogin(string $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET failed_login_count = failed_login_count + 1 WHERE id = :id'
        );
        $stmt->execute([':id' => $userId]);
    }

    public function resetFailedLogin(string $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = :id'
        );
        $stmt->execute([':id' => $userId]);
    }
}
