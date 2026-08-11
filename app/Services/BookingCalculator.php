<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

/**
 * The authoritative money math for a booking. The booking form
 * mirrors these formulas in JS for live feedback as the user types,
 * but this service is what actually gets trusted and persisted —
 * every save re-runs the numbers here from the submitted room lines,
 * never from whatever total the client sent.
 *
 * GST slab (5% vs 18%) is applied per room LINE against that line's
 * nightly rate, not against the booking's combined total — that
 * matches how Indian hotel GST actually works (the slab depends on
 * the declared per-night tariff of each room type), and a single
 * booking can mix a budget room and a suite that sit in different
 * slabs.
 *
 * hotel_gst is guest-facing (collected on top of room rent and
 * remitted, not a deduction from the hotel's earning); TDS, OTA
 * commission, Hotezo commission, and GST-on-commission are what
 * actually reduce hotel_earning. TCS is tracked but not deducted here
 * — same convention BookingSeeder already used, kept for consistency
 * with previously-seeded data and the dashboard aggregates built on it.
 */
final class BookingCalculator
{
    public const GST_LOW_RATE = 5.0;
    public const GST_HIGH_RATE = 18.0;
    public const GST_THRESHOLD = 7499.0;
    public const TDS_RATE = 0.1;
    public const TCS_RATE = 0.5;
    public const COMMISSION_GST_RATE = 18.0;

    public static function nights(string $checkin, string $checkout): int
    {
        $in = new DateTimeImmutable($checkin);
        $out = new DateTimeImmutable($checkout);

        return max(0, $in->diff($out)->days);
    }

    public static function roomLineSubtotal(float $nightlyRate, int $quantity, int $nights): float
    {
        return round($nightlyRate * $quantity * $nights, 2);
    }

    public static function roomLineGstRate(float $nightlyRate): float
    {
        return $nightlyRate <= self::GST_THRESHOLD ? self::GST_LOW_RATE : self::GST_HIGH_RATE;
    }

    /**
     * @param array<int, array{nightly_rate: float|int, quantity: int}> $roomLines
     * @return array{
     *     nights: int,
     *     total_room_rent: float,
     *     hotel_gst: float,
     *     tds: float,
     *     tcs: float,
     *     ota_commission: float,
     *     hotezo_commission: float,
     *     gst_on_commission: float,
     *     total_commission_taxes: float,
     *     hotel_earning: float,
     *     hotel_collection: float,
     *     hotezo_collection: float,
     * }
     */
    public static function calculate(
        array $roomLines,
        int $nights,
        float $otaCommissionPct,
        float $hotelCommissionPct,
        string $source
    ): array {
        $totalRoomRent = 0.0;
        $hotelGst = 0.0;

        foreach ($roomLines as $line) {
            $rate = (float) $line['nightly_rate'];
            $quantity = (int) $line['quantity'];
            $subtotal = self::roomLineSubtotal($rate, $quantity, $nights);

            $totalRoomRent += $subtotal;
            $hotelGst += round($subtotal * self::roomLineGstRate($rate) / 100, 2);
        }

        $totalRoomRent = round($totalRoomRent, 2);
        $hotelGst = round($hotelGst, 2);

        $tds = round($totalRoomRent * self::TDS_RATE / 100, 2);
        $tcs = round($totalRoomRent * self::TCS_RATE / 100, 2);
        $otaCommission = round($totalRoomRent * $otaCommissionPct / 100, 2);
        $hotezoCommission = round($totalRoomRent * $hotelCommissionPct / 100, 2);
        $gstOnCommission = round(($otaCommission + $hotezoCommission) * self::COMMISSION_GST_RATE / 100, 2);
        $totalCommissionTaxes = round($otaCommission + $hotezoCommission + $gstOnCommission, 2);
        $hotelEarning = round($totalRoomRent + $hotelGst - $otaCommission - $hotezoCommission - $gstOnCommission - $tds, 2);

        $hotelCollection = $source === 'ota'
            ? round($totalRoomRent + $hotelGst - $otaCommission, 2)
            : round($totalRoomRent + $hotelGst, 2);
        $hotezoCollection = round($hotezoCommission + $gstOnCommission, 2);

        return [
            'nights' => $nights,
            'total_room_rent' => $totalRoomRent,
            'hotel_gst' => $hotelGst,
            'tds' => $tds,
            'tcs' => $tcs,
            'ota_commission' => $otaCommission,
            'hotezo_commission' => $hotezoCommission,
            'gst_on_commission' => $gstOnCommission,
            'total_commission_taxes' => $totalCommissionTaxes,
            'hotel_earning' => $hotelEarning,
            'hotel_collection' => $hotelCollection,
            'hotezo_collection' => $hotezoCollection,
        ];
    }
}
