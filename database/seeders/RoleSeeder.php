<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;

/**
 * 10 roles. level: 0 = highest authority, 5 = lowest (matches the
 * CHECK constraint on roles.level in 0001_create_roles_table.php).
 */
return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $roles = [
            ['name' => 'super_admin', 'level' => 0, 'description' => 'Full control of the Hotezo platform across all hotels.'],
            ['name' => 'platform_auditor', 'level' => 1, 'description' => 'Hotezo staff; read-only access across all hotels for compliance and audit.'],
            ['name' => 'platform_support', 'level' => 1, 'description' => 'Hotezo staff; can assist hotels with bookings and invoices but not financial configuration.'],
            ['name' => 'hotel_admin', 'level' => 2, 'description' => 'Owns and fully controls one or more assigned hotels.'],
            ['name' => 'hotel_manager', 'level' => 2, 'description' => "Runs day-to-day operations for the hotel(s) they're assigned to."],
            ['name' => 'revenue_manager', 'level' => 3, 'description' => 'Manages room rates, rate plans, and OTA mapping.'],
            ['name' => 'accountant', 'level' => 3, 'description' => 'Manages invoices, settlements, and financial reports.'],
            ['name' => 'front_desk', 'level' => 4, 'description' => 'Handles guest check-in/check-out and marks bookings paid or unpaid.'],
            ['name' => 'housekeeping', 'level' => 4, 'description' => 'Updates room status (Available/Occupied/Maintenance).'],
            ['name' => 'read_only_viewer', 'level' => 5, 'description' => 'View-only access with no write permissions.'],
        ];

        foreach ($roles as $role) {
            $existing = Database::first('roles', ['name' => $role['name']]);

            if ($existing !== null) {
                Database::update('roles', $existing['id'], [
                    'level' => $role['level'],
                    'description' => $role['description'],
                ]);
                continue;
            }

            Database::insert('roles', [
                'id' => uuid(),
                'name' => $role['name'],
                'level' => $role['level'],
                'description' => $role['description'],
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
                'owner_role' => 'super_admin',
                'visibility_scope' => 'global',
            ]);
        }

        $this->log('Seeded 10 roles.');
    }
};
