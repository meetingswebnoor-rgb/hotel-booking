<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin wrapper around PHP sessions with a proper one-request flash
 * bucket: flash() data survives exactly the next request, whether or
 * not it's ever read.
 */
final class Session
{
    public static function start(): void
    {
        static $promoted = false;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $lifetimeMinutes = (int) env('SESSION_LIFETIME', 120);

            // Hardened cookie flags + strict mode (rejects session IDs the
            // server never generated, closing session-fixation via a
            // planted cookie) must be set before session_start().
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');

            session_name((string) env('SESSION_NAME', 'hotezo_session'));
            session_set_cookie_params([
                'lifetime' => $lifetimeMinutes * 60,
                'path' => '/',
                'domain' => '',
                'secure' => Request::isSecureServer(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        if (!$promoted) {
            $_SESSION['_flash'] = $_SESSION['_flash_next'] ?? [];
            $_SESSION['_flash_next'] = [];
            $promoted = true;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash_next'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash'][$key] ?? $default;
    }

    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
