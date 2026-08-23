<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ServiceInvoice;

/**
 * One-off Hotezo-to-hotel charges not tied to any booking (e.g. a
 * platform fee, a manual adjustment) — simpler than
 * App\Services\CommissionInvoiceService since there's nothing to pull:
 * the operator types an amount and picks a rate directly.
 */
final class ServiceInvoiceService
{
    private const SEQUENCE_PREFIX = 'SRV';

    /**
     * @return array<string, mixed>
     */
    public static function computeBreakup(float $amount, float $gstRatePercent, bool $isIntraState): array
    {
        $gstBreakup = gst($amount, $gstRatePercent, $isIntraState ? 'intra' : 'inter');

        return [
            'taxable_value' => $gstBreakup['taxable_amount'],
            'is_intra_state' => $isIntraState,
            'cgst_rate' => $isIntraState ? round($gstRatePercent / 2, 2) : 0.0,
            'cgst_amount' => $gstBreakup['cgst'],
            'sgst_rate' => $isIntraState ? round($gstRatePercent / 2, 2) : 0.0,
            'sgst_amount' => $gstBreakup['sgst'],
            'igst_rate' => $isIntraState ? 0.0 : $gstRatePercent,
            'igst_amount' => $gstBreakup['igst'],
            'total_tax' => $gstBreakup['total_tax'],
            'grand_total' => $gstBreakup['grand_total'],
        ];
    }

    /**
     * Hotel-scoped, same table (and same atomic
     * ON DUPLICATE KEY UPDATE last_number = last_number + 1 pattern) as
     * App\Services\CommissionInvoiceService::allocateNumbers() uses for
     * its own, differently-scoped sequence table — each hotel is its
     * own GST-registered issuer for a service invoice, unlike a
     * commission invoice which runs the other direction.
     */
    public static function allocateInvoiceNumber(string $hotelId, string $financialYear): string
    {
        Database::query(
            'INSERT INTO service_invoice_number_sequence (id, hotel_id, financial_year, prefix, last_number)
             VALUES (?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE last_number = last_number + 1',
            [uuid(), $hotelId, $financialYear, self::SEQUENCE_PREFIX]
        );

        $row = Database::first('service_invoice_number_sequence', [
            'hotel_id' => $hotelId,
            'financial_year' => $financialYear,
            'prefix' => self::SEQUENCE_PREFIX,
        ]);

        $sequence = str_pad((string) ($row['last_number'] ?? 1), 4, '0', STR_PAD_LEFT);

        return self::SEQUENCE_PREFIX . "-{$financialYear}-{$sequence}";
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function save(array $data, ?string $ownerRole): string
    {
        $data['owner_role'] = $ownerRole;
        $data['visibility_scope'] = 'hotel';

        return ServiceInvoice::create($data);
    }
}
