<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;

/**
 * 3 sample hotels, each with a handful of rooms and rate plans, so
 * the booking/commission modules have realistic data to build against.
 * Contact/bank details are fictional placeholders.
 */
return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $hotels = [
            [
                'name' => 'The Grand Horizon',
                'slug' => 'the-grand-horizon-mumbai',
                'address' => '14 Marine Drive, Nariman Point',
                'city' => 'Mumbai',
                'gst_number' => '27AGHPL1234F1Z5',
                'pan' => 'AGHPL1234F',
                'contact_person' => 'Rohan Mehta',
                'mobile' => '9876500001',
                'email' => 'reservations@grandhorizon.example',
                'bank_account' => '000401234567',
                'ifsc' => 'HDFC0001234',
                'account_holder' => 'The Grand Horizon Hospitality Pvt Ltd',
                'commission_pct' => 15.00,
                'settlement_type' => 'Monthly',
                'gst_type' => 'Registered',
                'status' => 'active',
                'rooms' => [
                    ['room_number' => '101', 'room_type' => 'Single', 'pax' => 2, 'max_adults' => 2, 'max_children' => 0, 'base_price' => 2200],
                    ['room_number' => '102', 'room_type' => 'Double', 'pax' => 3, 'max_adults' => 2, 'max_children' => 1, 'base_price' => 3200],
                    ['room_number' => '201', 'room_type' => 'Deluxe', 'pax' => 3, 'max_adults' => 2, 'max_children' => 1, 'base_price' => 4800],
                    ['room_number' => '301', 'room_type' => 'Suite', 'pax' => 4, 'max_adults' => 3, 'max_children' => 1, 'base_price' => 7500],
                ],
                'rate_plans' => [
                    ['plan_name' => 'Standard Single', 'room_type' => 'Single', 'occupancy_type' => 'Single Occupancy', 'season' => 'Regular', 'base_price' => 2200],
                    ['plan_name' => 'Peak Season Deluxe', 'room_type' => 'Deluxe', 'occupancy_type' => 'Double Occupancy', 'season' => 'Peak', 'base_price' => 5800],
                    ['plan_name' => 'Off-Peak Suite', 'room_type' => 'Suite', 'occupancy_type' => 'Double Occupancy', 'season' => 'Off-Peak', 'base_price' => 6200],
                ],
            ],
            [
                'name' => 'Sunset Palm Resort',
                'slug' => 'sunset-palm-resort-goa',
                'address' => 'Beach Road, Calangute',
                'city' => 'Goa',
                'gst_number' => '30SPRGL5678K1Z2',
                'pan' => 'SPRGL5678K',
                'contact_person' => 'Priya Nair',
                'mobile' => '9876500002',
                'email' => 'stay@sunsetpalmresort.example',
                'bank_account' => '000501234568',
                'ifsc' => 'ICIC0005678',
                'account_holder' => 'Sunset Palm Resorts LLP',
                'commission_pct' => 18.00,
                'settlement_type' => 'Weekly',
                'gst_type' => 'Registered',
                'status' => 'active',
                'rooms' => [
                    ['room_number' => 'G1', 'room_type' => 'Double', 'pax' => 2, 'max_adults' => 2, 'max_children' => 0, 'base_price' => 4500],
                    ['room_number' => 'G2', 'room_type' => 'Double', 'pax' => 3, 'max_adults' => 2, 'max_children' => 1, 'base_price' => 4800],
                    ['room_number' => 'V1', 'room_type' => 'Suite', 'pax' => 4, 'max_adults' => 3, 'max_children' => 1, 'base_price' => 9500],
                    ['room_number' => 'V2', 'room_type' => 'Deluxe', 'pax' => 3, 'max_adults' => 2, 'max_children' => 1, 'base_price' => 6800],
                ],
                'rate_plans' => [
                    ['plan_name' => 'Garden View Double', 'room_type' => 'Double', 'occupancy_type' => 'Double Occupancy', 'season' => 'Regular', 'base_price' => 4500],
                    ['plan_name' => 'Peak Season Villa Suite', 'room_type' => 'Suite', 'occupancy_type' => 'Double Occupancy', 'season' => 'Peak', 'base_price' => 12500],
                    ['plan_name' => 'Off-Peak Deluxe', 'room_type' => 'Deluxe', 'occupancy_type' => 'Double Occupancy', 'season' => 'Off-Peak', 'base_price' => 5200],
                ],
            ],
            [
                'name' => 'Heritage Courtyard',
                'slug' => 'heritage-courtyard-jaipur',
                'address' => '22 Hawa Mahal Road',
                'city' => 'Jaipur',
                'gst_number' => null,
                'pan' => 'HCJPL9012M',
                'contact_person' => 'Arjun Singh',
                'mobile' => '9876500003',
                'email' => 'bookings@heritagecourtyard.example',
                'bank_account' => '000601234569',
                'ifsc' => 'SBIN0009012',
                'account_holder' => 'Heritage Courtyard Hotels',
                'commission_pct' => 12.00,
                'settlement_type' => 'On-Demand',
                'gst_type' => 'Unregistered',
                'status' => 'active',
                'rooms' => [
                    ['room_number' => 'H1', 'room_type' => 'Single', 'pax' => 1, 'max_adults' => 1, 'max_children' => 0, 'base_price' => 1800],
                    ['room_number' => 'H2', 'room_type' => 'Double', 'pax' => 2, 'max_adults' => 2, 'max_children' => 0, 'base_price' => 2600],
                    ['room_number' => 'H3', 'room_type' => 'Deluxe', 'pax' => 3, 'max_adults' => 2, 'max_children' => 1, 'base_price' => 3600],
                    ['room_number' => 'H4', 'room_type' => 'Suite', 'pax' => 4, 'max_adults' => 3, 'max_children' => 1, 'base_price' => 5200],
                ],
                'rate_plans' => [
                    ['plan_name' => 'Courtyard Double', 'room_type' => 'Double', 'occupancy_type' => 'Double Occupancy', 'season' => 'Regular', 'base_price' => 2600],
                    ['plan_name' => 'Festival Peak Deluxe', 'room_type' => 'Deluxe', 'occupancy_type' => 'Double Occupancy', 'season' => 'Peak', 'base_price' => 4200],
                    ['plan_name' => 'Off-Peak Single', 'room_type' => 'Single', 'occupancy_type' => 'Single Occupancy', 'season' => 'Off-Peak', 'base_price' => 1500],
                ],
            ],
        ];

        foreach ($hotels as $data) {
            $hotelId = $this->upsertHotel($data);
            $this->seedRooms($hotelId, $data['rooms']);
            $this->seedRatePlans($hotelId, $data['rate_plans']);
        }

        $this->log('Seeded 3 hotels with rooms and rate plans.');
    }

    private function upsertHotel(array $data): string
    {
        $existing = Database::first('hotels', ['slug' => $data['slug']]);

        if ($existing !== null) {
            return (string) $existing['id'];
        }

        $id = uuid();

        Database::insert('hotels', [
            'id' => $id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'address' => $data['address'],
            'city' => $data['city'],
            'country' => 'India',
            'gst_number' => $data['gst_number'],
            'pan' => $data['pan'],
            'contact_person' => $data['contact_person'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'bank_account' => $data['bank_account'],
            'ifsc' => $data['ifsc'],
            'account_holder' => $data['account_holder'],
            'commission_pct' => $data['commission_pct'],
            'settlement_type' => $data['settlement_type'],
            'gst_type' => $data['gst_type'],
            'status' => $data['status'],
            'gallery' => json_encode([]),
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
            'owner_role' => 'hotel_admin',
            'visibility_scope' => 'hotel',
        ]);

        return $id;
    }

    private function seedRooms(string $hotelId, array $rooms): void
    {
        foreach ($rooms as $room) {
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
                'owner_role' => 'hotel_admin',
                'visibility_scope' => 'hotel',
            ]);
        }
    }

    private function seedRatePlans(string $hotelId, array $ratePlans): void
    {
        foreach ($ratePlans as $plan) {
            $existing = Database::first('rate_plans', [
                'hotel_id' => $hotelId,
                'plan_name' => $plan['plan_name'],
            ]);

            if ($existing !== null) {
                continue;
            }

            Database::insert('rate_plans', [
                'id' => uuid(),
                'hotel_id' => $hotelId,
                'plan_name' => $plan['plan_name'],
                'room_type' => $plan['room_type'],
                'occupancy_type' => $plan['occupancy_type'],
                'season' => $plan['season'],
                'base_price' => $plan['base_price'],
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
                'owner_role' => 'hotel_admin',
                'visibility_scope' => 'hotel',
            ]);
        }
    }
};
