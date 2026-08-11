<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Named levels for the 10-role model (see database/seeders/RoleSeeder.php).
 * Higher level = more power. Levels aren't 1:1 with roles — level 2
 * alone covers four distinct roles (Revenue/OTA/Reservation Managers,
 * Accounts) that differ by module, not seniority; that distinction is
 * resolved by the config/permissions.php matrix, not by level.
 */
final class RoleLevel
{
    public const READ_ONLY = 0;
    public const STAFF = 1;
    public const MANAGER = 2;
    public const HOTEL_MANAGER = 3;
    public const ADMIN = 4;
    public const SUPER_ADMIN = 5;
}
