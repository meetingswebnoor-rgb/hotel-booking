<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\CommissionInvoice;

/**
 * Pulls the billable bookings for a hotel+month, computes the GST
 * breakup, and allocates invoice/bill numbers. Rates come from
 * config/invoicing.php, not hardcoded here, specifically so the
 * TCS-rate discrepancy flagged there is a one-line fix once someone
 * reconciles it — see that file's docblock for the full explanation.
 *
 * Unlike App\Services\BookingCalculator (never trusts the client), this
 * module's generator screen is explicitly "fully editable before
 * generating" — computeBreakup() produces the starting preview, but
 * CommissionInvoiceController::store() saves whatever the operator
 * actually submitted (after range/type validation), not a server-side
 * recompute. The two modules have different jobs: BookingCalculator
 * prices a booking billed to guests; this reconciles what a hotel
 * already owes Hotezo, which an accountant may need to adjust by hand.
 */
final class CommissionInvoiceService
{
    /**
     * "Confirmed bookings" for invoicing purposes means bookings that
     * actually happened or are happening — not pending, cancelled,
     * rejected, or no-show.
     */
    private const BILLABLE_STATUSES = ['confirmed', 'checked_in', 'checked_out'];

    private const SEQUENCE_PREFIX = 'HZC';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function pullBookings(string $hotelId, string $periodStart, string $periodEnd): array
    {
        $statusPlaceholders = implode(', ', array_fill(0, count(self::BILLABLE_STATUSES), '?'));

        $sql = "SELECT * FROM bookings
                WHERE hotel_id = ? AND is_deleted = 0
                  AND status IN ({$statusPlaceholders})
                  AND checkin_date BETWEEN ? AND ?
                ORDER BY checkin_date";

        $params = [$hotelId, ...self::BILLABLE_STATUSES, $periodStart, $periodEnd];

        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * GST-registered hotels carry their state in the first two digits
     * of the GSTIN by design (e.g. "27" = Maharashtra) — hotels has no
     * separate state/state_code column, so this is derived rather than
     * stored. Unregistered hotels (no GSTIN) return null; the generator
     * form lets the operator confirm/override the state either way.
     */
    public static function deriveHotelStateCode(array $hotel): ?string
    {
        $gstNumber = trim((string) ($hotel['gst_number'] ?? ''));

        return strlen($gstNumber) >= 2 ? substr($gstNumber, 0, 2) : null;
    }

    /**
     * @param array<int, array<string, mixed>> $bookings
     * @return array<string, mixed>
     */
    public static function computeBreakup(array $bookings, ?string $billingEntityStateCode, ?string $hotelStateCode): array
    {
        $totalRoomNights = 0;
        $totalRoomRent = 0.0;
        $totalOtaCommission = 0.0;
        $totalHotezoCommission = 0.0;

        foreach ($bookings as $booking) {
            $nights = (int) $booking['nights'];
            $roomLines = json_decode((string) $booking['rooms'], true) ?: [];
            $roomQuantity = array_sum(array_map(
                static fn (array $line): int => (int) ($line['quantity'] ?? 1),
                $roomLines
            ));

            $totalRoomNights += $roomQuantity * $nights;
            $totalRoomRent += (float) $booking['total_room_rent'];
            $totalOtaCommission += (float) $booking['ota_commission'];
            $totalHotezoCommission += (float) $booking['hotezo_commission'];
        }

        $totalRoomRent = round($totalRoomRent, 2);
        $totalOtaCommission = round($totalOtaCommission, 2);
        $taxableValue = round($totalHotezoCommission, 2);

        $isIntraState = $billingEntityStateCode !== null
            && $hotelStateCode !== null
            && $billingEntityStateCode === $hotelStateCode;

        $gstRate = (float) config('invoicing.gst_rate', 18.0);
        $gstBreakup = gst($taxableValue, $gstRate, $isIntraState ? 'intra' : 'inter');

        // TDS/TCS apply against total room rent (the gross transaction
        // value), the same base App\Services\BookingCalculator already
        // uses per booking — not against the commission itself.
        $tdsRate = (float) config('invoicing.tds_rate', 0.1);
        $tcsRate = (float) config('invoicing.tcs_rate', 0.25);
        $tdsAmount = round($totalRoomRent * $tdsRate / 100, 2);
        $tcsAmount = round($totalRoomRent * $tcsRate / 100, 2);

        $grandTotal = $gstBreakup['grand_total'];
        $netReceivable = round($grandTotal - $tdsAmount - $tcsAmount, 2);

        return [
            'total_bookings' => count($bookings),
            'total_room_nights' => $totalRoomNights,
            'total_room_rent' => $totalRoomRent,
            'total_ota_commission' => $totalOtaCommission,
            'taxable_value' => $taxableValue,
            'is_intra_state' => $isIntraState,
            'cgst_rate' => $isIntraState ? round($gstRate / 2, 2) : 0.0,
            'cgst_amount' => $gstBreakup['cgst'],
            'sgst_rate' => $isIntraState ? round($gstRate / 2, 2) : 0.0,
            'sgst_amount' => $gstBreakup['sgst'],
            'igst_rate' => $isIntraState ? 0.0 : $gstRate,
            'igst_amount' => $gstBreakup['igst'],
            'total_tax' => $gstBreakup['total_tax'],
            'tds_rate' => $tdsRate,
            'tds_amount' => $tdsAmount,
            'tcs_rate' => $tcsRate,
            'tcs_amount' => $tcsAmount,
            'grand_total' => $grandTotal,
            'net_receivable' => $netReceivable,
        ];
    }

    /**
     * Atomic allocate-and-increment via ON DUPLICATE KEY UPDATE (a
     * single InnoDB row-level operation, safe under concurrent
     * generation) rather than a separate SELECT-then-UPDATE, which
     * would race if two accountants generate an invoice for the same
     * billing entity/FY at once.
     *
     * @return array{invoice_number: string, bill_number: string}
     */
    public static function allocateNumbers(string $billingEntityId, string $stateCode, string $financialYear): array
    {
        Database::query(
            'INSERT INTO commission_invoice_number_sequence (id, billing_entity_id, financial_year, prefix, last_number)
             VALUES (?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE last_number = last_number + 1',
            [uuid(), $billingEntityId, $financialYear, self::SEQUENCE_PREFIX]
        );

        $row = Database::first('commission_invoice_number_sequence', [
            'billing_entity_id' => $billingEntityId,
            'financial_year' => $financialYear,
            'prefix' => self::SEQUENCE_PREFIX,
        ]);

        $sequence = str_pad((string) ($row['last_number'] ?? 1), 4, '0', STR_PAD_LEFT);

        return [
            'invoice_number' => "{$stateCode}-{$financialYear}-{$sequence}",
            'bill_number' => self::SEQUENCE_PREFIX . "-{$sequence}",
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function save(array $data, ?string $ownerRole): string
    {
        $data['owner_role'] = $ownerRole;
        $data['visibility_scope'] = 'hotel';

        return CommissionInvoice::create($data);
    }
}
