<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Booking;
use App\Services\BookingCalculator;

/**
 * The booking entry form — create, edit, list, printable voucher, and
 * the two JSON endpoints the form's JS depends on (async booking-ID
 * uniqueness check, per-hotel room/rate-plan lookup). Every money
 * figure is recomputed server-side via BookingCalculator on save —
 * whatever the client's live preview showed is never trusted.
 */
final class BookingController
{
    private const ROOM_TYPES = ['Single', 'Double', 'Suite', 'Deluxe'];

    public function index(Request $request): Response
    {
        $hotelIds = $request->scope('hotel_ids');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'b.hotel_id');

        $total = (int) Database::query(
            "SELECT COUNT(*) FROM bookings b WHERE b.is_deleted = 0 {$scopeSql}",
            $scopeParams
        )->fetchColumn();

        $sql = "SELECT b.id, b.booking_id, b.guest_name, b.checkin_date, b.checkout_date, b.status,
                       b.total_room_rent, h.name AS hotel_name
                FROM bookings b JOIN hotels h ON h.id = b.hotel_id
                WHERE b.is_deleted = 0 {$scopeSql}
                ORDER BY b.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $bookings = Database::query($sql, $scopeParams)->fetchAll();

        $html = view('admin/bookings/index', [
            'title' => 'Bookings',
            'active' => 'bookings',
            'pageTitle' => 'Bookings',
            'user' => Auth::user(),
            'bookings' => $bookings,
            'canCreate' => can('bookings', 'create'),
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ], 'admin');

