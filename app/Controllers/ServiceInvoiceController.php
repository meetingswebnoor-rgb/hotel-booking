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
use App\Services\ServiceInvoiceService;

/**
 * One-off Hotezo-to-hotel charges not tied to any booking. Simpler
 * than commission invoices — nothing to pull from bookings, so the
 * form is a single GET/POST round trip: the hotel and billing-entity
 * state codes are embedded as data attributes on their <option>s at
 * render time, and public/assets/js/service-invoice-form.js computes
 * the GST split live in the browser, no preview endpoint needed.
 * Access matches commission invoices exactly (Super Admin/Admin/Accounts).
 */
final class ServiceInvoiceController
{
    private const PER_PAGE = 25;
    private const GST_RATES = [0.0, 5.0, 12.0, 18.0];
    private const INVOICE_TYPES = ['invoice' => 'Invoice', 'credit_note' => 'Credit Note', 'debit_note' => 'Debit Note'];
    private const TRANSACTION_CATEGORIES = ['REG' => 'Regular', 'RG' => 'Reverse Charge'];

    public function index(Request $request): Response
    {
        if (!$this->canManage()) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $hotelIds = $this->allowedHotelIds();
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'si.hotel_id');

        $sql = "SELECT si.id, si.invoice_number, si.invoice_date, si.financial_year, si.invoice_type,
                       si.service_description, si.grand_total, si.status,
                       h.name AS hotel_name, cc.legal_entity_name AS billing_entity_name
                FROM service_invoices si
                JOIN hotels h ON h.id = si.hotel_id
                LEFT JOIN company_compliance_details cc ON cc.id = si.billing_entity_id
                WHERE si.is_deleted = 0{$scopeSql}
                ORDER BY si.invoice_date DESC, si.created_at DESC
                LIMIT " . self::PER_PAGE;
        $invoices = Database::query($sql, $scopeParams)->fetchAll();

        $html = view('admin/service-invoices/index', [
            'title' => 'Service Invoices',
            'active' => 'service-invoices',
            'pageTitle' => 'Service Invoices',
            'user' => Auth::user(),
            'invoices' => $invoices,
            'invoiceTypes' => self::INVOICE_TYPES,
        ], 'admin');

