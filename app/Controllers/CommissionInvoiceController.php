<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\RoleLevel;
use App\Core\Session;
use App\Core\Validator;
use App\Services\CommissionInvoiceService;

/**
 * Hotezo billing a hotel for Hotezo's own commission — a generator
 * (pick hotel/month/billing entity, preview the breakup, edit it if
 * needed, save) plus a print-ready GST invoice view. Deliberately
 * gated narrower than config/permissions.php's generic 'invoices'
 * module (shared with hotel_manager/front_desk for guest/service
 * invoicing): commission invoicing is Hotezo's own internal billing,
 * so this checks role directly — Super Admin, Admin, or Accounts only.
 */
final class CommissionInvoiceController
{
    private const PER_PAGE = 25;

    public function index(Request $request): Response
    {
        if (!$this->canManage()) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $hotelIds = $this->allowedHotelIds();
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'ci.hotel_id');

        $sql = "SELECT ci.id, ci.invoice_number, ci.bill_number, ci.invoice_date, ci.financial_year,
                       ci.period_start, ci.period_end, ci.grand_total, ci.net_receivable, ci.status,
                       h.name AS hotel_name, cc.legal_entity_name AS billing_entity_name
                FROM commission_invoices ci
                JOIN hotels h ON h.id = ci.hotel_id
                LEFT JOIN company_compliance_details cc ON cc.id = ci.billing_entity_id
                WHERE ci.is_deleted = 0{$scopeSql}
                ORDER BY ci.invoice_date DESC, ci.created_at DESC
                LIMIT " . self::PER_PAGE;
        $invoices = Database::query($sql, $scopeParams)->fetchAll();

        $html = view('admin/commission-invoices/index', [
            'title' => 'Commission Invoices',
            'active' => 'commission-invoices',
            'pageTitle' => 'Commission Invoices',
            'user' => Auth::user(),
            'invoices' => $invoices,
        ], 'admin');

