<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;
use App\Services\BookingCalculator;

/**
 * Realistic-ish sample bookings across the seeded hotels, spread over
 * the last 6 months, mixing OTA/direct/walk-in channels and booking
 * statuses — exists purely so the analytics dashboard has real data
 * to aggregate (and to actually verify against) instead of an empty
 * table. Guest names/emails are fictional placeholders. Skips
 * entirely if bookings already exist, so re-running `php cli seed`
 * doesn't duplicate data.
 *
 * Uses the same App\Services\BookingCalculator and room-line JSON
 * shape (room_type/rate_plan_id/adults/children/quantity/nightly_rate)
 * as the real booking form, so seeded and form-created bookings are
 * computed identically and the dashboard's room-type parsing only
 * ever sees one shape.
 */
return new class extends Seeder {
    private const STATUSES = [
        'confirmed', 'confirmed', 'confirmed',
        'checked_in',
        'checked_out', 'checked_out', 'checked_out',
        'pending', 'cancelled', 'no_show',
    ];

    private const PAYMENT_STATUSES = ['pending', 'paid', 'paid', 'paid', 'hold', 'disputed'];

    private const FIRST_NAMES = [
        'Aarav', 'Vivaan', 'Aditya', 'Ishaan', 'Kabir', 'Ananya', 'Diya', 'Priya',
        'Meera', 'Sanya', 'Rohan', 'Karan', 'Neha', 'Pooja', 'Arjun', 'Simran',
    ];

    private const LAST_NAMES = [
        'Sharma', 'Verma', 'Gupta', 'Iyer', 'Nair', 'Reddy', 'Khan', 'Patel',
        'Singh', 'Rao', 'Mehta', 'Chopra',
    ];

    public function run(PDO $pdo): void
    {
        $existing = (int) Database::query('SELECT COUNT(*) FROM bookings')->fetchColumn();

        if ($existing > 0) {
            $this->log("Bookings already seeded ({$existing} rows) — skipping.");

            return;
        }

        $hotels = Database::all('hotels', ['is_deleted' => 0]);

        if ($hotels === []) {
            $this->log('No hotels found — skipping booking seed. Run HotelSeeder first.');

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
        $rangeStart = $now->modify('first day of this month')->modify('-5 months');
        $daysSpan = max(1, $rangeStart->diff($now)->days);

        $bookingCounter = 1;
        $totalInserted = 0;

        foreach ($hotels as $hotel) {
            $rooms = Database::all('rooms', ['hotel_id' => $hotel['id'], 'is_deleted' => 0]);

            if ($rooms === []) {
                continue;
            }

            $count = random_int(45, 70);

            for ($i = 0; $i < $count; $i++) {
                $bookingDate = $rangeStart->modify('+' . random_int(0, $daysSpan) . ' days');
                $nights = random_int(1, 5);
                $checkin = $bookingDate->modify('+' . random_int(0, 14) . ' days');
                $checkout = $checkin->modify("+{$nights} days");

                $roomLines = [$this->roomLine($rooms[array_rand($rooms)])];

                if (random_int(1, 100) <= 15) {
                    $roomLines[] = $this->roomLine($rooms[array_rand($rooms)]);
                }

                [$ota, $source] = $this->pickChannel($realOtas, $directOta, $walkinOta);

                $calc = BookingCalculator::calculate(
                    $roomLines,
                    $nights,
                    $ota !== null ? (float) $ota['commission_pct'] : 0.0,
                    (float) $hotel['commission_pct'],
                    $source
                );

                $status = self::STATUSES[array_rand(self::STATUSES)];

                if ($checkin > $now && in_array($status, ['checked_in', 'checked_out'], true)) {
                    $status = 'confirmed';
                }

                $guestName = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)] . ' ' . self::LAST_NAMES[array_rand(self::LAST_NAMES)];
                $adults = random_int(1, 3);
                $children = random_int(1, 100) <= 25 ? random_int(1, 2) : 0;

                Database::insert('bookings', [
                    'id' => uuid(),
                    'booking_id' => sprintf('HTZ-%s-%06d', $bookingDate->format('Y'), $bookingCounter++),
                    'hotel_id' => $hotel['id'],
                    'ota_id' => $ota['id'] ?? null,
                    'guest_name' => $guestName,
                    'guest_mobile' => '9' . random_int(100000000, 999999999),
                    'guest_email' => strtolower(str_replace(' ', '.', $guestName)) . '@example.com',
                    'booking_date' => $bookingDate->format('Y-m-d'),
                    'checkin_date' => $checkin->format('Y-m-d'),
                    'checkout_date' => $checkout->format('Y-m-d'),
                    'nights' => $nights,
                    'rooms' => json_encode($roomLines),
                    'adults' => $adults,
                    'children' => $children,
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
                    'ota_payment_status' => self::PAYMENT_STATUSES[array_rand(self::PAYMENT_STATUSES)],
                    'source' => $source,
                    'checked_in_at' => in_array($status, ['checked_in', 'checked_out'], true) ? $checkin->format('Y-m-d H:i:s') : null,
                    'checked_out_at' => $status === 'checked_out' ? $checkout->format('Y-m-d H:i:s') : null,
                    'cancelled_at' => $status === 'cancelled' ? $bookingDate->modify('+1 day')->format('Y-m-d H:i:s') : null,
                    'created_at' => $bookingDate->format('Y-m-d H:i:s'),
                    'updated_at' => $bookingDate->format('Y-m-d H:i:s'),
                    'owner_role' => 'hotel_admin',
                    'visibility_scope' => 'hotel',
                ]);

                $totalInserted++;
            }
        }

        $this->log("Seeded {$totalInserted} sample bookings across " . count($hotels) . ' hotels.');
    }

    /**
     * @param array<string, mixed> $room
     * @return array{room_type: string, rate_plan_id: null, adults: int, children: int, quantity: int, nightly_rate: float}
     */
    private function roomLine(array $room): array
    {
        return [
            'room_type' => $room['room_type'],
            'rate_plan_id' => null,
            'adults' => min((int) $room['max_adults'], 2) ?: 1,
            'children' => 0,
            'quantity' => 1,
            'nightly_rate' => (float) $room['base_price'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $realOtas
     * @param array<string, mixed>|null $directOta
     * @param array<string, mixed>|null $walkinOta
     * @return array{0: array<string, mixed>|null, 1: string}
     */
    private function pickChannel(array $realOtas, ?array $directOta, ?array $walkinOta): array
    {
        $roll = random_int(1, 100);

        if ($roll <= 55 && $realOtas !== []) {
            return [$realOtas[array_rand($realOtas)], 'ota'];
        }

        if ($roll <= 85) {
            return [$directOta, 'online'];
        }

        return [$walkinOta, 'walkin'];
    }
};
