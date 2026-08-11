<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;

/**
 * 10 roles across 5 levels. level: 0 = lowest authority, 5 = highest
 * (Super Admin) — see App\Core\RoleLevel for the named constants.
 */
return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $roles = [
            ['name' => 'super_admin', 'level' => 5, 'description' => 'Bypasses all rules; full system access.'],
            ['name' => 'admin', 'level' => 4, 'description' => 'Full management of users, hotels, and OTAs.'],
            ['name' => 'hotel_manager', 'level' => 3, 'description' => 'Full operational control for assigned hotels only.'],
            ['name' => 'revenue_manager', 'level' => 2, 'description' => 'Access to financial data and revenue reports.'],
            ['name' => 'ota_manager', 'level' => 2, 'description' => 'Manages OTA relationships and commissions.'],
            ['name' => 'reservation_manager', 'level' => 2, 'description' => 'Manages bookings and guest invoices.'],
            ['name' => 'accounts', 'level' => 2, 'description' => 'Manages settlements, billing, and invoicing.'],
            ['name' => 'front_desk', 'level' => 1, 'description' => 'Creates bookings, generates invoices, and handles check-in/check-out.'],
            ['name' => 'reception', 'level' => 1, 'description' => 'Creates bookings. Default role for new users.'],
            ['name' => 'read_only', 'level' => 0, 'description' => 'View-only access — no create, edit, or delete.'],
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