        return Response::html($html);
    }

    public function create(Request $request): Response
    {
        if (!$this->canManage()) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $html = view('admin/commission-invoices/create', [
            'title' => 'Generate Commission Invoice',
            'active' => 'commission-invoices',
            'pageTitle' => 'Generate Commission Invoice',
            'user' => Auth::user(),
            'hotels' => $this->allowedHotels(),
            'billingEntities' => Database::all('company_compliance_details', ['hotel_id' => null, 'is_deleted' => 0], '*', 'legal_entity_name'),
        ], 'admin');

        return Response::html($html);
    }

    /**
     * GET /commission-invoices/preview — read-only JSON the generator
     * form calls whenever hotel/month/billing entity changes, so the
     * breakup panel populates before anything is saved.
     */
    public function preview(Request $request): Response
    {
        if (!$this->canManage()) {
            return Response::json(['message' => 'Forbidden.'], 403);
        }

        $hotelId = (string) $request->query('hotel_id', '');
        $month = (string) $request->query('month', '');
        $billingEntityId = (string) $request->query('billing_entity_id', '');

        [$hotel, $billingEntity, $error] = $this->resolveInputs($hotelId, $month, $billingEntityId);

        if ($error !== null) {
            return Response::json(['message' => $error], 422);
        }

        [$periodStart, $periodEnd] = $this->monthBounds($month);
        $bookings = CommissionInvoiceService::pullBookings($hotelId, $periodStart, $periodEnd);
        $hotelStateCode = CommissionInvoiceService::deriveHotelStateCode($hotel);
        $breakup = CommissionInvoiceService::computeBreakup($bookings, $billingEntity['state_code'], $hotelStateCode);

        return Response::json([
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'financial_year' => fy_label($periodStart),
            'hotel_state_code' => $hotelStateCode,
            'billing_entity_state_code' => $billingEntity['state_code'],
            'breakup' => $breakup,
        ]);
    }

    public function store(Request $request): Response
    {
        if (!$this->canManage()) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect(route('commission-invoices.create'));
        }

        $hotelId = (string) $request->input('hotel_id', '');
        $month = (string) $request->input('month', '');
        $billingEntityId = (string) $request->input('billing_entity_id', '');

        [$hotel, $billingEntity, $error] = $this->resolveInputs($hotelId, $month, $billingEntityId);

        if ($error !== null) {
            Session::flash('error', $error);

            return Response::redirect(route('commission-invoices.create'));
        }

        $rules = [
            'total_bookings' => 'required|numeric',
            'total_room_nights' => 'required|numeric',
            'total_room_rent' => 'required|numeric',
            'total_ota_commission' => 'required|numeric',
            'taxable_value' => 'required|numeric',
            'cgst_amount' => 'required|numeric',
            'sgst_amount' => 'required|numeric',
            'igst_amount' => 'required|numeric',
            'tds_amount' => 'required|numeric',
            'tcs_amount' => 'required|numeric',
        ];
        $errors = Validator::make($request->all(), $rules)->errors();

        if ($errors !== []) {
            Session::flash('error', 'Please fix the breakup figures — every value must be a number.');
            Session::flash('_old_input', $request->all());

            return Response::redirect(route('commission-invoices.create'));
        }

        [$periodStart, $periodEnd] = $this->monthBounds($month);
        $financialYear = fy_label($periodStart);
        $hotelStateCode = CommissionInvoiceService::deriveHotelStateCode($hotel);

        $totalTax = round(
            (float) $request->input('cgst_amount') + (float) $request->input('sgst_amount') + (float) $request->input('igst_amount'),
            2
        );
        $grandTotal = round((float) $request->input('taxable_value') + $totalTax, 2);
        $tdsAmount = (float) $request->input('tds_amount');
        $tcsAmount = (float) $request->input('tcs_amount');
        $netReceivable = round($grandTotal - $tdsAmount - $tcsAmount, 2);

        $numbers = CommissionInvoiceService::allocateNumbers(
            $billingEntity['id'],
            (string) ($billingEntity['state_code'] ?? 'NA'),
            $financialYear
        );

        $id = CommissionInvoiceService::save([
            'hotel_id' => $hotelId,
            'billing_entity_id' => $billingEntity['id'],
            'invoice_number' => $numbers['invoice_number'],
            'bill_number' => $numbers['bill_number'],
            'invoice_date' => date('Y-m-d'),
            'financial_year' => $financialYear,
            'hotel_state_code' => $hotelStateCode,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_bookings' => (int) $request->input('total_bookings'),
            'total_room_nights' => (int) $request->input('total_room_nights'),
            'total_room_rent' => (float) $request->input('total_room_rent'),
            'total_ota_commission' => (float) $request->input('total_ota_commission'),
            'taxable_value' => (float) $request->input('taxable_value'),
            'cgst_rate' => (float) $request->input('cgst_rate', 0),
            'cgst_amount' => (float) $request->input('cgst_amount'),
            'sgst_rate' => (float) $request->input('sgst_rate', 0),
            'sgst_amount' => (float) $request->input('sgst_amount'),
            'igst_rate' => (float) $request->input('igst_rate', 0),
            'igst_amount' => (float) $request->input('igst_amount'),
            'total_tax' => $totalTax,
            'tds_rate' => (float) config('invoicing.tds_rate', 0.1),
            'tds_amount' => $tdsAmount,
            'tcs_rate' => (float) config('invoicing.tcs_rate', 0.25),
            'tcs_amount' => $tcsAmount,
            'grand_total' => $grandTotal,
            'net_receivable' => $netReceivable,
            'status' => 'issued',
            'notes' => $this->nullableInput($request, 'notes'),
        ], Auth::roleName());

        Session::flash('success', 'Commission invoice ' . $numbers['invoice_number'] . ' generated.');

        return Response::redirect(route('commission-invoices.show', ['id' => $id]));
    }

    public function show(Request $request): Response
    {
        if (!$this->canManage()) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $invoice = Database::first('commission_invoices', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($invoice === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        $hotel = Database::first('hotels', ['id' => $invoice['hotel_id']]);
        $billingEntity = $invoice['billing_entity_id'] !== null
            ? Database::first('company_compliance_details', ['id' => $invoice['billing_entity_id']])
            : null;

        $html = view('admin/commission-invoices/show', [
            'title' => 'Commission Invoice — ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'hotel' => $hotel,
            'billingEntity' => $billingEntity,
        ], 'print');

        return Response::html($html);
    }

    private function canManage(): bool
    {
        return Auth::hasRole('accounts') || role_at_least(RoleLevel::ADMIN);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function monthBounds(string $month): array
    {
        $start = $month . '-01';
        $end = date('Y-m-t', (int) strtotime($start));

        return [$start, $end];
    }

    /**
     * Shared input resolution + validation for both preview() and
     * store() — a hotel outside the caller's scope, an unparseable
     * month, or an unknown billing entity all fail the same way.
     *
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: string|null}
     */
    private function resolveInputs(string $hotelId, string $month, string $billingEntityId): array
    {
        if ($hotelId === '' || $month === '' || $billingEntityId === '') {
            return [null, null, 'Select a hotel, month, and billing entity.'];
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return [null, null, 'Invalid month.'];
        }

        $hotelIds = $this->allowedHotelIds();
        if ($hotelIds !== null && !in_array($hotelId, $hotelIds, true)) {
            return [null, null, 'You do not have access to that hotel.'];
        }

        $hotel = Database::first('hotels', ['id' => $hotelId, 'is_deleted' => 0]);
        if ($hotel === null) {
            return [null, null, 'Hotel not found.'];
        }

        $billingEntity = Database::first('company_compliance_details', ['id' => $billingEntityId, 'hotel_id' => null, 'is_deleted' => 0]);
        if ($billingEntity === null) {
            return [null, null, 'Billing entity not found.'];
        }

        return [$hotel, $billingEntity, null];
    }

    private function nullableInput(Request $request, string $key): ?string
    {
        $value = trim((string) $request->input($key, ''));

        return $value === '' ? null : sanitize($value);
    }

    /**
     * @return array<int, string>|null
     */
    private function allowedHotelIds(): ?array
    {
        return Auth::hasGlobalHotelAccess() ? null : Auth::hotelIds();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allowedHotels(): array
    {
        if (Auth::hasGlobalHotelAccess()) {
            return Database::all('hotels', ['is_deleted' => 0], 'id, name, gst_number, city', 'name');
        }

        $ids = Auth::hotelIds();

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        return Database::query(
            "SELECT id, name, gst_number, city FROM hotels WHERE is_deleted = 0 AND id IN ({$placeholders}) ORDER BY name",
            $ids
        )->fetchAll();
    }
}
