<?php

use App\Core\View;

/**
 * Glass card grid — one card per hotel the caller is scoped to see,
 * with a hero image, city, room count, status, and a couple of quick
 * stats. Small/simple enough (a handful of hotels, not thousands of
 * bookings) that it's server-rendered directly, unlike the bookings
 * list's AJAX/JSON/pagination treatment.
 *
 * @var bool $canCreate
 * @var array<int, array<string, mixed>> $hotels
 */

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'Hotels']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <h2>Hotels</h2>
  <?php if ($canCreate): ?>
    <a href="<?= route('hotels.create') ?>" class="btn btn-primary">
      <?= icon('plus', 'icon icon-sm') ?>
      <span>New Hotel</span>
    </a>
  <?php endif; ?>
</div>

<?php if ($hotels === []): ?>
  <?= partial('empty-state', [
      'title' => 'No hotels yet',
      'description' => $canCreate ? 'Add your first property to start taking bookings.' : 'Ask your Admin to assign you to a hotel.',
      'icon' => '🏨',
  ]) ?>
<?php else: ?>
  <div class="hotel-grid" data-animate>
    <?php foreach ($hotels as $h): ?>
      <a class="hotel-card glass card-hover" href="<?= route('hotels.show', ['id' => $h['id']]) ?>">
        <div class="hotel-card__image" <?= $h['hero_image'] ? 'style="background-image: url(\'' . e(asset($h['hero_image'])) . '\')"' : '' ?>>
          <?php if (!$h['hero_image']): ?><?= icon('home', 'hotel-card__placeholder-icon') ?><?php endif; ?>
          <span class="badge hotel-card__status badge--<?= $h['status'] === 'active' ? 'success' : ($h['status'] === 'inactive' ? 'danger' : 'warning') ?>">
            <?= e(ucwords(str_replace('_', ' ', $h['status']))) ?>
          </span>
        </div>
        <div class="hotel-card__body">
          <h3 class="hotel-card__name"><?= e($h['name']) ?></h3>
          <p class="hotel-card__location">
            <?= icon('map-pin', 'icon icon-sm') ?>
            <span><?= e(trim(($h['city'] ?: '—') . ', ' . $h['country'], ', ')) ?></span>
          </p>
          <div class="hotel-card__stats">
            <div class="hotel-card__stat">
              <strong><?= (int) $h['room_count'] ?></strong>
              <span>Rooms</span>
            </div>
            <div class="hotel-card__stat">
              <strong><?= (int) $h['booking_count'] ?></strong>
              <span>Bookings</span>
            </div>
            <div class="hotel-card__stat">
              <strong><?= e(number_format((float) $h['commission_pct'], 1)) ?>%</strong>
              <span>Commission</span>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
