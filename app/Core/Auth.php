<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session-based auth against the `users` table. Role name/level are
 * cached in the session at login time (avoids a join on every
 * request) — if a role's level changes mid-session, that user won't
 * see it reflected until they log in again, same tradeoff most
 * session-based auth systems make.
 */
final class Auth
{
    public const REMEMBER_COOKIE = 'hotezo_remember';

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('auth_user_id', $user['id']);

        $role = Database::first('roles', ['id' => $user['role_id']]);
        Session::set('auth_role_name', $role['name'] ?? null);
        Session::set('auth_role_level', (int) ($role['level'] ?? 0));
    }

    public static function logout(): void
    {
        Session::remove('auth_user_id');
        Session::remove('auth_role_name');
        Session::remove('auth_role_level');
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::has('auth_user_id');
    }

    public static function id(): int|string|null
    {
        return Session::get('auth_user_id');
    }

    public static function roleName(): ?string
    {
        return Session::get('auth_role_name');
    }

    public static function hasRole(string ...$names): bool
    {
        $role = self::roleName();

        return $role !== null && in_array($role, $names, true);
    }

    public static function level(): int
    {
        return (int) Session::get('auth_role_level', 0);
    }

    public static function hasMinLevel(int $level): bool
    {
        return self::check() && self::level() >= $level;
    }

    /**
     * Admin and Super Admin manage/see every hotel; everyone else is
     * restricted to the hotels in user_hotels (see hotelIds()).
     */
    public static function hasGlobalHotelAccess(): bool
    {
        return self::level() >= RoleLevel::ADMIN;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        static $cached = null;

        if (!self::check()) {
            return null;
        }

        if ($cached !== null) {
            return $cached;
        }

        return $cached = Database::first('users', ['id' => self::id(), 'is_deleted' => 0]);
    }

    /**
     * The hotel IDs this user is assigned to, regardless of whether
     * their level grants them global access — HotelScopeMiddleware and
     * Permission decide when this list actually applies.
     *
     * @return array<int, string>
     */
    public static function hotelIds(): array
    {
        static $cached = null;

        if (!self::check()) {
            return [];
        }

        if ($cached !== null) {
            return $cached;
        }

        $rows = Database::all('user_hotels', ['user_id' => self::id()]);

        return $cached = array_values(array_column($rows, 'hotel_id'));
    }

    /**
     * Silently re-establishes a session from the "remember me" cookie,
     * if present and valid. Call once per web request, before any
     * auth checks run.
     */
    public static function attemptRememberLogin(): void
    {
        if (self::check()) {
            return;
        }

        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;

        if (!is_string($cookie) || !str_contains($cookie, '.')) {
            return;
        }

        [$userId, $token] = explode('.', $cookie, 2);
        $user = Database::first('users', ['id' => $userId, 'is_deleted' => 0]);

        if ($user === null || $user['status'] !== 'active' || empty($user['remember_token'])) {
            return;
        }

        if (!hash_equals((string) $user['remember_token'], hash('sha256', $token))) {
            return;
        }

        self::login($user);
    }
}
