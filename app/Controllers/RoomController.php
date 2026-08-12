<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\FileUpload;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\RoomService;

/**
 * The standalone, cross-hotel Rooms page — same rooms, same validation,
 * same App\Services\RoomService as the hotel hub's Rooms tab
 * (App\Controllers\HotelController), just browsable/manageable across
 * every hotel the caller has access to instead of one at a time.
 */
final class RoomController
{
    public function index(Request $request): Response
    {
        if (!can('rooms', 'view')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $hotelIds = $this->effectiveHotelIds($request);
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'r.hotel_id');

        $sql = "SELECT r.*, h.name AS hotel_name
                FROM rooms r
                JOIN hotels h ON h.id = r.hotel_id
                WHERE r.is_deleted = 0{$scopeSql}
                ORDER BY h.name, r.room_number";
        $rooms = Database::query($sql, $scopeParams)->fetchAll();

        $html = view('admin/rooms/index', [
            'title' => 'Rooms',
            'active' => 'rooms',
            'pageTitle' => 'Rooms',
            'user' => Auth::user(),
            'rooms' => $rooms,
            'filterHotels' => $this->filterableHotels($request),
            'addableHotels' => $this->allowedHotels(),
            'selectedHotelId' => $request->query('hotel_id'),
            'roomTypes' => RoomService::ROOM_TYPES,
            'roomStatuses' => RoomService::STATUSES,
            'canCreate' => can('rooms', 'create'),
        ], 'admin');

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function update(Request $request): Response
    {
        $room = Database::first('rooms', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($room === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        return $this->save($request, $room);
    }

    public function destroy(Request $request): Response
    {
        $room = Database::first('rooms', ['id' => $request->param('id'), 'is_deleted' => 0]);
        $redirectBack = $this->redirectBack($request);

        if ($room === null) {
            return Response::redirect($redirectBack);
        }

        if (!can('rooms', 'delete', $room['hotel_id'])) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        RoomService::delete($room['id'], Auth::id());
        Session::flash('success', 'Room removed.');

        return Response::redirect($redirectBack);
    }

    private function save(Request $request, ?array $existing): Response
    {
        $redirectBack = $this->redirectBack($request);

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        // The hotel is fixed once a room exists (same rule as the
        // booking form's Property field) — only a brand-new room's
        // hotel comes from the submitted form.
        $hotelId = $existing !== null ? (string) $existing['hotel_id'] : (string) $request->input('hotel_id', '');
        $action = $existing === null ? 'create' : 'edit';

        if ($hotelId === '' || !can('rooms', $action, $hotelId)) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $errors = RoomService::validate($request->all(), $hotelId, $existing['id'] ?? null);

        if ($errors !== []) {
            Session::flash('error', 'Please fix the room details — ' . implode(' ', array_merge(...array_values($errors))));

            return Response::redirect($redirectBack);
        }

        $imagePath = FileUpload::storeImage($request->file('room_image'), 'hotels/' . $hotelId . '/rooms');
        RoomService::save($request->all(), $hotelId, $existing, $imagePath, Auth::roleName());
        Session::flash('success', $existing === null ? 'Room added.' : 'Room updated.');

        return Response::redirect($redirectBack);
    }

    /**
     * Every form on this page carries the page's current ?hotel_id=
     * filter in a hidden field, so add/edit/delete round-trips land
     * back on the same filtered view instead of resetting it.
     */
    private function redirectBack(Request $request): string
    {
        $hotelId = $request->input('_redirect_hotel_id');
        $base = route('rooms.index');

        return is_string($hotelId) && $hotelId !== '' ? $base . '?hotel_id=' . urlencode($hotelId) : $base;
    }

    /**
     * @return array<int, string>|null
     */
    private function effectiveHotelIds(Request $request): ?array
    {
        $scoped = $request->scope('hotel_ids');
        $requested = $request->query('hotel_id');

        if ($requested === null || $requested === '') {
            return $scoped;
        }

        if ($scoped === null) {
            return [$requested];
        }

        return in_array($requested, $scoped, true) ? [$requested] : $scoped;
    }

    /**
     * Hotels for the page's own filter dropdown — intersected with
     * whatever the topbar's global hotel filter already narrowed to,
     * same pattern as BookingController::filterableHotels().
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterableHotels(Request $request): array
    {
        $scopeIds = $request->scope('hotel_ids');
        $allowed = $this->allowedHotels();

        if ($scopeIds === null) {
            return $allowed;
        }

        return array_values(array_filter($allowed, static fn (array $h): bool => in_array($h['id'], $scopeIds, true)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allowedHotels(): array
    {
        if (Auth::hasGlobalHotelAccess()) {
            return Database::all('hotels', ['is_deleted' => 0], 'id, name', 'name');
        }

        $ids = Auth::hotelIds();

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        return Database::query(
            "SELECT id, name FROM hotels WHERE is_deleted = 0 AND id IN ({$placeholders}) ORDER BY name",
            $ids
        )->fetchAll();
    }
}
