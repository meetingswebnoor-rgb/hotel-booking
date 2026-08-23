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

    private const STATUS_OPTIONS = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'checked_in' => 'Checked In',
        'checked_out' => 'Checked Out',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
        'no_show' => 'No Show',
    ];

    private const PAYMENT_STATUS_OPTIONS = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'hold' => 'Hold',
        'disputed' => 'Disputed',
    ];

    private const PER_PAGE_OPTIONS = [25, 50, 100];

    /**
     * Renders the filter bar + skeleton shell only. GET /bookings/data
     * (below) does the actual work once the page's JS calls it.
     */
    public function index(Request $request): Response
    {
        if (!can('bookings', 'view')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $html = view('admin/bookings/index', [
            'title' => 'Bookings',
            'active' => 'bookings',
            'pageTitle' => 'Bookings',
            'user' => Auth::user(),
            'canCreate' => can('bookings', 'create'),
            'canViewReports' => can('reports', 'view'),
            'hotels' => $this->filterableHotels($request),
            'otas' => Database::all('otas', ['is_deleted' => 0, 'status' => 'active'], '*', 'name'),
            'statusOptions' => self::STATUS_OPTIONS,
            'paymentStatusOptions' => self::PAYMENT_STATUS_OPTIONS,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ], 'admin');

        return Response::html($html);
    }

    /**
     * GET /bookings/data — the paginated, filtered JSON the list page
     * fetches. Filters combine (AND); hotel scope is the same
     * "narrow, never widen" pattern as the dashboard's drill-down.
     */
    public function data(Request $request): Response
    {
        if (!can('bookings', 'view')) {
            return Response::json(['rows' => [], 'total' => 0], 403);
        }

        $hotelIds = $this->effectiveHotelIds($request);
        $canViewReports = can('reports', 'view');
        $filters = $this->parseFilters($request);

        $page = max(1, (int) $request->query('page', 1));
        $requestedPerPage = (int) $request->query('per_page', 25);
        $perPage = in_array($requestedPerPage, self::PER_PAGE_OPTIONS, true) ? $requestedPerPage : 25;
        $offset = ($page - 1) * $perPage;

        [$where, $params] = $this->buildFilterWhere($hotelIds, $filters);

        $total = (int) Database::query("SELECT COUNT(*) FROM bookings b WHERE {$where}", $params)->fetchColumn();

        $earningSelect = $canViewReports ? ', b.hotel_earning' : '';
        $sql = "SELECT b.id, b.booking_id, b.guest_name, b.guest_mobile, b.checkin_date, b.checkout_date,
                       b.nights, b.total_room_rent, b.status, b.ota_payment_status, b.adults, b.children,
                       h.name AS hotel_name, o.name AS ota_name{$earningSelect}
                FROM bookings b
                JOIN hotels h ON h.id = b.hotel_id
                LEFT JOIN otas o ON o.id = b.ota_id
                WHERE {$where}
                ORDER BY b.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $rows = Database::query($sql, $params)->fetchAll();

        $pageRevenue = 0.0;
        $pageGuests = 0;

        foreach ($rows as $row) {
            $pageRevenue += (float) $row['total_room_rent'];
            $pageGuests += (int) $row['adults'] + (int) $row['children'];
        }

        return Response::json([
            'rows' => array_map(static function (array $row) use ($canViewReports): array {
                $item = [
                    'id' => $row['id'],
                    'booking_id' => $row['booking_id'],
                    'guest_name' => $row['guest_name'],
                    'guest_mobile' => $row['guest_mobile'],
                    'hotel_name' => $row['hotel_name'],
                    'ota_name' => $row['ota_name'] ?? 'Direct',
                    'checkin_date' => $row['checkin_date'],
                    'checkout_date' => $row['checkout_date'],
                    'nights' => (int) $row['nights'],
                    'total_room_rent' => (float) $row['total_room_rent'],
                    'status' => $row['status'],
                    'ota_payment_status' => $row['ota_payment_status'],
                    'guests' => (int) $row['adults'] + (int) $row['children'],
                ];

                if ($canViewReports) {
                    $item['hotel_earning'] = (float) $row['hotel_earning'];
                }

                return $item;
            }, $rows),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
            'can_view_reports' => $canViewReports,
            'page_stats' => [
                'count' => count($rows),
                'revenue' => round($pageRevenue, 2),
                'guests' => $pageGuests,
            ],
        ]);
    }

    /**
     * GET /bookings/{id}/detail — the full record for the row-click
     * drawer. The commission/tax breakdown is only included when the
     * caller has reports access, same gate as the dashboard.
     */
    public function detail(Request $request): Response
    {
        $booking = Database::first('bookings', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($booking === null) {
            return Response::json(['message' => 'Not found.'], 404);
        }

        if (!can('bookings', 'view', $booking['hotel_id'])) {
            return Response::json(['message' => 'Forbidden.'], 403);
        }

        $hotel = Database::first('hotels', ['id' => $booking['hotel_id']]);
        $ota = $booking['ota_id'] !== null ? Database::first('otas', ['id' => $booking['ota_id']]) : null;
        $canViewReports = can('reports', 'view', $booking['hotel_id']);

        $payload = [
            'id' => $booking['id'],
            'booking_id' => $booking['booking_id'],
            'guest_name' => $booking['guest_name'],
            'guest_mobile' => $booking['guest_mobile'],
            'guest_email' => $booking['guest_email'],
            'hotel_name' => $hotel['name'] ?? '',
            'ota_name' => $ota['name'] ?? 'Direct',
            'source' => $booking['source'],
            'booking_date' => $booking['booking_date'],
            'checkin_date' => $booking['checkin_date'],
            'checkout_date' => $booking['checkout_date'],
            'nights' => (int) $booking['nights'],
            'adults' => (int) $booking['adults'],
            'children' => (int) $booking['children'],
            'rooms' => json_decode((string) $booking['rooms'], true) ?: [],
            'total_room_rent' => (float) $booking['total_room_rent'],
            'status' => $booking['status'],
            'ota_payment_status' => $booking['ota_payment_status'],
            'payment_remarks' => $booking['payment_remarks'],
            'internal_notes' => $booking['internal_notes'],
            'can_view_reports' => $canViewReports,
            'can_edit' => can('bookings', 'edit', $booking['hotel_id']),
            'edit_url' => route('bookings.edit', ['id' => $booking['id']]),
            'voucher_url' => route('bookings.voucher', ['id' => $booking['id']]),
        ];

        if ($canViewReports) {
            $payload['financials'] = [
                'hotel_gst' => (float) $booking['hotel_gst'],
                'tds' => (float) $booking['tds'],
                'tcs' => (float) $booking['tcs'],
                'ota_commission' => (float) $booking['ota_commission'],
                'hotezo_commission' => (float) $booking['hotezo_commission'],
                'gst_on_commission' => (float) $booking['gst_on_commission'],
                'total_commission_taxes' => (float) $booking['total_commission_taxes'],
                'hotel_earning' => (float) $booking['hotel_earning'],
                'hotel_collection' => (float) $booking['hotel_collection'],
                'hotezo_collection' => (float) $booking['hotezo_collection'],
            ];
        }

        return Response::json($payload);
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
            'statusOptions' => self::STATUS_OPTIONS,
            'paymentStatusOptions' => self::PAYMENT_STATUS_OPTIONS,
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
            'statusOptions' => self::STATUS_OPTIONS,
            'paymentStatusOptions' => self::PAYMENT_STATUS_OPTIONS,
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

        // Fetched up front (rather than where $source/$calc need it
        // further down) because payment-status validation below needs
        // this OTA's custom_payment_statuses too — an OTA can define
        // extra labels (e.g. "Escrow Hold") beyond the base set, which
        // the booking form's JS merges into the dropdown for that OTA.
        $otaIdInput = $request->input('ota_id');
        $otaId = is_string($otaIdInput) && $otaIdInput !== '' ? $otaIdInput : null;
        $ota = $otaId !== null ? Database::first('otas', ['id' => $otaId]) : null;

        $rules = [
            'guest_name' => 'required|max:150',
            'guest_mobile' => 'required|min:7|max:20',
            'booking_date' => 'required',
            'checkin_date' => 'required',
            'checkout_date' => 'required',
            'status' => 'required|in:' . implode(',', array_keys(self::STATUS_OPTIONS)),
        ];
        $errors = Validator::make($request->all(), $rules)->errors();

        $allowedPaymentStatuses = array_keys(self::PAYMENT_STATUS_OPTIONS);
        if ($ota !== null && !empty($ota['custom_payment_statuses'])) {
            $custom = json_decode((string) $ota['custom_payment_statuses'], true);
            if (is_array($custom)) {
                $allowedPaymentStatuses = array_values(array_unique([...$allowedPaymentStatuses, ...$custom]));
            }
        }

        $otaPaymentStatus = (string) $request->input('ota_payment_status', '');
        if ($otaPaymentStatus === '' || !in_array($otaPaymentStatus, $allowedPaymentStatuses, true)) {
            $errors['ota_payment_status'][] = 'Please choose a valid payment status.';
        }

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
     * The permission-based scope, further narrowed (never widened) by
     * an optional ?hotel_id= filter — same pattern as the dashboard's
     * drill-down.
     *
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
     * Hotels for the filter bar's own dropdown — intersected with
     * whatever the topbar's global hotel filter already narrowed to
     * (HotelScopeMiddleware), so the list's filter never offers a
     * hotel that would just come back with zero rows.
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
     * @return array{ota_id: ?string, status: ?string, date_from: ?string, date_to: ?string, q: ?string}
     */
    private function parseFilters(Request $request): array
    {
        $otaId = (string) $request->query('ota_id', '');
        $status = (string) $request->query('status', '');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $q = trim((string) $request->query('q', ''));

        return [
            'ota_id' => $otaId !== '' ? $otaId : null,
            'status' => array_key_exists($status, self::STATUS_OPTIONS) ? $status : null,
            'date_from' => $dateFrom !== '' ? $dateFrom : null,
            'date_to' => $dateTo !== '' ? $dateTo : null,
            'q' => $q !== '' ? $q : null,
        ];
    }

    /**
     * Filters combine with AND. Date range filters on checkin_date —
     * the natural "what's happening when" axis for an operational
     * list (as opposed to booking_date, "when was this booked").
     *
     * @param array<int, string>|null $hotelIds
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildFilterWhere(?array $hotelIds, array $filters): array
    {
        [$scopeSql, $params] = Database::scopeCondition($hotelIds, 'b.hotel_id');
        $where = "b.is_deleted = 0{$scopeSql}";

        if ($filters['ota_id'] !== null) {
            $where .= ' AND b.ota_id = ?';
            $params[] = $filters['ota_id'];
        }

        if ($filters['status'] !== null) {
            $where .= ' AND b.status = ?';
            $params[] = $filters['status'];
        }

        if ($filters['date_from'] !== null) {
            $where .= ' AND b.checkin_date >= ?';
            $params[] = $filters['date_from'];
        }

        if ($filters['date_to'] !== null) {
            $where .= ' AND b.checkin_date <= ?';
            $params[] = $filters['date_to'];
        }

        if ($filters['q'] !== null) {
            $where .= ' AND (b.guest_name LIKE ? OR b.booking_id LIKE ? OR b.guest_mobile LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return [$where, $params];
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
