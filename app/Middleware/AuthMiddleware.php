<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;

final class AuthMiddleware
{
    public static function userId(): ?string
    {
        return $_SESSION['user_id'] ?? null;
    }

    /** For page requests: redirect to login instead of a JSON error. */
    public static function requireWeb(): string
    {
        $userId = self::userId();
        if ($userId === null) {
            header('Location: /login.php');
            exit;
        }
        return $userId;
    }

    /** For API requests: JSON 401 instead of a redirect. */
    public static function requireApi(): string
    {
        $userId = self::userId();
        if ($userId === null) {
            Response::error('Authentication required.', 401);
        }
        return $userId;
    }
}
