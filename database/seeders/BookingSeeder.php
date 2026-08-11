<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;

/**
 * Realistic-ish sample bookings across the seeded hotels, spread over
 * the last 6 months, mixing OTA/direct/walk-in channels and booking
 * statuses — exists purely so the analytics dashboard has real data
 * to aggregate (and to actually verify against) instead of an empty
 * table. Guest names/emails are fictional placeholders. Skips
 * entirely if bookings already exist, so re-running `php cli seed`
 * doesn't duplicate data.
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

                $roomLines = [$this->roomLine($rooms[array_rand($rooms)], $nights)];

                if (random_int(1, 100) <= 15) {
                    $roomLines[] = $this->roomLine($rooms[array_rand($rooms)], $nights);
                }

                $totalRoomRent = round(array_sum(array_column($roomLines, 'subtotal')), 2);

                [$ota, $source] = $this->pickChannel($realOtas, $directOta, $walkinOta);

                $otaCommissionPct = $ota !== null ? (float) $ota['commission_pct'] : 0.0;
                $otaCommission = round($totalRoomRent * $otaCommissionPct / 100, 2);
                $hotezoCommission = round($totalRoomRent * (float) $hotel['commission_pct'] / 100, 2);
                $gstOnCommission = round($hotezoCommission * 0.18, 2);
                $hotelGst = round($totalRoomRent * 0.12, 2);
                $tds = round($totalRoomRent * 0.01, 2);
                $totalCommissionTaxes = round($otaCommission + $hotezoCommission + $gstOnCommission, 2);
                $hotelEarning = round($totalRoomRent + $hotelGst - $otaCommission - $hotezoCommission - $gstOnCommission - $tds, 2);
                $hotelCollection = $source === 'ota'
                    ? round($totalRoomRent + $hotelGst - $otaCommission, 2)
                    : round($totalRoomRent + $hotelGst, 2);
                $hotezoCollection = round($hotezoCommission + $gstOnCommission, 2);

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
                    'total_room_rent' => $totalRoomRent,
                    'hotel_gst' => $hotelGst,
                    'tds' => $tds,
                    'tcs' => 0,
                    'ota_commission' => $otaCommission,
                    'hotezo_commission' => $hotezoCommission,
                    'gst_on_commission' => $gstOnCommission,
                    'total_commission_taxes' => $totalCommissionTaxes,
                    'hotel_earning' => $hotelEarning,
                    'hotel_collection' => $hotelCollection,
                    'hotezo_collection' => $hotezoCollection,
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
     * @return array{room_id: string, room_type: string, nightly_rate: float, nights: int, subtotal: float}
     */
    private function roomLine(array $room, int $nights): array
    {
        $rate = (float) $room['base_price'];

        return [
            'room_id' => $room['id'],
            'room_type' => $room['room_type'],
            'nightly_rate' => $rate,
            'nights' => $nights,
            'subtotal' => round($rate * $nights, 2),
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
