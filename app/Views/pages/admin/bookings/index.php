<?php

use App\Core\View;

/**
 * @var array<int, array<string, mixed>> $bookings
 * @var bool $canCreate
 * @var int $page
 * @var int $totalPages
 * @var int $total
 */

$statusBadge = [
    'pending' => 'badge--warning',
    'confirmed' => 'badge--info',
    'checked_in' => 'badge--success',
    'checked_out' => 'badge--neutral',
    'cancelled' => 'badge--danger',
    'no_show' => 'badge--danger',
    'rejected' => 'badge--danger',
];

$statusLabel = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'Bookings']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <div>
    <h2>Bookings</h2>
    <p class="text-med mt-2"><?= (int) $total ?> total</p>
  </div>

  <?php if ($canCreate): ?>
    <a href="<?= route('bookings.create') ?>" class="btn btn-primary">
      <?= icon('plus', 'icon icon-sm') ?>
      <span>New Booking</span>
    </a>
  <?php endif; ?>
</div>

<div class="card glass" data-animate>
  <?php if ($bookings === []): ?>
    <?= partial('empty-state', [
        'title' => 'No bookings yet',
        'description' => $canCreate ? 'Create the first booking to see it here.' : 'Nothing in scope for your account yet.',
        'icon' => '🗓️',
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Booking</th>
            <th>Guest</th>
            <th>Hotel</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Status</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr class="booking-row" data-href="<?= route('bookings.edit', ['id' => $b['id']]) ?>">
              <td class="font-mono"><?= e((string) $b['booking_id']) ?></td>
              <td><?= e((string) $b['guest_name']) ?></td>
              <td><?= e((string) $b['hotel_name']) ?></td>
              <td><?= e((string) $b['checkin_date']) ?></td>
              <td><?= e((string) $b['checkout_date']) ?></td>
              <td><span class="badge <?= e($statusBadge[$b['status']] ?? 'badge--neutral') ?>"><?= e($statusLabel((string) $b['status'])) ?></span></td>
              <td><?= e(money((float) $b['total_room_rent'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="<?= route('bookings.index') ?>?page=<?= $p ?>"><button type="button" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></button></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php View::section('scripts'); ?>
<script>
  document.querySelectorAll('.booking-row[data-href]').forEach((row) => {
    row.addEventListener('click', () => { window.location.href = row.dataset.href; });
  });
</script>
<?php View::endSection(); ?>
