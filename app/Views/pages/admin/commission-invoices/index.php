<?php

use App\Core\View;

/**
 * @var array<int, array<string, mixed>> $invoices
 */

$statusBadge = static fn (string $status): string => match ($status) {
    'paid' => 'success',
    'issued' => 'info',
    'overdue' => 'danger',
    'cancelled' => 'neutral',
    default => 'warning',
};

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'Commission Invoices']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <div>
    <h2>Commission Invoices</h2>
    <p class="text-med mt-2">Hotezo billing hotels for Hotezo's own commission.</p>
  </div>
  <a href="<?= route('commission-invoices.create') ?>" class="btn btn-primary">
    <?= icon('plus', 'icon icon-sm') ?>
    <span>Generate Invoice</span>
  </a>
</div>

<?php if ($invoices === []): ?>
  <?= partial('empty-state', [
      'title' => 'No commission invoices yet',
      'description' => 'Generate one from a hotel\'s confirmed bookings for a given month.',
      'icon' => '🧾',
  ]) ?>
<?php else: ?>
  <div class="card glass" data-animate>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Bill #</th>
            <th>Hotel</th>
            <th>Billing Entity</th>
            <th>Period</th>
            <th>FY</th>
            <th>Grand Total</th>
            <th>Net Receivable</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td class="font-mono"><?= e($inv['invoice_number']) ?></td>
              <td class="font-mono"><?= e((string) ($inv['bill_number'] ?? '—')) ?></td>
              <td><?= e($inv['hotel_name']) ?></td>
              <td><?= e((string) ($inv['billing_entity_name'] ?? '—')) ?></td>
              <td><?= e((string) $inv['period_start']) ?> &rarr; <?= e((string) $inv['period_end']) ?></td>
              <td><?= e($inv['financial_year']) ?></td>
              <td><?= money($inv['grand_total']) ?></td>
              <td><?= money($inv['net_receivable']) ?></td>
              <td><span class="badge badge--<?= $statusBadge($inv['status']) ?>"><?= e(ucfirst($inv['status'])) ?></span></td>
              <td class="booking-row__actions">
                <a href="<?= route('commission-invoices.show', ['id' => $inv['id']]) ?>" target="_blank">View / Print</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
