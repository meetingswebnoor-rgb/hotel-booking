<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Central authorization check: Super Admin bypass -> hotel scope ->
 * per-user override (user_permissions) -> role-level default
 * (config/permissions.php). Use the can() global helper from views.
 */
final class Permission
{
    public static function check(string $module, string $action, ?string $hotelId = null): bool
    {
        if (!Auth::check()) {
            return false;
        }

        if (Auth::level() >= RoleLevel::SUPER_ADMIN) {
            return true;
        }

        if ($hotelId !== null && !Auth::hasGlobalHotelAccess() && !in_array($hotelId, Auth::hotelIds(), true)) {
            return false;
        }

        $override = self::userOverride($module, $action, $hotelId);

        if ($override !== null) {
            return $override;
        }

        return self::roleAllows(Auth::roleName(), $module, $action);
    }

    /**
     * Enforces "a user can only create/manage users with a role level
     * lower than their own." Super Admin is unrestricted.
     */
    public static function canManageRoleLevel(int $targetRoleLevel): bool
    {
        if (!Auth::check()) {
            return false;
        }

        if (Auth::level() >= RoleLevel::SUPER_ADMIN) {
            return true;
        }

        return Auth::level() > $targetRoleLevel;
    }

    private static function userOverride(string $module, string $action, ?string $hotelId): ?bool
    {
        $key = "{$module}.{$action}";
        $userId = Auth::id();

        if ($hotelId !== null) {
            $scoped = Database::first('user_permissions', [
                'user_id' => $userId,
                'permission_key' => $key,
                'hotel_id' => $hotelId,
            ]);

            if ($scoped !== null) {
                return (bool) $scoped['is_allowed'];
            }
        }

        $global = Database::first('user_permissions', [
            'user_id' => $userId,
            'permission_key' => $key,
            'hotel_id' => null,
        ]);

        return $global !== null ? (bool) $global['is_allowed'] : null;
    }

    private static function roleAllows(?string $roleName, string $module, string $action): bool
    {
        if ($roleName === null) {
            return false;
        }

        $actions = config("permissions.{$roleName}.{$module}", []);

        return in_array($action, $actions, true);
    }
}
