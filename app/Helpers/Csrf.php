<?php

declare(strict_types=1);

namespace App\Helpers;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verify(?string $submitted): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($submitted)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $submitted);
    }

    /** Reads the token from JSON body, form POST, or the X-CSRF-Token header. */
    public static function fromRequest(array $jsonBody = []): ?string
    {
        return $jsonBody['csrf_token']
            ?? $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
    }
}
