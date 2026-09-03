<?php

declare(strict_types=1);

namespace App\Helpers;

final class ApiRequest
{
    private static ?array $jsonCache = null;

    public static function json(): array
    {
        if (self::$jsonCache !== null) {
            return self::$jsonCache;
        }
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '[]', true);
        self::$jsonCache = is_array($decoded) ? $decoded : [];
        return self::$jsonCache;
    }

    /** Enforces CSRF for every state-changing request. Call at the top of any POST endpoint. */
    public static function requireCsrf(): void
    {
        $body = self::json();
        $token = Csrf::fromRequest($body);
        if (!Csrf::verify($token)) {
            Response::error('Invalid or missing CSRF token.', 419);
        }
    }
}
