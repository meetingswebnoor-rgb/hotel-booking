<?php

declare(strict_types=1);

/**
 * Tax rates for Hotezo's own commission invoicing (App\Services\CommissionInvoiceService),
 * kept in one place specifically so the rates are easy to compare and reconcile.
 *
 * FLAGGED DISCREPANCY: 'tcs_rate' below is 0.25%, per the commission-invoice spec this
 * module was built from. App\Services\BookingCalculator::TCS_RATE (used for the per-booking
 * TCS shown on the booking form and bookings list) is 0.5% — the original booking-form spec
 * and the original commission-invoice spec simply disagree with each other. Both are real,
 * both are already live in their own module, and neither has been changed to match the other.
 * 'tds_rate' (0.1%) matches BookingCalculator::TDS_RATE exactly, so that one isn't in question.
 *
 * Until this is reconciled with whoever owns the actual tax filing, commission invoices use
 * the rate below (0.25%) and booking-level figures keep using BookingCalculator's 0.5% —
 * that mismatch is real and intentional, not a bug, until someone says which rate is correct.
 */
return [
    'tds_rate' => 0.1,
    'tcs_rate' => 0.25,
    'gst_rate' => 18.0,
];
