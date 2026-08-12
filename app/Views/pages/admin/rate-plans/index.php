<?php

use App\Core\View;

/**
 * Standalone, cross-hotel Rate Plans page — server-rendered, same
 * reasoning as the Rooms page. Feeds the booking form's per-day rent
 * suggestions once attached to a hotel (see BookingController::roomsForHotel()).
 *
 * @var array<int, array<string, mixed>> $ratePlans
 * @var array<int, array<string, mixed>> $filterHotels
 * @var array<int, array<string, mixed>> $addableHotels
 * @var string|null $selectedHotelId
 * @var array<int, string> $roomTypes
 * @var array<int, string> $seasons
 * @var bool $canCreate
 */

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'Rate Plans']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <div>
    <h2>Rate Plans</h2>
    <p class="text-med mt-2"><?= count($ratePlans) ?> plan<?= count($ratePlans) === 1 ? '' : 's' ?><?= $selectedHotelId !== null ? ' at this hotel' : ' across your hotels' ?></p>
  </div>
  <?php if ($canCreate): ?>
    <button type="button" class="btn btn-primary" data-modal-open="rateplan-add">
      <?= icon('plus', 'icon icon-sm') ?>
      <span>Add Rate Plan</span>
    </button>
  <?php endif; ?>
</div>

<form method="GET" action="<?= route('rate-plans.index') ?>" class="card glass filter-bar mb-6" data-animate>
  <div class="filter-bar__row">
    <div class="field">
      <label for="filter-hotel">Hotel</label>
      <select class="select" id="filter-hotel" name="hotel_id" data-auto-submit>
        <option value="">All Hotels</option>
        <?php foreach ($filterHotels as $h): ?>
          <option value="<?= e($h['id']) ?>" <?= $selectedHotelId === $h['id'] ? 'selected' : '' ?>><?= e($h['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</form>

<?php if ($ratePlans === []): ?>
  <?= partial('empty-state', [
      'title' => 'No rate plans yet',
      'description' => $canCreate ? 'Set seasonal pricing by room type to feed the booking form\'s suggested rates.' : 'Ask your Admin to set up rate plans for your assigned hotels.',
      'icon' => '💰',
  ]) ?>
<?php else: ?>
  <div class="card glass" data-animate>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Plan</th><th>Hotel</th><th>Room Type</th><th>Occupancy</th><th>Season</th><th>Base Price</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($ratePlans as $plan): ?>
            <tr>
              <td><?= e($plan['plan_name']) ?></td>
              <td><?= e($plan['hotel_name']) ?></td>
              <td><?= e($plan['room_type']) ?></td>
              <td><?= e($plan['occupancy_type'] ?: '—') ?></td>
              <td><span class="badge badge--<?= $plan['season'] === 'Peak' ? 'warning' : 'info' ?>"><?= e($plan['season']) ?></span></td>
              <td><?= money($plan['base_price']) ?></td>
              <td class="booking-row__actions">
                <?php if (can('rate_plans', 'edit', $plan['hotel_id'])): ?>
                  <button type="button" class="link-btn" data-modal-open="rateplan-edit-<?= e($plan['id']) ?>"><?= icon('edit-2', 'icon icon-sm') ?> Edit</button>
                <?php endif; ?>
                <?php if (can('rate_plans', 'delete', $plan['hotel_id'])): ?>
                  <form method="POST" action="<?= route('rate-plans.destroy', ['id' => $plan['id']]) ?>" data-confirm-submit data-confirm-title="Remove this rate plan?" data-confirm-message="<?= e($plan['plan_name']) ?> at <?= e($plan['hotel_name']) ?> will be soft-deleted.">
                    <?= csrf_field() ?>
                    <?php if ($selectedHotelId !== null): ?><input type="hidden" name="_redirect_hotel_id" value="<?= e($selectedHotelId) ?>"><?php endif; ?>
                    <button type="submit" class="link-btn link-btn--danger"><?= icon('trash', 'icon icon-sm') ?> Remove</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php foreach ($ratePlans as $plan): ?>
    <?php if (can('rate_plans', 'edit', $plan['hotel_id'])): ?>
      <?= partial('admin/rate-plan-modal', [
          'modalId' => 'rateplan-edit-' . $plan['id'],
          'formAction' => route('rate-plans.update', ['id' => $plan['id']]),
          'plan' => $plan,
          'roomTypes' => $roomTypes,
          'seasons' => $seasons,
          'heading' => 'Edit ' . $plan['plan_name'] . ' — ' . $plan['hotel_name'],
          'redirectHotelId' => $selectedHotelId,
      ]) ?>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>

<?php if ($canCreate): ?>
  <?= partial('admin/rate-plan-modal', [
      'modalId' => 'rateplan-add',
      'formAction' => route('rate-plans.store'),
      'plan' => null,
      'roomTypes' => $roomTypes,
      'seasons' => $seasons,
      'heading' => 'Add Rate Plan',
      'hotels' => $addableHotels,
      'redirectHotelId' => $selectedHotelId,
  ]) ?>
<?php endif; ?>
