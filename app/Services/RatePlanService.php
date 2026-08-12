<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;
use App\Models\RatePlan;

/**
 * The one place rate-plan validation + save logic lives, so the hotel
 * hub's Rate Plans tab (App\Controllers\HotelController) and the
 * standalone Rate Plans page (App\Controllers\RatePlanController)
 * share identical behavior instead of each re-implementing it.
 */
final class RatePlanService
{
    public const ROOM_TYPES = ['Single', 'Double', 'Suite', 'Deluxe'];
    public const SEASONS = ['Peak', 'Off-Peak', 'Regular'];

    /**
     * @param array<string, mixed> $input
     * @return array<string, array<int, string>> empty when valid
     */
    public static function validate(array $input): array
    {
        $rules = [
            'plan_name' => 'required|max:150',
            'room_type' => 'required|in:' . implode(',', self::ROOM_TYPES),
            'occupancy_type' => 'max:50',
            'season' => 'required|in:' . implode(',', self::SEASONS),
            'base_price' => 'required|numeric',
        ];

        return Validator::make($input, $rules)->errors();
    }

    /**
     * @param array<string, mixed> $input already-validated
     * @param array<string, mixed>|null $existing
     * @return string the rate plan id (new or existing)
     */
    public static function save(array $input, string $hotelId, ?array $existing, ?string $ownerRole): string
    {
        $data = [
            'hotel_id' => $hotelId,
            'plan_name' => sanitize((string) $input['plan_name']),
            'room_type' => (string) $input['room_type'],
            'occupancy_type' => self::nullableString($input['occupancy_type'] ?? null),
            'season' => (string) $input['season'],
            'base_price' => (float) $input['base_price'],
        ];

        if ($existing === null) {
            $data['owner_role'] = $ownerRole;
            $data['visibility_scope'] = 'hotel';

            return RatePlan::create($data);
        }

        RatePlan::updateRecord($existing['id'], $data);

        return (string) $existing['id'];
    }

    public static function delete(string $planId, null|int|string $deletedBy): void
    {
        RatePlan::softDelete($planId, $deletedBy);
    }

    private static function nullableString(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return sanitize($value);
    }
}
