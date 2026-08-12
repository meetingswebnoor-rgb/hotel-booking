<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * The public-facing counterpart to the admin Hotel Management module —
 * a directory (GET /explore) and per-hotel page (GET /hotel/{slug}) for
 * anyone, logged in or not. Deliberately separate from
 * App\Controllers\HotelController: that one is auth-scoped management
 * (edit/delete/rooms/rate plans/staff), this one only ever reads active,
 * non-deleted hotels and never exposes anything financial. No online
 * booking flow exists yet (see README "What's next") — the hotel page's
 * CTA is an enquiry, not a reservation form.
 */
final class PublicHotelController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $sql = "SELECT id, name, slug, city, country, hero_image,
                       (SELECT COUNT(*) FROM rooms r WHERE r.hotel_id = h.id AND r.is_deleted = 0) AS room_count,
                       (SELECT MIN(base_price) FROM rooms r WHERE r.hotel_id = h.id AND r.is_deleted = 0) AS from_price
                FROM hotels h
                WHERE h.is_deleted = 0 AND h.status = 'active'";
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (h.name LIKE ? OR h.city LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like];
        }

        $sql .= ' ORDER BY h.name';
        $hotels = Database::query($sql, $params)->fetchAll();

        $html = view('public/hotels-index', [
            'title' => 'Browse Hotels — Hotezo',
            'description' => 'Explore hotels on the Hotezo platform — direct booking, transparent pricing, no hidden fees.',
            'hotels' => $hotels,
            'search' => $search,
        ], 'public');

        return Response::html($html);
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->param('slug');
        $hotel = Database::first('hotels', ['slug' => $slug, 'is_deleted' => 0, 'status' => 'active']);

        if ($hotel === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        $rooms = Database::query(
            'SELECT room_type, MIN(base_price) AS from_price, COUNT(*) AS count
             FROM rooms WHERE hotel_id = ? AND is_deleted = 0
             GROUP BY room_type ORDER BY from_price',
            [$hotel['id']]
        )->fetchAll();

        $html = view('public/hotels-show', [
            'title' => $hotel['name'] . ' — Hotezo',
            'description' => trim($hotel['name'] . ' in ' . (string) ($hotel['city'] ?? '') . ' — view rooms and enquire on Hotezo.'),
            'hotel' => $hotel,
            'roomTypes' => $rooms,
            'gallery' => !empty($hotel['gallery']) ? (json_decode((string) $hotel['gallery'], true) ?: []) : [],
        ], 'public');

        return Response::html($html);
    }
}
