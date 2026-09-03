<?php

declare(strict_types=1);

namespace App\Middleware;

final class SessionMiddleware
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $session = $appConfig['session'];

        session_name($session['name']);
        session_set_cookie_params([
            'lifetime' => $session['lifetime_minutes'] * 60,
            'path' => '/',
            'domain' => '',
            'secure' => $session['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /** Call immediately after successful login to prevent session fixation. */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
