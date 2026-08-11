<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Global topbar search. Only hotels are meaningfully searchable so
 * far (the one real, browsable entity in the schema) — extend this
 * as bookings/users/etc. get their own list views.
 */
final class SearchController
{
    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return Response::json(['results' => []]);
        }

        $hotelIds = Auth::hasGlobalHotelAccess() ? null : Auth::hotelIds();

        if ($hotelIds === []) {
            return Response::json(['results' => []]);
        }

        $like = '%' . $query . '%';

        if ($hotelIds === null) {
            $rows = Database::query(
                'SELECT id, name, city FROM hotels WHERE is_deleted = 0 AND (name LIKE ? OR city LIKE ?) ORDER BY name LIMIT 8',
                [$like, $like]
            )->fetchAll();
        } else {
            $placeholders = implode(', ', array_fill(0, count($hotelIds), '?'));
            $rows = Database::query(
                "SELECT id, name, city FROM hotels WHERE is_deleted = 0 AND id IN ({$placeholders}) AND (name LIKE ? OR city LIKE ?) ORDER BY name LIMIT 8",
                [...$hotelIds, $like, $like]
            )->fetchAll();
        }

        $results = array_map(static fn (array $hotel): array => [
            'type' => 'hotel',
            'label' => $hotel['name'],
            'meta' => $hotel['city'] ?? '',
        ], $rows);

        return Response::json(['results' => $results]);
    }
}