        return Response::html($html);
    }

    public function create(Request $request): Response
    {
        if (!can('bookings', 'create')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $html = view('admin/bookings/form', [
            'title' => 'New Booking',
            'active' => 'bookings',
            'pageTitle' => 'New Booking',
            'user' => Auth::user(),
            'booking' => null,
            'hotels' => $this->allowedHotels(),
            'otas' => Database::all('otas', ['is_deleted' => 0, 'status' => 'active'], '*', 'name'),
            'suggestedBookingId' => $this->suggestedBookingId(),
            'formAction' => route('bookings.store'),
        ], 'admin');

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function edit(Request $request): Response
    {
        $booking = Database::first('bookings', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($booking === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        if (!can('bookings', 'edit', $booking['hotel_id'])) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $hotel = Database::first('hotels', ['id' => $booking['hotel_id']]);

        $html = view('admin/bookings/form', [
            'title' => 'Edit Booking',
            'active' => 'bookings',
            'pageTitle' => 'Edit ' . $booking['booking_id'],
            'user' => Auth::user(),
            'booking' => $booking,
            'hotels' => $hotel !== null ? [$hotel] : [],
            'otas' => Database::all('otas', ['is_deleted' => 0, 'status' => 'active'], '*', 'name'),
            'suggestedBookingId' => $booking['booking_id'],
            'formAction' => route('bookings.update', ['id' => $booking['id']]),
        ], 'admin');

        return Response::html($html);
    }

    public function update(Request $request): Response
    {
        $booking = Database::first('bookings', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($booking === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        return $this->save($request, $booking);
    }

    public function voucher(Request $request): Response
    {
        $booking = Database::first('bookings', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($booking === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        if (!can('bookings', 'view', $booking['hotel_id'])) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $hotel = Database::first('hotels', ['id' => $booking['hotel_id']]);
        $ota = $booking['ota_id'] !== null ? Database::first('otas', ['id' => $booking['ota_id']]) : null;
        $rooms = json_decode((string) $booking['rooms'], true) ?: [];

        $now = date('Y-m-d H:i:s');
        Database::insert('booking_voucher_logs', [
            'id' => uuid(),
            'booking_id' => $booking['id'],
            'action' => 'generated',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => Auth::id(),
        ]);

        $html = view('admin/bookings/voucher', [
            'title' => 'Voucher — ' . $booking['booking_id'],
            'booking' => $booking,
            'hotel' => $hotel,
            'ota' => $ota,
            'rooms' => $rooms,
        ], 'print');

        return Response::html($html);
    }

    /**
     * GET /bookings/check-id?booking_id=X[&exclude_id=Y] — debounced
     * async uniqueness check for the booking-ID field.
     */
    public function checkId(Request $request): Response
    {
        $bookingId = trim((string) $request->query('booking_id', ''));
        $excludeId = $request->query('exclude_id');

        if ($bookingId === '') {
            return Response::json(['available' => false]);
        }

        $existing = Database::first('bookings', ['booking_id' => $bookingId]);
        $available = $existing === null || ($excludeId !== null && $existing['id'] === $excludeId);

        return Response::json(['available' => $available]);
    }

    /**
     * GET /bookings/rooms?hotel_id=X — room types (with capacity
     * limits, aggregated across that hotel's physical rooms) and rate
     * plans, for the room-line repeater's dropdowns.
     */
    public function roomsForHotel(Request $request): Response
    {
        $hotelId = (string) $request->query('hotel_id', '');

        if ($hotelId === '' || !can('bookings', 'create', $hotelId)) {
            return Response::json(['room_types' => [], 'rate_plans' => []], 403);
        }

        $rooms = Database::all('rooms', ['hotel_id' => $hotelId, 'is_deleted' => 0]);
        $roomTypes = [];

        foreach ($rooms as $room) {
            $type = $room['room_type'];

            if (!isset($roomTypes[$type])) {
                $roomTypes[$type] = [
                    'room_type' => $type,
                    'max_adults' => 0,
                    'max_children' => 0,
                    'base_price' => (float) $room['base_price'],
                    'count' => 0,
                ];
            }

            $roomTypes[$type]['max_adults'] = max($roomTypes[$type]['max_adults'], (int) $room['max_adults']);
            $roomTypes[$type]['max_children'] = max($roomTypes[$type]['max_children'], (int) $room['max_children']);
            $roomTypes[$type]['count']++;
        }

        $ratePlans = Database::all('rate_plans', ['hotel_id' => $hotelId, 'is_deleted' => 0]);

        return Response::json([
            'room_types' => array_values($roomTypes),
            'rate_plans' => array_map(static fn (array $rp): array => [
                'id' => $rp['id'],
                'plan_name' => $rp['plan_name'],
                'room_type' => $rp['room_type'],
                'season' => $rp['season'],
                'base_price' => (float) $rp['base_price'],
            ], $ratePlans),
        ]);
    }

    private function save(Request $request, ?array $existing): Response
    {
        $redirectBack = $existing !== null
            ? route('bookings.edit', ['id' => $existing['id']])
            : route('bookings.create');

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        // The hotel is locked once a booking exists — the edit form
        // renders it disabled, so a mismatch here only happens on a
        // tampered request, in which case we just keep the real one.
        $hotelId = $existing !== null ? (string) $existing['hotel_id'] : (string) $request->input('hotel_id', '');
        $action = $existing === null ? 'create' : 'edit';

        if ($hotelId === '' || !can('bookings', $action, $hotelId)) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $rules = [
            'guest_name' => 'required|max:150',
            'guest_mobile' => 'required|min:7|max:20',
            'booking_date' => 'required',
            'checkin_date' => 'required',
            'checkout_date' => 'required',
            'status' => 'required|in:pending,confirmed,checked_in,checked_out,cancelled,no_show,rejected',
            'ota_payment_status' => 'required|in:pending,paid,hold,disputed',
        ];
        $errors = Validator::make($request->all(), $rules)->errors();

        $checkin = (string) $request->input('checkin_date', '');
        $checkout = (string) $request->input('checkout_date', '');

        if ($checkin !== '' && $checkout !== '' && strtotime($checkout) <= strtotime($checkin)) {
            $errors['checkout_date'][] = 'Check-out must be after check-in.';
        }

        $bookingIdValue = trim((string) $request->input('booking_id', ''));

        if ($bookingIdValue === '') {
            $errors['booking_id'][] = 'Booking ID is required.';
        } else {
            $dupe = Database::first('bookings', ['booking_id' => $bookingIdValue]);
            if ($dupe !== null && ($existing === null || $dupe['id'] !== $existing['id'])) {
                $errors['booking_id'][] = 'This booking ID is already in use.';
            }
        }

        $roomLinesInput = $request->input('rooms', []);
        $roomLines = $this->normalizeRoomLines(is_array($roomLinesInput) ? $roomLinesInput : []);

        if ($roomLines === []) {
            $errors['rooms'][] = 'Add at least one room.';
        }

        if ($errors !== []) {
            Session::flash('error', 'Please fix the errors below.');
            Session::flash('_old_input', $request->all());
            Session::flash('_form_errors', $errors);

            return Response::redirect($redirectBack);
        }

        $hotel = Database::first('hotels', ['id' => $hotelId, 'is_deleted' => 0]);

        if ($hotel === null) {
            Session::flash('error', 'Selected hotel could not be found.');

            return Response::redirect($redirectBack);
        }

        $otaIdInput = $request->input('ota_id');
        $otaId = is_string($otaIdInput) && $otaIdInput !== '' ? $otaIdInput : null;
        $ota = $otaId !== null ? Database::first('otas', ['id' => $otaId]) : null;
        $source = $this->deriveSource($ota);

        $nights = BookingCalculator::nights($checkin, $checkout);
        $calc = BookingCalculator::calculate(
            $roomLines,
            $nights,
            $ota !== null ? (float) $ota['commission_pct'] : 0.0,
            (float) $hotel['commission_pct'],
            $source
        );

        $status = (string) $request->input('status');
        $now = date('Y-m-d H:i:s');
        $previousStatus = $existing['status'] ?? null;

        $data = [
            'booking_id' => $bookingIdValue,
            'hotel_id' => $hotelId,
            'ota_id' => $otaId,
            'guest_name' => sanitize((string) $request->input('guest_name')),
            'guest_mobile' => sanitize((string) $request->input('guest_mobile')),
            'guest_email' => $this->nullableInput($request, 'guest_email'),
            'booking_date' => (string) $request->input('booking_date'),
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'nights' => $nights,
            'rooms' => json_encode($roomLines),
            'adults' => array_sum(array_map(static fn (array $l): int => $l['adults'] * $l['quantity'], $roomLines)),
            'children' => array_sum(array_map(static fn (array $l): int => $l['children'] * $l['quantity'], $roomLines)),
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
            'ota_payment_status' => (string) $request->input('ota_payment_status'),
            'payment_remarks' => $this->nullableInput($request, 'payment_remarks'),
            'internal_notes' => $this->nullableInput($request, 'internal_notes'),
            'source' => $source,
        ];

        if ($status === 'checked_in' && $previousStatus !== 'checked_in') {
            $data['checked_in_at'] = $now;
        }

        if ($status === 'checked_out' && $previousStatus !== 'checked_out') {
            $data['checked_out_at'] = $now;
        }

        if ($status === 'cancelled' && $previousStatus !== 'cancelled') {
            $data['cancelled_at'] = $now;
        }

        if ($existing === null) {
            $data['owner_role'] = Auth::roleName();
            $data['visibility_scope'] = 'hotel';
            $id = Booking::create($data);
            $auditAction = 'booking.created';
            Session::flash('success', 'Booking created.');
        } else {
            $id = $existing['id'];
            Booking::updateRecord($id, $data);
            $auditAction = 'booking.updated';
            Session::flash('success', 'Booking updated.');
        }

        Database::insert('audit_logs', [
            'id' => uuid(),
            'user_id' => Auth::id(),
            'action' => $auditAction,
            'entity_type' => 'bookings',
            'entity_id' => $id,
            'new_values' => json_encode($data),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Module 16 will also queue guest/hotel notifications from this
        // same point once the email pipeline exists.

        return Response::redirect(route('bookings.edit', ['id' => $id]));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRoomLines(array $raw): array
    {
        $lines = [];

        foreach ($raw as $line) {
            if (!is_array($line)) {
                continue;
            }

            $roomType = $line['room_type'] ?? null;
            $quantity = (int) ($line['quantity'] ?? 0);
            $nightlyRate = is_numeric($line['nightly_rate'] ?? null) ? (float) $line['nightly_rate'] : -1;

            if (!in_array($roomType, self::ROOM_TYPES, true) || $quantity < 1 || $nightlyRate < 0) {
                continue;
            }

            $lines[] = [
                'room_type' => $roomType,
                'rate_plan_id' => !empty($line['rate_plan_id']) ? (string) $line['rate_plan_id'] : null,
                'adults' => max(1, (int) ($line['adults'] ?? 1)),
                'children' => max(0, (int) ($line['children'] ?? 0)),
                'quantity' => $quantity,
                'nightly_rate' => $nightlyRate,
            ];
        }

        return $lines;
    }

    private function deriveSource(?array $ota): string
    {
        if ($ota === null) {
            return 'online';
        }

        return match ($ota['name']) {
            'Walk-in' => 'walkin',
            'Direct Booking' => 'online',
            default => 'ota',
        };
    }

    private function nullableInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return sanitize($value);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allowedHotels(): array
    {
        if (Auth::hasGlobalHotelAccess()) {
            return Database::all('hotels', ['is_deleted' => 0], 'id, name, commission_pct', 'name');
        }

        $ids = Auth::hotelIds();

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        return Database::query(
            "SELECT id, name, commission_pct FROM hotels WHERE is_deleted = 0 AND id IN ({$placeholders}) ORDER BY name",
            $ids
        )->fetchAll();
    }

    private function suggestedBookingId(): string
    {
        $prefix = 'HTZ-' . date('Y') . '-';
        $last = Database::query(
            'SELECT booking_id FROM bookings WHERE booking_id LIKE ? ORDER BY booking_id DESC LIMIT 1',
            [$prefix . '%']
        )->fetchColumn();

        $next = 1;

        if ($last !== false && preg_match('/(\d+)$/', (string) $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
