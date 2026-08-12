<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Validator;
use App\Models\Room;

/**
 * The one place room validation + save/delete logic lives, so the
 * hotel hub's Rooms tab (App\Controllers\HotelController) and the
 * standalone Rooms page (App\Controllers\RoomController) share
 * identical behavior instead of each re-implementing it.
 */
final class RoomService
{
    public const ROOM_TYPES = ['Single', 'Double', 'Suite', 'Deluxe'];
    public const STATUSES = ['Available', 'Occupied', 'Maintenance'];

    /**
     * @param array<string, mixed> $input
     * @return array<string, array<int, string>> empty when valid
     */
    public static function validate(array $input, string $hotelId, ?string $excludeId): array
    {
        $rules = [
            'room_number' => 'required|max:20',
            'room_type' => 'required|in:' . implode(',', self::ROOM_TYPES),
            'pax' => 'required|numeric',
            'max_adults' => 'required|numeric',
            'max_children' => 'required|numeric',
            'base_price' => 'required|numeric',
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ];
        $errors = Validator::make($input, $rules)->errors();

        $roomNumber = trim((string) ($input['room_number'] ?? ''));

        if ($roomNumber !== '') {
            $dupe = Database::first('rooms', ['hotel_id' => $hotelId, 'room_number' => $roomNumber, 'is_deleted' => 0]);
            if ($dupe !== null && ($excludeId === null || $dupe['id'] !== $excludeId)) {
                $errors['room_number'][] = 'A room with this number already exists at this hotel.';
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input already-validated
     * @param array<string, mixed>|null $existing
     * @return string the room id (new or existing)
     */
    public static function save(array $input, string $hotelId, ?array $existing, ?string $newImagePath, ?string $ownerRole): string
    {
        $images = self::resolveImages($existing, $newImagePath);

        $data = [
            'hotel_id' => $hotelId,
            'room_number' => sanitize(trim((string) $input['room_number'])),
            'room_type' => (string) $input['room_type'],
            'pax' => (int) $input['pax'],
            'max_adults' => (int) $input['max_adults'],
            'max_children' => (int) $input['max_children'],
            'base_price' => (float) $input['base_price'],
            'status' => (string) $input['status'],
            'images' => json_encode($images),
        ];

        if ($existing === null) {
            $data['owner_role'] = $ownerRole;
            $data['visibility_scope'] = 'hotel';

            return Room::create($data);
        }

        Room::updateRecord($existing['id'], $data);

        return (string) $existing['id'];
    }

    public static function delete(string $roomId, null|int|string $deletedBy): void
    {
        Room::softDelete($roomId, $deletedBy);
    }

    /**
     * A room carries at most one image today (the schema's `images`
     * JSON column stays an array for forward-compatibility with a
     * real gallery later, mirroring the hotel hero_image pattern: a
     * fresh upload replaces it, no upload keeps whatever was there.
     *
     * @param array<string, mixed>|null $existing
     * @return array<int, string>
     */
    private static function resolveImages(?array $existing, ?string $newImagePath): array
    {
        if ($newImagePath !== null) {
            return [$newImagePath];
        }

        if ($existing === null || empty($existing['images'])) {
            return [];
        }

        $decoded = json_decode((string) $existing['images'], true);

        return is_array($decoded) ? $decoded : [];
    }
}
