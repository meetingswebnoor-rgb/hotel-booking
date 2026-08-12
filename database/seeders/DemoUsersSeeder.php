<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;
use App\Services\BookingCalculator;

/**
 * Seeds the three "quick login" demo accounts the public landing page's
 * Live Demo section logs people into (see App\Controllers\AuthController::demoLogin()
 * and the DEMO_MODE env flag), plus a self-contained "Demo Grand Hotel"
 * with a few rooms and sample bookings so those dashboards aren't empty.
 *
 * Idempotent by email/slug like every other seeder — safe to re-run.
 *
 * Note: `admin@hotezo.com` already exists as the level-5 Super Admin
 * SuperAdminSeeder created (using its default email, since SUPER_ADMIN_EMAIL
 * isn't set). The landing page's spec wants that exact email for the
 * level-4 Admin/Owner demo account and says "update instead of duplicate"
 * — so that pre-existing account gets repurposed into the demo Admin
 * here, and `superadmin@hotezo.com` (new) becomes the actual Super Admin
 * demo login. Whoever runs `php cli seed` sees this called out in the
 * printed summary, not just silently changed.
 */
return new class extends Seeder {
    private const HOTEL_SLUG = 'demo-grand-hotel';

    private const ROOMS = [
        ['room_number' => 'D101', 'room_type' => 'Double', 'pax' => 3, 'max_adults' => 2, 'max_children' => 1, 'base_price' => 3500],
        ['room_number' => 'D102', 'room_type' => 'Deluxe', 'pax' => 3, 'max_adults' => 2, 'max_children' => 1, 'base_price' => 5200],
        ['room_number' => 'D103', 'room_type' => 'Suite', 'pax' => 4, 'max_adults' => 3, 'max_children' => 1, 'base_price' => 8200],
    ];

    private const BOOKING_COUNT = 18;

    private const FIRST_NAMES = ['Aarav', 'Diya', 'Kabir', 'Meera', 'Rohan', 'Sanya', 'Vivaan', 'Ananya'];
    private const LAST_NAMES = ['Sharma', 'Iyer', 'Nair', 'Khan', 'Patel', 'Verma'];

    public function run(PDO $pdo): void
    {
        $hotelId = $this->upsertHotel();
        $this->seedRooms($hotelId);

        $superAdmin = $this->upsertUser(
            email: 'superadmin@hotezo.com',
            password: 'Super@Hotezo2026',
            fullName: 'Demo Super Admin',
            username: 'demo_super_admin',
            roleName: 'super_admin',
            hotelAssignmentType: 'all',
            hotelId: null
        );

        $repurposedExistingAdmin = Database::first('users', ['email' => 'admin@hotezo.com']) !== null;
        $admin = $this->upsertUser(
            email: 'admin@hotezo.com',
            password: 'Admin@Hotezo2026',
            fullName: 'Demo Hotel Admin (Owner)',
            username: 'demo_admin',
            roleName: 'admin',
            hotelAssignmentType: 'single',
            hotelId: $hotelId
        );

        $manager = $this->upsertUser(
            email: 'manager@hotezo.com',
            password: 'Manager@Hotezo2026',
            fullName: 'Demo Hotel Manager',
            username: 'demo_manager',
            roleName: 'hotel_manager',
            hotelAssignmentType: 'single',
            hotelId: $hotelId
        );

        $this->assignToHotel($admin, $hotelId);
        $this->assignToHotel($manager, $hotelId);

        $this->seedBookings($hotelId);
        $this->seedTodayOperations($hotelId);

        $this->log('Seeded demo accounts:');
        $this->log('  Super Admin    superadmin@hotezo.com / Super@Hotezo2026');
        $this->log('  Admin (Owner)  admin@hotezo.com / Admin@Hotezo2026'
            . ($repurposedExistingAdmin ? '  [repurposed a pre-existing account — see this file\'s docblock]' : ''));
        $this->log('  Hotel Manager  manager@hotezo.com / Manager@Hotezo2026');
        $this->log('  All scoped to "Demo Grand Hotel" except Super Admin (global).');
    }

    private function upsertHotel(): string
    {
        $existing = Database::first('hotels', ['slug' => self::HOTEL_SLUG]);

        if ($existing !== null) {
            return (string) $existing['id'];
        }

        $id = uuid();

        Database::insert('hotels', [
            'id' => $id,
            'name' => 'Demo Grand Hotel',
            'slug' => self::HOTEL_SLUG,
            'address' => '1 Demo Avenue, MG Road',
            'city' => 'Bengaluru',
            'country' => 'India',
            'gst_number' => '29DEMOG1234H1Z6',
            'pan' => 'DEMOG1234H',
            'contact_person' => 'Demo Front Desk',
            'mobile' => '9876500099',
            'email' => 'frontdesk@demograndhotel.example',
            'bank_account' => '000901234599',
            'ifsc' => 'HDFC0009999',
            'account_holder' => 'Demo Grand Hotel Pvt Ltd',
            'commission_pct' => 15.00,
            'settlement_type' => 'Monthly',
            'gst_type' => 'Registered',
            'status' => 'active',
            'gallery' => json_encode([]),
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
            'owner_role' => 'super_admin',
            'visibility_scope' => 'hotel',
        ]);

        return $id;
    }

    private function seedRooms(string $hotelId): void
    {
        foreach (self::ROOMS as $room) {
            $existing = Database::first('rooms', ['hotel_id' => $hotelId, 'room_number' => $room['room_number']]);

            if ($existing !== null) {
                continue;
            }

            Database::insert('rooms', [
                'id' => uuid(),
                'hotel_id' => $hotelId,
                'room_number' => $room['room_number'],
                'room_type' => $room['room_type'],
                'pax' => $room['pax'],
                'max_adults' => $room['max_adults'],
                'max_children' => $room['max_children'],
                'base_price' => $room['base_price'],
                'status' => 'Available',
                'images' => json_encode([]),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
                'owner_role' => 'super_admin',
                'visibility_scope' => 'hotel',
            ]);
        }
    }

    /**
     * @return array<string, mixed> the user row (fresh from the DB)
     */
    private function upsertUser(
        string $email,
        string $password,
        string $fullName,
        string $username,
        string $roleName,
        string $hotelAssignmentType,
        ?string $hotelId
    ): array {
        $role = Database::first('roles', ['name' => $roleName]);

        if ($role === null) {
            throw new RuntimeException("Seed roles before demo users (roles.{$roleName} not found).");
        }

        $data = [
            'full_name' => $fullName,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role_id' => $role['id'],
            'status' => 'active',
            'assigned_hotels' => json_encode($hotelId !== null ? [$hotelId] : []),
            'hotel_assignment_type' => $hotelAssignmentType,
            'is_demo' => 1,
            'is_deleted' => 0,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'updated_at' => $this->now(),
        ];

        $existing = Database::first('users', ['email' => $email]);
        $excludeId = $existing['id'] ?? null;
        $usernameTaken = Database::first('users', ['username' => $username]);
        $usernameCollides = $usernameTaken !== null && $usernameTaken['id'] !== $excludeId;
        $data['username'] = $usernameCollides ? $username . '_' . substr(uuid(), 0, 8) : $username;

        if ($existing !== null) {
            Database::update('users', $existing['id'], $data);

            return Database::first('users', ['id' => $existing['id']]);
        }

        $id = uuid();

        Database::insert('users', array_merge($data, [
            'id' => $id,
            'email' => $email,
            'designation' => 'Demo Account',
            'created_at' => $this->now(),
            'owner_role' => 'super_admin',
            'visibility_scope' => 'global',
        ]));

        return Database::first('users', ['id' => $id]);
    }

    private function assignToHotel(array $user, string $hotelId): void
    {
        $existing = Database::first('user_hotels', ['user_id' => $user['id'], 'hotel_id' => $hotelId]);

        if ($existing !== null) {
            return;
        }

        Database::insert('user_hotels', [
            'id' => uuid(),
            'user_id' => $user['id'],
            'hotel_id' => $hotelId,
            'is_primary' => 1,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
            'owner_role' => 'super_admin',
            'visibility_scope' => 'hotel',
        ]);
    }

    private function seedBookings(string $hotelId): void
    {
        $existing = (int) Database::query('SELECT COUNT(*) FROM bookings WHERE hotel_id = ?', [$hotelId])->fetchColumn();

        if ($existing > 0) {
            $this->log("Demo Grand Hotel already has {$existing} booking(s) — skipping booking seed.");

            return;
        }

        $rooms = Database::all('rooms', ['hotel_id' => $hotelId, 'is_deleted' => 0]);
        $hotel = Database::first('hotels', ['id' => $hotelId]);

        if ($rooms === [] || $hotel === null) {
            return;
        }

        $otas = Database::all('otas', ['is_deleted' => 0]);
        $directOta = null;
        $walkinOta = null;
        $realOtas = [];

        foreach ($otas as $ota) {
            if ($ota['name'] === 'Direct Booking') {
                $directOta = $ota;
            } elseif ($ota['name'] === 'Walk-in') {
                $walkinOta = $ota;
            } else {
                $realOtas[] = $ota;
            }
        }

        $now = new DateTimeImmutable();
        $rangeStart = $now->modify('first day of this month')->modify('-4 months');
        $daysSpan = max(1, $rangeStart->diff($now)->days);
        $statuses = ['confirmed', 'confirmed', 'checked_in', 'checked_out', 'checked_out', 'pending'];

        for ($i = 0; $i < self::BOOKING_COUNT; $i++) {
            $bookingDate = $rangeStart->modify('+' . random_int(0, $daysSpan) . ' days');
            $nights = random_int(1, 4);
            $checkin = $bookingDate->modify('+' . random_int(0, 10) . ' days');
            $checkout = $checkin->modify("+{$nights} days");

            $room = $rooms[array_rand($rooms)];
            $roomLines = [[
                'room_type' => $room['room_type'],
                'rate_plan_id' => null,
                'adults' => min((int) $room['max_adults'], 2) ?: 1,
                'children' => 0,
                'quantity' => 1,
                'nightly_rate' => (float) $room['base_price'],
            ]];

            $roll = random_int(1, 100);
            if ($roll <= 55 && $realOtas !== []) {
                $ota = $realOtas[array_rand($realOtas)];
                $source = 'ota';
            } elseif ($roll <= 85) {
                $ota = $directOta;
                $source = 'online';
            } else {
                $ota = $walkinOta;
                $source = 'walkin';
            }

            $calc = BookingCalculator::calculate(
                $roomLines,
                $nights,
                $ota !== null ? (float) $ota['commission_pct'] : 0.0,
                (float) $hotel['commission_pct'],
                $source
            );

            $status = $statuses[array_rand($statuses)];
            if ($checkin > $now && in_array($status, ['checked_in', 'checked_out'], true)) {
                $status = 'confirmed';
            }

            $guestName = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)] . ' ' . self::LAST_NAMES[array_rand(self::LAST_NAMES)];

            Database::insert('bookings', [
                'id' => uuid(),
                'booking_id' => sprintf('HTZ-DEMO-%04d', $i + 1),
                'hotel_id' => $hotelId,
                'ota_id' => $ota['id'] ?? null,
                'guest_name' => $guestName,
                'guest_mobile' => '9' . random_int(100000000, 999999999),
                'guest_email' => strtolower(str_replace(' ', '.', $guestName)) . '@example.com',
                'booking_date' => $bookingDate->format('Y-m-d'),
                'checkin_date' => $checkin->format('Y-m-d'),
                'checkout_date' => $checkout->format('Y-m-d'),
                'nights' => $nights,
                'rooms' => json_encode($roomLines),
                'adults' => $roomLines[0]['adults'],
                'children' => 0,
                'total_room_rent' => $calc['total_room_rent'],
                'hotel_gst' => $calc['hotel_gst'],
                'tds' => $calc['tds'],
                'tcs' => $calc['tcs'],
                'ota_commission' => $calc['ota_commission'],
                'hotezo_commission' => $calc['hotezo_commission'],
                'gst_on_commission' => $calc['gst_on_commission'],
                'total_commission_taxes' => $calc['total_commission_taxes'],
                'hotel_earning' => $calc['hotel_earning'],
                'hotel_collection' => $calc['hotel_collection'],
                'hotezo_collection' => $calc['hotezo_collection'],
                'status' => $status,
                'ota_payment_status' => $status === 'checked_out' ? 'paid' : 'pending',
                'source' => $source,
                'checked_in_at' => in_array($status, ['checked_in', 'checked_out'], true) ? $checkin->format('Y-m-d H:i:s') : null,
                'checked_out_at' => $status === 'checked_out' ? $checkout->format('Y-m-d H:i:s') : null,
                'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
                'owner_role' => 'super_admin',
                'visibility_scope' => 'hotel',
            ]);
        }

        $this->log('Seeded ' . self::BOOKING_COUNT . ' sample bookings for Demo Grand Hotel.');
    }

    /**
     * The bulk history above (seedBookings()) only runs once — re-running
     * `php cli seed` on a later day would otherwise leave the dashboard's
     * "Today's Operations" panel (App\Controllers\DashboardController::
     * todayOperations()) permanently empty, since every seeded date would
     * have drifted into the past. These two bookings are upserted by a
     * fixed booking_id on every single seed run instead, so the demo
     * always has at least one check-in and one check-out due "today" —
     * a lightweight stand-in for the nightly-reset job this seeder's
     * class docblock earmarks as future work.
     */
    private function seedTodayOperations(string $hotelId): void
    {
        $rooms = Database::all('rooms', ['hotel_id' => $hotelId, 'is_deleted' => 0]);
        $hotel = Database::first('hotels', ['id' => $hotelId]);
        $directOta = Database::first('otas', ['name' => 'Direct Booking']);

        if ($rooms === [] || $hotel === null) {
            return;
        }

        $today = new DateTimeImmutable();
        $room = $rooms[0];

        $entries = [
            [
                'booking_id' => 'HTZ-DEMO-TODAY-CHECKIN',
                'guest_name' => 'Priya Nair',
                'checkin' => $today,
                'checkout' => $today->modify('+2 days'),
                'status' => 'confirmed',
            ],
            [
                'booking_id' => 'HTZ-DEMO-TODAY-CHECKOUT',
                'guest_name' => 'Arjun Mehta',
                'checkin' => $today->modify('-2 days'),
                'checkout' => $today,
                'status' => 'checked_in',
            ],
        ];

        foreach ($entries as $entry) {
            $nights = BookingCalculator::nights($entry['checkin']->format('Y-m-d'), $entry['checkout']->format('Y-m-d'));
            $roomLine = [
                'room_type' => $room['room_type'],
                'rate_plan_id' => null,
                'adults' => min((int) $room['max_adults'], 2) ?: 1,
                'children' => 0,
                'quantity' => 1,
                'nightly_rate' => (float) $room['base_price'],
            ];
            $calc = BookingCalculator::calculate([$roomLine], $nights, 0.0, (float) $hotel['commission_pct'], 'online');

            $data = [
                'hotel_id' => $hotelId,
                'ota_id' => $directOta['id'] ?? null,
                'guest_name' => $entry['guest_name'],
                'guest_mobile' => '9' . substr(md5($entry['booking_id']), 0, 9),
                'guest_email' => strtolower(str_replace(' ', '.', $entry['guest_name'])) . '@example.com',
                'booking_date' => $entry['checkin']->modify('-3 days')->format('Y-m-d'),
                'checkin_date' => $entry['checkin']->format('Y-m-d'),
                'checkout_date' => $entry['checkout']->format('Y-m-d'),
                'nights' => $nights,
                'rooms' => json_encode([$roomLine]),
                'adults' => $roomLine['adults'],
                'children' => 0,
                'total_room_rent' => $calc['total_room_rent'],
                'hotel_gst' => $calc['hotel_gst'],
                'tds' => $calc['tds'],
                'tcs' => $calc['tcs'],
                'ota_commission' => $calc['ota_commission'],
                'hotezo_commission' => $calc['hotezo_commission'],
                'gst_on_commission' => $calc['gst_on_commission'],
                'total_commission_taxes' => $calc['total_commission_taxes'],
                'hotel_earning' => $calc['hotel_earning'],
                'hotel_collection' => $calc['hotel_collection'],
                'hotezo_collection' => $calc['hotezo_collection'],
                'status' => $entry['status'],
                'ota_payment_status' => 'pending',
                'source' => 'online',
                'updated_at' => $this->now(),
            ];

            $existing = Database::first('bookings', ['booking_id' => $entry['booking_id']]);

            if ($existing !== null) {
                Database::update('bookings', $existing['id'], $data);
                continue;
            }

            Database::insert('bookings', array_merge($data, [
                'id' => uuid(),
                'booking_id' => $entry['booking_id'],
                'created_at' => $this->now(),
                'owner_role' => 'super_admin',
                'visibility_scope' => 'hotel',
            ]));
        }

        $this->log('Refreshed the two always-current "today" demo bookings.');
    }
};
