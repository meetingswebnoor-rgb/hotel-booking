<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Hotel lifecycle operations that touch more than the hotels row
 * itself. Deleting a hotel is a soft delete that cascades to every
 * table that belongs to it — rooms, bookings, guest invoices, staff
 * assignments (user_hotels), and rate plans — so none of those show
 * up as orphaned "still active" records once their hotel is gone.
 */
final class HotelService
{
    private const CASCADE_TABLES = ['rooms', 'bookings', 'guest_invoices', 'user_hotels', 'rate_plans'];

    public static function delete(string $hotelId, null|int|string $deletedBy): void
    {
        $deletedByStr = $deletedBy === null ? null : (string) $deletedBy;

        Database::softDelete('hotels', $hotelId, $deletedByStr);

        foreach (self::CASCADE_TABLES as $table) {
            Database::query(
                "UPDATE {$table} SET is_deleted = 1, deleted_at = ?, deleted_by = ? WHERE hotel_id = ? AND is_deleted = 0",
                [date('Y-m-d H:i:s'), $deletedByStr, $hotelId]
            );
        }
    }
}
