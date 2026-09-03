<?php

declare(strict_types=1);

namespace App\Helpers;

final class Response
{
    public static function json(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(array $data = [], int $statusCode = 200): never
    {
        self::json(['success' => true, 'data' => $data], $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, array $extra = []): never
    {
        self::json(['success' => false, 'message' => $message] + $extra, $statusCode);
    }

    /** 403 specifically for attempts to mutate the protected system countdown. */
    public static function systemProtected(): never
    {
        self::error('The system countdown cannot be modified.', 403);
    }
}
