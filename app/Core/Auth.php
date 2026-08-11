<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session-based auth against the `users` table (created by the
 * upcoming Auth/RBAC module). Roles: super_admin, hotel_admin, hotel_manager.
 */
final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::first('users', ['email' => $email, 'is_deleted' => 0]);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        self::login($user);

        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('auth_user_id', $user['id']);
        Session::set('auth_user_role', $user['role']);
        Session::set('auth_hotel_id', $user['hotel_id'] ?? null);
    }

    public static function logout(): void
    {
        Session::remove('auth_user_id');
        Session::remove('auth_user_role');
        Session::remove('auth_hotel_id');
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

    public static function role(): ?string
    {
        return Session::get('auth_user_role');
    }

    /**
     * Non-super-admin users are scoped to the hotel assigned on login.
     */
    public static function hotelId(): int|string|null
    {
        return Session::get('auth_hotel_id');
    }

    public static function hasRole(string ...$roles): bool
    {
        $role = self::role();

        return $role !== null && in_array($role, $roles, true);
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
}
