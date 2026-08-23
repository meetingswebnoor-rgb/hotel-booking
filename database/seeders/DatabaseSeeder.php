<?php

declare(strict_types=1);

use App\Core\Seeder;

/**
 * Orchestrates every seeder in dependency order. Run via `php cli seed`.
 */
return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $seeders = [
            BASE_PATH . '/database/seeders/RoleSeeder.php',
            BASE_PATH . '/database/seeders/OtaSeeder.php',
            BASE_PATH . '/database/seeders/SuperAdminSeeder.php',
            BASE_PATH . '/database/seeders/HotelSeeder.php',
            BASE_PATH . '/database/seeders/CompanyComplianceSeeder.php',
            BASE_PATH . '/database/seeders/BookingSeeder.php',
            BASE_PATH . '/database/seeders/DemoUsersSeeder.php',
        ];

        foreach ($seeders as $file) {
            /** @var Seeder $seeder */
            $seeder = require $file;
            $seeder->run($pdo);
        }

        $this->log('Database seeding complete.');
    }
};
