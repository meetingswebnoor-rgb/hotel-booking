<?php

use App\Core\View;

/**
 * @var array<int, array<string, mixed>> $invoices
 * @var array<string, string> $invoiceTypes
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
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'Service Invoices']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <div>
    <h2>Service Invoices</h2>
    <p class="text-med mt-2">One-off Hotezo-to-hotel charges not tied to any booking.</p>
  </div>
  <a href="<?= route('service-invoices.create') ?>" class="btn btn-primary">
    <?= icon('plus', 'icon icon-sm') ?>
    <span>Generate Invoice</span>
  </a>
</div>

<?php if ($invoices === []): ?>
  <?= partial('empty-state', [
      'title' => 'No service invoices yet',
      'description' => 'Generate one for a one-off charge — a platform fee, a manual adjustment, or a credit/debit note.',
      'icon' => '🧾',
  ]) ?>
<?php else: ?>
  <div class="card glass" data-animate>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Hotel</th>
            <th>Billing Entity</th>
            <th>Description</th>
            <th>Type</th>
            <th>Date</th>
            <th>FY</th>
            <th>Total Payable</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td class="font-mono"><?= e($inv['invoice_number']) ?></td>
              <td><?= e($inv['hotel_name']) ?></td>
              <td><?= e((string) ($inv['billing_entity_name'] ?? '—')) ?></td>
              <td><?= e($inv['service_description']) ?></td>
              <td><span class="badge badge--neutral"><?= e($invoiceTypes[$inv['invoice_type']] ?? $inv['invoice_type']) ?></span></td>
              <td><?= e(date('d M Y', strtotime((string) $inv['invoice_date']))) ?></td>
              <td><?= e($inv['financial_year']) ?></td>
              <td><?= money($inv['grand_total']) ?></td>
              <td><span class="badge badge--<?= $statusBadge($inv['status']) ?>"><?= e(ucfirst($inv['status'])) ?></span></td>
              <td class="booking-row__actions">
                <a href="<?= route('service-invoices.show', ['id' => $inv['id']]) ?>" target="_blank">View / Print</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