        return Response::html($html);
    }

    public function create(Request $request): Response
    {
        if (!$this->canManage()) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $hotels = $this->allowedHotels();
        $hotels = array_map(static function (array $h): array {
            $h['state_code'] = CommissionInvoiceService::deriveHotelStateCode($h);

            return $h;
        }, $hotels);

        $html = view('admin/service-invoices/create', [
            'title' => 'Generate Service Invoice',
            'active' => 'service-invoices',
            'pageTitle' => 'Generate Service Invoice',
            'user' => Auth::user(),
            'hotels' => $hotels,
            'billingEntities' => Database::all('company_compliance_details', ['hotel_id' => null, 'is_deleted' => 0], '*', 'legal_entity_name'),
            'gstRates' => self::GST_RATES,
            'invoiceTypes' => self::INVOICE_TYPES,
            'transactionCategories' => self::TRANSACTION_CATEGORIES,
        ], 'admin');

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        if (!$this->canManage()) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect(route('service-invoices.create'));
        }

        $hotelId = (string) $request->input('hotel_id', '');
        $billingEntityId = (string) $request->input('billing_entity_id', '');

        $rules = [
            'hotel_id' => 'required',
            'billing_entity_id' => 'required',
            'service_description' => 'required|max:255',
            'taxable_value' => 'required|numeric',
            'gst_rate' => 'required|in:' . implode(',', self::GST_RATES),
            'invoice_type' => 'required|in:' . implode(',', array_keys(self::INVOICE_TYPES)),
            'transaction_category' => 'required|in:' . implode(',', array_keys(self::TRANSACTION_CATEGORIES)),
            'cgst_amount' => 'required|numeric',
            'sgst_amount' => 'required|numeric',
            'igst_amount' => 'required|numeric',
        ];
        $errors = Validator::make($request->all(), $rules)->errors();

        $hotelIds = $this->allowedHotelIds();
        if ($hotelId !== '' && $hotelIds !== null && !in_array($hotelId, $hotelIds, true)) {
            $errors['hotel_id'][] = 'You do not have access to that hotel.';
        }

        if ($errors !== []) {
            Session::flash('error', 'Please fix the errors below.');
            Session::flash('_old_input', $request->all());
            Session::flash('_form_errors', $errors);

            return Response::redirect(route('service-invoices.create'));
        }

        $hotel = Database::first('hotels', ['id' => $hotelId, 'is_deleted' => 0]);
        $billingEntity = Database::first('company_compliance_details', ['id' => $billingEntityId, 'hotel_id' => null, 'is_deleted' => 0]);

        if ($hotel === null || $billingEntity === null) {
            Session::flash('error', 'Selected hotel or billing entity could not be found.');

            return Response::redirect(route('service-invoices.create'));
        }

        $hotelStateCode = CommissionInvoiceService::deriveHotelStateCode($hotel);
        $financialYear = fy_label();

        $totalTax = round(
            (float) $request->input('cgst_amount') + (float) $request->input('sgst_amount') + (float) $request->input('igst_amount'),
            2
        );
        $taxableValue = (float) $request->input('taxable_value');
        $grandTotal = round($taxableValue + $totalTax, 2);

        $invoiceNumber = ServiceInvoiceService::allocateInvoiceNumber($hotelId, $financialYear);

        $id = ServiceInvoiceService::save([
            'hotel_id' => $hotelId,
            'billing_entity_id' => $billingEntityId,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => date('Y-m-d'),
            'financial_year' => $financialYear,
            'hotel_state_code' => $hotelStateCode,
            'place_of_supply' => gst_state_name($hotelStateCode) ?? 'Unknown',
            'service_description' => sanitize(trim((string) $request->input('service_description'))),
            'invoice_type' => (string) $request->input('invoice_type'),
            'transaction_category' => (string) $request->input('transaction_category'),
            'taxable_value' => $taxableValue,
            'gst_rate' => (float) $request->input('gst_rate'),
            'cgst_rate' => (float) $request->input('cgst_rate', 0),
            'cgst_amount' => (float) $request->input('cgst_amount'),
            'sgst_rate' => (float) $request->input('sgst_rate', 0),
            'sgst_amount' => (float) $request->input('sgst_amount'),
            'igst_rate' => (float) $request->input('igst_rate', 0),
            'igst_amount' => (float) $request->input('igst_amount'),
            'total_tax' => $totalTax,
            'grand_total' => $grandTotal,
            'status' => 'issued',
            'notes' => $this->nullableInput($request, 'notes'),
        ], Auth::roleName());

        Session::flash('success', 'Service invoice ' . $invoiceNumber . ' generated.');

        return Response::redirect(route('service-invoices.show', ['id' => $id]));
    }

    public function show(Request $request): Response
    {
        if (!$this->canManage()) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $invoice = Database::first('service_invoices', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($invoice === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        $hotel = Database::first('hotels', ['id' => $invoice['hotel_id']]);
        $billingEntity = $invoice['billing_entity_id'] !== null
            ? Database::first('company_compliance_details', ['id' => $invoice['billing_entity_id']])
            : null;

        $html = view('admin/service-invoices/show', [
            'title' => 'Service Invoice — ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'hotel' => $hotel,
            'billingEntity' => $billingEntity,
            'invoiceTypeLabels' => self::INVOICE_TYPES,
            'transactionCategoryLabels' => self::TRANSACTION_CATEGORIES,
        ], 'print');

        return Response::html($html);
    }

    private function canManage(): bool
    {
        return Auth::hasRole('accounts') || role_at_least(RoleLevel::ADMIN);
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
