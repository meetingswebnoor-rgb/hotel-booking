<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;

/**
 * Creates the one Super Admin account. Override the defaults via
 * SUPER_ADMIN_EMAIL / SUPER_ADMIN_PASSWORD in .env; otherwise a
 * random password is generated and printed once — it is never stored
 * anywhere except as a bcrypt hash.
 */
return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $email = (string) env('SUPER_ADMIN_EMAIL', 'admin@hotezo.com');
        $existing = Database::first('users', ['email' => $email]);

        if ($existing !== null) {
            $this->log("Super Admin already exists: {$email}");

            return;
        }

        $role = Database::first('roles', ['name' => 'super_admin']);

        if ($role === null) {
            throw new RuntimeException('Seed roles before seeding the Super Admin (roles.super_admin not found).');
        }

        $password = (string) env('SUPER_ADMIN_PASSWORD', '');
        $password = $password !== '' ? $password : bin2hex(random_bytes(6));

        Database::insert('users', [
            'id' => uuid(),
            'full_name' => 'Hotezo Super Admin',
            'username' => 'superadmin',
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'designation' => 'Super Administrator',
            'role_id' => $role['id'],
            'status' => 'active',
            'assigned_hotels' => json_encode([]),
            'hotel_assignment_type' => 'all',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
            'owner_role' => 'super_admin',
            'visibility_scope' => 'global',
        ]);

        $this->log('Seeded Super Admin:');
        $this->log("  Email:    {$email}");
        $this->log("  Password: {$password}");
        $this->log('  (change this after first login — it will not be shown again)');
    }
};
