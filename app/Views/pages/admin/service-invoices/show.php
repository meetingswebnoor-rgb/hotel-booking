<?php

/**
 * @var array<string, mixed> $invoice
 * @var array<string, mixed>|null $hotel
 * @var array<string, mixed>|null $billingEntity
 * @var array<string, string> $invoiceTypeLabels
 * @var array<string, string> $transactionCategoryLabels
 */

$isIntraState = (float) $invoice['cgst_amount'] > 0 || (float) $invoice['sgst_amount'] > 0;
$documentTitle = $invoiceTypeLabels[$invoice['invoice_type']] ?? 'Invoice';
?>
<div class="voucher-actions no-print">
  <button type="button" class="btn btn-primary" data-print-trigger>
    <?= icon('printer', 'icon icon-sm') ?>
    <span>Print / Save as PDF</span>
  </button>
  <a href="<?= route('service-invoices.index') ?>" class="btn btn-ghost">Back to Service Invoices</a>
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
      <strong><?= e($documentTitle) ?></strong>
      <span class="font-mono"><?= e((string) $invoice['invoice_number']) ?></span>
    </div>
  </header>

  <section class="invoice-meta-grid">
    <div><span class="voucher-label">Invoice Date</span><p><?= e(date('d M Y', strtotime((string) $invoice['invoice_date']))) ?></p></div>
    <div><span class="voucher-label">Financial Year</span><p><?= e((string) $invoice['financial_year']) ?></p></div>
    <div><span class="voucher-label">Transaction Category</span><p><?= e($invoice['transaction_category']) ?> — <?= e($transactionCategoryLabels[$invoice['transaction_category']] ?? '') ?></p></div>
    <div><span class="voucher-label">Place of Supply</span><p><?= e((string) ($invoice['place_of_supply'] ?? '—')) ?></p></div>
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
      <span class="voucher-label">Description</span>
      <p><?= e((string) $invoice['service_description']) ?></p>
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
      <tr><td><?= e((string) $invoice['service_description']) ?></td><td><?= money($invoice['taxable_value']) ?></td></tr>
      <?php if ($isIntraState): ?>
        <tr><td>CGST @ <?= e((string) $invoice['cgst_rate']) ?>%</td><td><?= money($invoice['cgst_amount']) ?></td></tr>
        <tr><td>SGST @ <?= e((string) $invoice['sgst_rate']) ?>%</td><td><?= money($invoice['sgst_amount']) ?></td></tr>
      <?php else: ?>
        <tr><td>IGST @ <?= e((string) $invoice['igst_rate']) ?>%</td><td><?= money($invoice['igst_amount']) ?></td></tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr class="voucher-grand-total">
        <td>Total Payable</td>
        <td><?= money($invoice['grand_total']) ?></td>
      </tr>
    </tfoot>
  </table>

  <p class="invoice-amount-words"><strong>Amount in words:</strong> <?= e(amount_in_words((float) $invoice['grand_total'])) ?></p>

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
      <p class="text-low">This is a system-generated <?= e(mb_strtolower($documentTitle)) ?>.</p>
    </div>
    <div class="invoice-signatory__block">
      <p>For <?= e((string) ($billingEntity['legal_entity_name'] ?? 'Hotezo')) ?></p>
      <p class="invoice-signatory__line">&nbsp;</p>
      <p class="text-med"><?= e((string) ($billingEntity['signatory_name'] ?? 'Authorized Signatory')) ?></p>
      <p class="text-low"><?= e((string) ($billingEntity['signatory_designation'] ?? '')) ?></p>
    </div>
  </footer>
</div>
