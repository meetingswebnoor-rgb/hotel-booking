<?php

/**
 * @var array<string, mixed> $invoice
 * @var array<string, mixed>|null $hotel
 * @var array<string, mixed>|null $billingEntity
 */

$isIntraState = (float) $invoice['cgst_amount'] > 0 || (float) $invoice['sgst_amount'] > 0;
?>
<div class="voucher-actions no-print">
  <button type="button" class="btn btn-primary" data-print-trigger>
    <?= icon('printer', 'icon icon-sm') ?>
    <span>Print / Save as PDF</span>
  </button>
  <a href="<?= route('commission-invoices.index') ?>" class="btn btn-ghost">Back to Commission Invoices</a>
</div>

<div class="voucher-paper">
  <header class="invoice-letterhead">
    <div>
      <h1><?= e((string) ($billingEntity['legal_entity_name'] ?? 'Hotezo')) ?></h1>
      <p><?= e((string) ($billingEntity['registered_address'] ?? '')) ?></p>
      <p><?= e((string) ($billingEntity['state'] ?? '')) ?><?= !empty($billingEntity['state_code']) ? ' (State Code ' . e((string) $billingEntity['state_code']) . ')' : '' ?></p>
      <p>
        <?php if (!empty($billingEntity['gstin'])): ?>GSTIN: <?= e((string) $billingEntity['gstin']) ?><?php endif; ?>
        <?php if (!empty($billingEntity['pan'])): ?> &middot; PAN: <?= e((string) $billingEntity['pan']) ?><?php endif; ?>
      </p>
    </div>
    <div class="voucher-badge">
      <strong>Commission Invoice</strong>
      <span class="font-mono"><?= e((string) $invoice['invoice_number']) ?></span>
      <span class="text-low">Bill No. <?= e((string) ($invoice['bill_number'] ?? '—')) ?></span>
    </div>
  </header>

  <section class="invoice-meta-grid">
    <div><span class="voucher-label">Invoice Date</span><p><?= e(date('d M Y', strtotime((string) $invoice['invoice_date']))) ?></p></div>
    <div><span class="voucher-label">Financial Year</span><p><?= e((string) $invoice['financial_year']) ?></p></div>
    <div><span class="voucher-label">Billing Period</span><p><?= e(date('d M Y', strtotime((string) $invoice['period_start']))) ?> &ndash; <?= e(date('d M Y', strtotime((string) $invoice['period_end']))) ?></p></div>
    <div><span class="voucher-label">Place of Supply</span><p><?= $isIntraState ? 'Intra-State' : 'Inter-State' ?></p></div>
  </section>

  <section class="voucher-grid invoice-parties">
    <div>
      <span class="voucher-label">Billed To</span>
      <p><?= e((string) ($hotel['name'] ?? '')) ?></p>
      <p class="text-med"><?= e((string) ($hotel['address'] ?? '')) ?><?= !empty($hotel['city']) ? ', ' . e((string) $hotel['city']) : '' ?></p>
      <?php if (!empty($hotel['gst_number'])): ?><p class="text-med">GSTIN: <?= e((string) $hotel['gst_number']) ?></p><?php endif; ?>
      <?php if (!empty($invoice['hotel_state_code'])): ?><p class="text-med">State Code: <?= e((string) $invoice['hotel_state_code']) ?></p><?php endif; ?>
    </div>
    <div>
      <span class="voucher-label">HSN/SAC</span>
      <p>996111</p>
      <p class="text-med">Commission agent services</p>
    </div>
  </section>

  <table class="voucher-table">
    <thead>
      <tr>
        <th>Description</th>
        <th>Value</th>
      </tr>
    </thead>
    <tbody>
      <tr><td>Confirmed Bookings</td><td><?= (int) $invoice['total_bookings'] ?></td></tr>
      <tr><td>Room Nights</td><td><?= (int) $invoice['total_room_nights'] ?></td></tr>
      <tr><td>Room Rent</td><td><?= money($invoice['total_room_rent']) ?></td></tr>
      <tr><td>OTA Commission (informational — not billed by Hotezo)</td><td><?= money($invoice['total_ota_commission']) ?></td></tr>
      <tr><td><strong>Hotezo Commission (Taxable Value)</strong></td><td><strong><?= money($invoice['taxable_value']) ?></strong></td></tr>
      <?php if ($isIntraState): ?>
        <tr><td>CGST @ <?= e((string) $invoice['cgst_rate']) ?>%</td><td><?= money($invoice['cgst_amount']) ?></td></tr>
        <tr><td>SGST @ <?= e((string) $invoice['sgst_rate']) ?>%</td><td><?= money($invoice['sgst_amount']) ?></td></tr>
      <?php else: ?>
        <tr><td>IGST @ <?= e((string) $invoice['igst_rate']) ?>%</td><td><?= money($invoice['igst_amount']) ?></td></tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr class="voucher-grand-total">
        <td>Grand Total (Taxable Value + GST)</td>
        <td><?= money($invoice['grand_total']) ?></td>
      </tr>
      <tr><td>Less: TDS @ <?= e((string) $invoice['tds_rate']) ?>%</td><td>&minus; <?= money($invoice['tds_amount']) ?></td></tr>
      <tr><td>Less: TCS @ <?= e((string) $invoice['tcs_rate']) ?>%</td><td>&minus; <?= money($invoice['tcs_amount']) ?></td></tr>
      <tr class="voucher-grand-total">
        <td>Net Receivable</td>
        <td><?= money($invoice['net_receivable']) ?></td>
      </tr>
    </tfoot>
  </table>

  <p class="invoice-amount-words"><strong>Amount in words:</strong> <?= e(amount_in_words((float) $invoice['net_receivable'])) ?></p>

  <?php if (!empty($invoice['notes'])): ?>
    <p class="voucher-notes"><strong>Notes:</strong> <?= e((string) $invoice['notes']) ?></p>
  <?php endif; ?>

  <?php if (!empty($billingEntity['bank_name'])): ?>
    <section class="invoice-bank-details">
      <span class="voucher-label">Bank Details</span>
      <p><?= e((string) $billingEntity['bank_name']) ?> &middot; A/C <?= e((string) ($billingEntity['bank_account_number'] ?? '')) ?> &middot; IFSC <?= e((string) ($billingEntity['bank_ifsc'] ?? '')) ?></p>
      <p class="text-med"><?= e((string) ($billingEntity['bank_account_holder'] ?? '')) ?></p>
    </section>
  <?php endif; ?>

  <footer class="invoice-signatory">
    <div>
      <p class="text-low">This is a system-generated commission invoice.</p>
    </div>
    <div class="invoice-signatory__block">
      <p>For <?= e((string) ($billingEntity['legal_entity_name'] ?? 'Hotezo')) ?></p>
      <p class="invoice-signatory__line">&nbsp;</p>
      <p class="text-med"><?= e((string) ($billingEntity['signatory_name'] ?? 'Authorized Signatory')) ?></p>
      <p class="text-low"><?= e((string) ($billingEntity['signatory_designation'] ?? '')) ?></p>
    </div>
  </footer>
</div>
