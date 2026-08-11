<?php
use App\Core\View;

/**
 * @var array<string, mixed>|null $user
 * @var string|null $roleName
 * @var int $roleLevel
 * @var array<int, array<string, mixed>> $hotels
 * @var bool $isUnrestricted
 */

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'Dashboard']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <div>
    <h2>Welcome, <?= e((string) ($user['full_name'] ?? 'there')) ?></h2>
    <p class="text-med mt-2">
      Role: <span class="badge badge--info"><?= e(ucwords(str_replace('_', ' ', (string) $roleName))) ?></span>
      <span class="text-low">· Level <?= (int) $roleLevel ?></span>
    </p>
  </div>

  <?php if (can('bookings', 'create')): ?>
    <a href="#" class="btn btn-primary" aria-disabled="true">+ New Booking</a>
  <?php endif; ?>
</div>

<div class="showcase-grid" data-animate>
  <div class="stat-card glass">
    <div class="stat-card__icon"><?= icon('home') ?></div>
    <div class="stat-card__value font-mono"><?= count($hotels) ?></div>
    <div class="stat-card__label">Hotels you can see</div>
  </div>

  <div class="stat-card glass">
    <div class="stat-card__icon"><?= icon('sliders') ?></div>
    <div class="stat-card__value font-mono"><?= $isUnrestricted ? 'All' : count($hotels) ?></div>
    <div class="stat-card__label">Hotel access scope</div>
  </div>

  <div class="stat-card glass">
    <div class="stat-card__icon"><?= icon('check') ?></div>
    <div class="stat-card__value font-mono"><?= can('bookings', 'create') ? 'Yes' : 'No' ?></div>
    <div class="stat-card__label">Can create bookings</div>
  </div>
</div>

<div class="card glass mt-8" data-animate>
  <div class="flex-between mb-4">
    <h3>Your hotels</h3>
    <span class="badge <?= $isUnrestricted ? 'badge--info' : 'badge--neutral' ?>">
      <?= $isUnrestricted ? 'Unrestricted access' : 'Filtered / scoped view' ?>
    </span>
  </div>

  <?php if ($hotels === []): ?>
    <?= partial('empty-state', [
        'title' => 'No hotels assigned',
        'description' => 'Ask your Admin to assign you to a hotel to see it here.',
        'icon' => '🏨',
    ]) ?>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Hotel</th>
            <th>City</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($hotels as $hotel): ?>
            <tr>
              <td><?= e((string) $hotel['name']) ?></td>
              <td><?= e((string) ($hotel['city'] ?? '—')) ?></td>
              <td><span class="badge badge--success"><?= e((string) $hotel['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
