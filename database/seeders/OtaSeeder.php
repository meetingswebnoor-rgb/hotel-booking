<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;

/**
 * 10 OTAs. Commission percentages are plausible placeholder defaults —
 * adjust per hotel's real OTA contracts once that data exists.
 *
 * Hostelworld was in the original schema-module spec; Hotels.com was
 * added later for the booking-form module. Both are kept (rather than
 * swapping one for the other) so existing seeded bookings that
 * reference Hostelworld by ota_id don't get silently orphaned.
 */
return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $otas = [
            ['name' => 'Booking.com', 'commission_pct' => 15.00, 'brand_color' => '#003580'],
            ['name' => 'Agoda', 'commission_pct' => 16.00, 'brand_color' => '#5392F9'],
            ['name' => 'Goibibo', 'commission_pct' => 18.00, 'brand_color' => '#EB670F'],
            ['name' => 'MakeMyTrip', 'commission_pct' => 18.00, 'brand_color' => '#E74C3C'],
            ['name' => 'Airbnb', 'commission_pct' => 14.00, 'brand_color' => '#FF385C'],
            ['name' => 'Hostelworld', 'commission_pct' => 12.00, 'brand_color' => '#FF8300'],
            ['name' => 'Hotels.com', 'commission_pct' => 15.00, 'brand_color' => '#D32F2F'],
            ['name' => 'Expedia', 'commission_pct' => 15.00, 'brand_color' => '#00355F'],
            ['name' => 'Direct Booking', 'commission_pct' => 0.00, 'brand_color' => '#6366F1'],
            ['name' => 'Walk-in', 'commission_pct' => 0.00, 'brand_color' => '#64748B'],
        ];

        foreach ($otas as $ota) {
            $existing = Database::first('otas', ['name' => $ota['name']]);

            if ($existing !== null) {
                Database::update('otas', $existing['id'], [
                    'commission_pct' => $ota['commission_pct'],
                    'brand_color' => $ota['brand_color'],
                ]);
                continue;
            }

            Database::insert('otas', [
                'id' => uuid(),
                'name' => $ota['name'],
                'commission_pct' => $ota['commission_pct'],
                'brand_color' => $ota['brand_color'],
                'status' => 'active',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
                'owner_role' => 'super_admin',
                'visibility_scope' => 'global',
            ]);
        }

        $this->log('Seeded 10 OTAs.');
    }
};
