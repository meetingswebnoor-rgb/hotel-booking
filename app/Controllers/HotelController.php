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
use App\Core\Validator;
use App\Models\Hotel;
use App\Models\RatePlan;
use App\Models\Room;
use App\Services\HotelService;

/**
 * The hotel list (glass cards) and the per-hotel management hub — a
 * single tabbed page (Details, Rooms, Rate Plans, Inventory, Bookings,
 * Guests, Invoices, Staff, Reports, Settings) driven server-side and
 * switched client-side, same as the rest of the admin shell's tabs
 * component. Deleting a hotel is a soft-delete cascade, delegated to
 * HotelService so this controller only orchestrates HTTP concerns.
 *
 * "Hotel Admin" in the product spec maps to the existing hotel_manager
 * role (level 3, hotel-scoped) — no separate role exists or is needed;
 * config/permissions.php already restricts hotel_manager to view/edit
 * on the hotels module (no create/delete), which is exactly that rule.
 */
final class HotelController
{
    private const SETTLEMENT_TYPES = ['Weekly', 'Monthly', 'On-Demand'];
    private const GST_TYPES = ['Registered', 'Unregistered'];
    private const STATUS_OPTIONS = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending_approval' => 'Pending Approval',
    ];
    private const ROOM_TYPES = ['Single', 'Double', 'Suite', 'Deluxe'];
    private const ROOM_STATUSES = ['Available', 'Occupied', 'Maintenance'];
    private const SEASONS = ['Peak', 'Off-Peak', 'Regular'];

    // Mirrors BookingController's option sets — the embedded Bookings
    // tab (see partials/admin/bookings-embed.php) needs the same
    // dropdowns as the standalone list.
    private const BOOKING_STATUS_OPTIONS = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'checked_in' => 'Checked In',
        'checked_out' => 'Checked Out',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
        'no_show' => 'No Show',
    ];
    private const PAYMENT_STATUS_OPTIONS = ['pending' => 'Pending', 'paid' => 'Paid', 'hold' => 'Hold', 'disputed' => 'Disputed'];
    private const PER_PAGE_OPTIONS = [25, 50, 100];

    public function index(Request $request): Response
    {
        if (!can('hotels', 'view')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $hotelIds = $request->scope('hotel_ids');
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'h.id');

        $sql = "SELECT h.id, h.name, h.slug, h.city, h.country, h.status, h.hero_image, h.commission_pct,
                       (SELECT COUNT(*) FROM rooms r WHERE r.hotel_id = h.id AND r.is_deleted = 0) AS room_count,
                       (SELECT COUNT(*) FROM bookings b WHERE b.hotel_id = h.id AND b.is_deleted = 0) AS booking_count
                FROM hotels h
                WHERE h.is_deleted = 0{$scopeSql}
                ORDER BY h.name";
        $hotels = Database::query($sql, $scopeParams)->fetchAll();

        $html = view('admin/hotels/index', [
            'title' => 'Hotels',
            'active' => 'hotels',
            'pageTitle' => 'Hotels',
            'user' => Auth::user(),
            'hotels' => $hotels,
            'canCreate' => can('hotels', 'create'),
        ], 'admin');

        return Response::html($html);
    }

    public function create(Request $request): Response
    {
        if (!can('hotels', 'create')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $html = view('admin/hotels/create', [
            'title' => 'New Hotel',
            'active' => 'hotels',
            'pageTitle' => 'New Hotel',
            'user' => Auth::user(),
            'settlementTypes' => self::SETTLEMENT_TYPES,
            'gstTypes' => self::GST_TYPES,
            'statusOptions' => self::STATUS_OPTIONS,
        ], 'admin');

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function update(Request $request): Response
    {
        $hotel = Database::first('hotels', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($hotel === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        return $this->save($request, $hotel);
    }

    public function show(Request $request): Response
    {
        $hotel = Database::first('hotels', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($hotel === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        if (!can('hotels', 'view', $hotel['id'])) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $hotelId = $hotel['id'];
        $canViewRooms = can('rooms', 'view', $hotelId);
        $canViewRatePlans = can('rate_plans', 'view', $hotelId);
        $canViewBookings = can('bookings', 'view', $hotelId);
        $canViewInvoices = can('invoices', 'view', $hotelId);
        $canViewStaff = can('users', 'view');
        $canViewReports = can('reports', 'view', $hotelId);

        $rooms = $canViewRooms
            ? Database::all('rooms', ['hotel_id' => $hotelId, 'is_deleted' => 0], '*', 'room_number')
            : [];
        $ratePlans = $canViewRatePlans
            ? Database::all('rate_plans', ['hotel_id' => $hotelId, 'is_deleted' => 0], '*', 'plan_name')
            : [];
        $guests = $canViewBookings ? $this->guestsForHotel($hotelId) : [];
        $invoices = $canViewInvoices ? $this->invoicesForHotel($hotelId) : [];
        $staff = $canViewStaff ? $this->staffForHotel($hotelId) : [];
        $bookingCount = (int) Database::query(
            'SELECT COUNT(*) FROM bookings WHERE hotel_id = ? AND is_deleted = 0',
            [$hotelId]
        )->fetchColumn();

        $html = view('admin/hotels/show', [
            'title' => $hotel['name'],
            'active' => 'hotels',
            'pageTitle' => $hotel['name'],
            'user' => Auth::user(),
            'hotel' => $hotel,
            'rooms' => $rooms,
            'ratePlans' => $ratePlans,
            'guests' => $guests,
            'invoices' => $invoices,
            'staff' => $staff,
            'bookingCount' => $bookingCount,
            'canEdit' => can('hotels', 'edit', $hotelId),
            'canDelete' => can('hotels', 'delete', $hotelId),
            'canViewRooms' => $canViewRooms,
            'canManageRooms' => can('rooms', 'create', $hotelId) || can('rooms', 'edit', $hotelId),
            'canDeleteRooms' => can('rooms', 'delete', $hotelId),
            'canViewRatePlans' => $canViewRatePlans,
            'canManageRatePlans' => can('rate_plans', 'create', $hotelId) || can('rate_plans', 'edit', $hotelId),
            'canDeleteRatePlans' => can('rate_plans', 'delete', $hotelId),
            'canViewBookings' => $canViewBookings,
            'canCreateBooking' => can('bookings', 'create', $hotelId),
            'canViewInvoices' => $canViewInvoices,
            'canViewStaff' => $canViewStaff,
            'canViewReports' => $canViewReports,
            'settlementTypes' => self::SETTLEMENT_TYPES,
            'gstTypes' => self::GST_TYPES,
            'statusOptions' => self::STATUS_OPTIONS,
            'roomTypes' => self::ROOM_TYPES,
            'roomStatuses' => self::ROOM_STATUSES,
            'seasons' => self::SEASONS,
            'otas' => $canViewBookings ? Database::all('otas', ['is_deleted' => 0, 'status' => 'active'], '*', 'name') : [],
            'bookingStatusOptions' => self::BOOKING_STATUS_OPTIONS,
            'paymentStatusOptions' => self::PAYMENT_STATUS_OPTIONS,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ], 'admin');

        return Response::html($html);
    }

    public function destroy(Request $request): Response
    {
        $hotel = Database::first('hotels', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($hotel === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        if (!can('hotels', 'delete', $hotel['id'])) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect(route('hotels.show', ['id' => $hotel['id']]));
        }

        HotelService::delete($hotel['id'], Auth::id());

        $now = date('Y-m-d H:i:s');
        Database::insert('audit_logs', [
            'id' => uuid(),
            'user_id' => Auth::id(),
            'action' => 'hotel.deleted',
            'entity_type' => 'hotels',
            'entity_id' => $hotel['id'],
            'new_values' => json_encode(['cascaded_to' => ['rooms', 'bookings', 'guest_invoices', 'user_hotels', 'rate_plans']]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Session::flash('success', "\"{$hotel['name']}\" and its rooms, bookings, invoices, and staff assignments were deleted.");

        return Response::redirect(route('hotels.index'));
    }

    public function storeRoom(Request $request): Response
    {
        return $this->saveRoom($request, null);
    }

    public function updateRoom(Request $request): Response
    {
        $room = Database::first('rooms', [
            'id' => $request->param('roomId'),
            'hotel_id' => $request->param('id'),
            'is_deleted' => 0,
        ]);

        if ($room === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        return $this->saveRoom($request, $room);
    }

    public function destroyRoom(Request $request): Response
    {
        $hotelId = (string) $request->param('id');
        $redirectBack = route('hotels.show', ['id' => $hotelId]) . '?tab=rooms';

        $room = Database::first('rooms', ['id' => $request->param('roomId'), 'hotel_id' => $hotelId, 'is_deleted' => 0]);

        if ($room === null) {
            return Response::redirect($redirectBack);
        }

        if (!can('rooms', 'delete', $hotelId)) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        Room::softDelete($room['id'], Auth::id());
        Session::flash('success', 'Room removed.');

        return Response::redirect($redirectBack);
    }

    public function storeRatePlan(Request $request): Response
    {
        return $this->saveRatePlan($request, null);
    }

    public function updateRatePlan(Request $request): Response
    {
        $plan = Database::first('rate_plans', [
            'id' => $request->param('planId'),
            'hotel_id' => $request->param('id'),
            'is_deleted' => 0,
        ]);

        if ($plan === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        return $this->saveRatePlan($request, $plan);
    }

    public function destroyRatePlan(Request $request): Response
    {
        $hotelId = (string) $request->param('id');
        $redirectBack = route('hotels.show', ['id' => $hotelId]) . '?tab=rate-plans';

        $plan = Database::first('rate_plans', ['id' => $request->param('planId'), 'hotel_id' => $hotelId, 'is_deleted' => 0]);

        if ($plan === null) {
            return Response::redirect($redirectBack);
        }

        if (!can('rate_plans', 'delete', $hotelId)) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        RatePlan::softDelete($plan['id'], Auth::id());
        Session::flash('success', 'Rate plan removed.');

        return Response::redirect($redirectBack);
    }

    private function save(Request $request, ?array $existing): Response
    {
        $redirectBack = $existing !== null
            ? route('hotels.show', ['id' => $existing['id']]) . '?tab=details'
            : route('hotels.create');

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        $action = $existing === null ? 'create' : 'edit';

        if (!can('hotels', $action, $existing['id'] ?? null)) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $rules = [
            'name' => 'required|max:191',
            'slug' => 'required|max:191',
            'city' => 'max:100',
            'country' => 'max:100',
            'gst_number' => 'max:15',
            'pan' => 'max:10',
            'contact_person' => 'max:150',
            'mobile' => 'max:20',
            'email' => 'email',
            'bank_account' => 'max:30',
            'ifsc' => 'max:11',
            'account_holder' => 'max:150',
            'commission_pct' => 'required|numeric',
            'settlement_type' => 'required|in:' . implode(',', self::SETTLEMENT_TYPES),
            'gst_type' => 'required|in:' . implode(',', self::GST_TYPES),
            'status' => 'required|in:' . implode(',', array_keys(self::STATUS_OPTIONS)),
        ];
        $errors = Validator::make($request->all(), $rules)->errors();

        $slug = strtolower(trim((string) $request->input('slug', '')));

        if ($slug !== '' && !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
            $errors['slug'][] = 'Use lowercase letters, numbers, and hyphens only.';
        } elseif ($slug !== '') {
            $dupe = Database::first('hotels', ['slug' => $slug]);
            if ($dupe !== null && ($existing === null || $dupe['id'] !== $existing['id'])) {
                $errors['slug'][] = 'This slug is already in use.';
            }
        }

        $commissionPct = is_numeric($request->input('commission_pct')) ? (float) $request->input('commission_pct') : -1;

        if ($commissionPct < 5 || $commissionPct > 50) {
            $errors['commission_pct'][] = 'Commission must be between 5% and 50%.';
        }

        if ($errors !== []) {
            Session::flash('error', 'Please fix the errors below.');
            Session::flash('_old_input', $request->all());
            Session::flash('_form_errors', $errors);

            return Response::redirect($redirectBack);
        }

        $hotelId = $existing['id'] ?? uuid();
        $subdir = 'hotels/' . $hotelId;

        $heroPath = FileUpload::storeImage($request->file('hero_image'), $subdir) ?? ($existing['hero_image'] ?? null);

        $existingGallery = $existing !== null ? (json_decode((string) ($existing['gallery'] ?? '[]'), true) ?: []) : [];
        $removeGallery = $request->input('remove_gallery', []);
        $removeGallery = is_array($removeGallery) ? $removeGallery : [];
        $keptGallery = array_values(array_diff($existingGallery, $removeGallery));
        $newGallery = FileUpload::storeImages($request->file('gallery'), $subdir);
        $gallery = [...$keptGallery, ...$newGallery];

        $data = [
            'name' => sanitize((string) $request->input('name')),
            'slug' => $slug,
            'address' => $this->nullableInput($request, 'address'),
            'city' => $this->nullableInput($request, 'city'),
            'country' => $this->nullableInput($request, 'country') ?? 'India',
            'gst_number' => $this->nullableInput($request, 'gst_number'),
            'pan' => $this->nullableInput($request, 'pan'),
            'contact_person' => $this->nullableInput($request, 'contact_person'),
            'mobile' => $this->nullableInput($request, 'mobile'),
            'email' => $this->nullableInput($request, 'email'),
            'bank_account' => $this->nullableInput($request, 'bank_account'),
            'ifsc' => $this->nullableInput($request, 'ifsc'),
            'account_holder' => $this->nullableInput($request, 'account_holder'),
            'commission_pct' => $commissionPct,
            'settlement_type' => (string) $request->input('settlement_type'),
            'gst_type' => (string) $request->input('gst_type'),
            'status' => (string) $request->input('status'),
            'hero_image' => $heroPath,
            'gallery' => json_encode($gallery),
        ];

        if ($existing === null) {
            $data['id'] = $hotelId;
            $data['owner_role'] = Auth::roleName();
            $data['visibility_scope'] = 'hotel';
            Hotel::create($data);
            $auditAction = 'hotel.created';
            Session::flash('success', 'Hotel created.');
        } else {
            Hotel::updateRecord($hotelId, $data);
            $auditAction = 'hotel.updated';
            Session::flash('success', 'Hotel updated.');
        }

        $now = date('Y-m-d H:i:s');
        Database::insert('audit_logs', [
            'id' => uuid(),
            'user_id' => Auth::id(),
            'action' => $auditAction,
            'entity_type' => 'hotels',
            'entity_id' => $hotelId,
            'new_values' => json_encode($data),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return Response::redirect(route('hotels.show', ['id' => $hotelId]) . '?tab=details');
    }

    private function saveRoom(Request $request, ?array $existing): Response
    {
        $hotelId = (string) $request->param('id');
        $redirectBack = route('hotels.show', ['id' => $hotelId]) . '?tab=rooms';

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        $action = $existing === null ? 'create' : 'edit';

        if (!can('rooms', $action, $hotelId)) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $rules = [
            'room_number' => 'required|max:20',
            'room_type' => 'required|in:' . implode(',', self::ROOM_TYPES),
            'pax' => 'required|numeric',
            'max_adults' => 'required|numeric',
            'max_children' => 'required|numeric',
            'base_price' => 'required|numeric',
            'status' => 'required|in:' . implode(',', self::ROOM_STATUSES),
        ];
        $errors = Validator::make($request->all(), $rules)->errors();

        $roomNumber = trim((string) $request->input('room_number', ''));

        if ($roomNumber !== '') {
            $dupe = Database::first('rooms', ['hotel_id' => $hotelId, 'room_number' => $roomNumber, 'is_deleted' => 0]);
            if ($dupe !== null && ($existing === null || $dupe['id'] !== $existing['id'])) {
                $errors['room_number'][] = 'A room with this number already exists at this hotel.';
            }
        }

        if ($errors !== []) {
            Session::flash('error', 'Please fix the room details — ' . implode(' ', array_merge(...array_values($errors))));

            return Response::redirect($redirectBack);
        }

        $data = [
            'hotel_id' => $hotelId,
            'room_number' => sanitize($roomNumber),
            'room_type' => (string) $request->input('room_type'),
            'pax' => (int) $request->input('pax'),
            'max_adults' => (int) $request->input('max_adults'),
            'max_children' => (int) $request->input('max_children'),
            'base_price' => (float) $request->input('base_price'),
            'status' => (string) $request->input('status'),
        ];

        if ($existing === null) {
            $data['owner_role'] = Auth::roleName();
            $data['visibility_scope'] = 'hotel';
            Room::create($data);
            Session::flash('success', 'Room added.');
        } else {
            Room::updateRecord($existing['id'], $data);
            Session::flash('success', 'Room updated.');
        }

        return Response::redirect($redirectBack);
    }

    private function saveRatePlan(Request $request, ?array $existing): Response
    {
        $hotelId = (string) $request->param('id');
        $redirectBack = route('hotels.show', ['id' => $hotelId]) . '?tab=rate-plans';

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        $action = $existing === null ? 'create' : 'edit';

        if (!can('rate_plans', $action, $hotelId)) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $rules = [
            'plan_name' => 'required|max:150',
            'room_type' => 'required|in:' . implode(',', self::ROOM_TYPES),
            'occupancy_type' => 'max:50',
            'season' => 'required|in:' . implode(',', self::SEASONS),
            'base_price' => 'required|numeric',
        ];
        $errors = Validator::make($request->all(), $rules)->errors();

        if ($errors !== []) {
            Session::flash('error', 'Please fix the rate plan details — ' . implode(' ', array_merge(...array_values($errors))));

            return Response::redirect($redirectBack);
        }

        $data = [
            'hotel_id' => $hotelId,
            'plan_name' => sanitize((string) $request->input('plan_name')),
            'room_type' => (string) $request->input('room_type'),
            'occupancy_type' => $this->nullableInput($request, 'occupancy_type'),
            'season' => (string) $request->input('season'),
            'base_price' => (float) $request->input('base_price'),
        ];

        if ($existing === null) {
            $data['owner_role'] = Auth::roleName();
            $data['visibility_scope'] = 'hotel';
            RatePlan::create($data);
            Session::flash('success', 'Rate plan added.');
        } else {
            RatePlan::updateRecord($existing['id'], $data);
            Session::flash('success', 'Rate plan updated.');
        }

        return Response::redirect($redirectBack);
    }

    /**
     * Guests aren't a real table — this dedupes bookings by mobile
     * number (the one reliably unique guest identifier we capture).
     * "Company" is listed in the spec but no booking field captures
     * it, so the view renders that column as "—" rather than inventing
     * data; MAX() wrapping keeps this safe under ONLY_FULL_GROUP_BY.
     *
     * @return array<int, array<string, mixed>>
     */
    private function guestsForHotel(string $hotelId): array
    {
        $sql = "SELECT guest_mobile,
                       MAX(guest_name) AS guest_name,
                       MAX(guest_email) AS guest_email,
                       COUNT(*) AS booking_count,
                       MAX(checkin_date) AS last_stay
                FROM bookings
                WHERE hotel_id = ? AND is_deleted = 0
                GROUP BY guest_mobile
                ORDER BY last_stay DESC
                LIMIT 100";

        return Database::query($sql, [$hotelId])->fetchAll();
    }

    /**
     * The unified invoices ledger is real and queryable, but nothing
     * writes to it yet (invoice generation is a future module) — this
     * naturally renders as a beautiful empty state today.
     *
     * @return array<int, array<string, mixed>>
     */
    private function invoicesForHotel(string $hotelId): array
    {
        $sql = 'SELECT id, invoice_type, invoice_number, invoice_date, grand_total, status
                FROM invoices
                WHERE hotel_id = ? AND is_deleted = 0
                ORDER BY invoice_date DESC
                LIMIT 100';

        return Database::query($sql, [$hotelId])->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staffForHotel(string $hotelId): array
    {
        $sql = 'SELECT u.id, u.full_name, u.email, u.designation, r.name AS role_name, uh.is_primary
                FROM user_hotels uh
                JOIN users u ON u.id = uh.user_id AND u.is_deleted = 0
                JOIN roles r ON r.id = u.role_id
                WHERE uh.hotel_id = ? AND uh.is_deleted = 0
                ORDER BY u.full_name';

        return Database::query($sql, [$hotelId])->fetchAll();
    }

    private function nullableInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return sanitize($value);
    }
}
