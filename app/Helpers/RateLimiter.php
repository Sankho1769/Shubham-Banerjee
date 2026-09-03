<?php

declare(strict_types=1);

namespace App\Helpers;

final class RateLimiter
{
    /**
     * Returns true if the action is currently allowed, false if the caller
     * has exceeded maxAttempts within windowSeconds. Records this attempt
     * regardless (so failed logins still count toward the limit).
     */
    public static function attempt(string $bucketKey, int $maxAttempts, int $windowSeconds): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM rate_limit_attempts
             WHERE bucket_key = :key AND created_at >= (NOW() - INTERVAL :window SECOND)'
        );
        $stmt->execute([':key' => $bucketKey, ':window' => $windowSeconds]);
        $count = (int) $stmt->fetchColumn();

        if ($count >= $maxAttempts) {
            return false;
        }

        $insert = $pdo->prepare('INSERT INTO rate_limit_attempts (bucket_key) VALUES (:key)');
        $insert->execute([':key' => $bucketKey]);

        return true;
    }

    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
