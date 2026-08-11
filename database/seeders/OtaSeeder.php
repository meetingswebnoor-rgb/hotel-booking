<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;

/**
 * 9 OTAs. Commission percentages are plausible placeholder defaults —
 * adjust per hotel's real OTA contracts once that data exists.
 */
return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $otas = [
            ['name' => 'Booking.com', 'commission_pct' => 15.00],
            ['name' => 'Agoda', 'commission_pct' => 16.00],
            ['name' => 'Goibibo', 'commission_pct' => 18.00],
            ['name' => 'MakeMyTrip', 'commission_pct' => 18.00],
            ['name' => 'Airbnb', 'commission_pct' => 14.00],
            ['name' => 'Hostelworld', 'commission_pct' => 12.00],
            ['name' => 'Expedia', 'commission_pct' => 15.00],
            ['name' => 'Direct Booking', 'commission_pct' => 0.00],
            ['name' => 'Walk-in', 'commission_pct' => 0.00],
        ];

        foreach ($otas as $ota) {
            $existing = Database::first('otas', ['name' => $ota['name']]);

            if ($existing !== null) {
                Database::update('otas', $existing['id'], ['commission_pct' => $ota['commission_pct']]);
                continue;
            }

            Database::insert('otas', [
                'id' => uuid(),
                'name' => $ota['name'],
                'commission_pct' => $ota['commission_pct'],
                'status' => 'active',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
                'owner_role' => 'super_admin',
                'visibility_scope' => 'global',
            ]);
        }

        $this->log('Seeded 9 OTAs.');
    }
};
