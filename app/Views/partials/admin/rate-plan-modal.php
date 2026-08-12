<?php

/**
 * @var string $modalId
 * @var string $formAction
 * @var array<string, mixed>|null $plan
 * @var array<int, string> $roomTypes
 * @var array<int, string> $seasons
 * @var string $heading
 * @var array<int, array<string, mixed>>|null $hotels present only for the standalone Rate Plans page's Add-Plan modal (cross-hotel picker); the hub's per-hotel tab never passes it, and edit modals never show it — a plan's hotel is fixed once it exists.
 * @var string|null $redirectHotelId the standalone page's current ?hotel_id= filter, round-tripped via a hidden field so save/delete lands back on the same filtered view
 */
$hotels ??= null;
$redirectHotelId ??= null;
?>
<div class="modal-backdrop" id="<?= e($modalId) ?>" hidden>
  <div class="modal glass">
    <div class="modal__header">
      <h3><?= e($heading) ?></h3>
      <button type="button" class="modal__close" data-modal-close aria-label="Close"><?= icon('x', 'icon') ?></button>
    </div>
    <form method="POST" action="<?= e($formAction) ?>">
      <?= csrf_field() ?>
      <?php if ($redirectHotelId !== null): ?>
        <input type="hidden" name="_redirect_hotel_id" value="<?= e($redirectHotelId) ?>">
      <?php endif; ?>

      <?php if ($plan === null && $hotels !== null): ?>
        <div class="field mb-4">
          <label for="<?= e($modalId) ?>-hotel">Hotel</label>
          <select class="select" id="<?= e($modalId) ?>-hotel" name="hotel_id" required>
            <option value="">Select a hotel…</option>
            <?php foreach ($hotels as $h): ?>
              <option value="<?= e($h['id']) ?>" <?= ($redirectHotelId === $h['id'] || count($hotels) === 1) ? 'selected' : '' ?>><?= e($h['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <div class="field mb-4">
        <label for="<?= e($modalId) ?>-plan-name">Plan Name</label>
        <input class="input" type="text" id="<?= e($modalId) ?>-plan-name" name="plan_name" value="<?= e((string) ($plan['plan_name'] ?? '')) ?>" required autofocus>
      </div>
      <div class="booking-form__cols-2 mb-4">
        <div class="field">
          <label for="<?= e($modalId) ?>-room-type">Room Type</label>
          <select class="select" id="<?= e($modalId) ?>-room-type" name="room_type">
            <?php foreach ($roomTypes as $type): ?>
              <option value="<?= e($type) ?>" <?= ($plan['room_type'] ?? 'Single') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="<?= e($modalId) ?>-season">Season</label>
          <select class="select" id="<?= e($modalId) ?>-season" name="season">
            <?php foreach ($seasons as $season): ?>
              <option value="<?= e($season) ?>" <?= ($plan['season'] ?? 'Regular') === $season ? 'selected' : '' ?>><?= e($season) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field mb-4">
        <label for="<?= e($modalId) ?>-occupancy">Occupancy Type <span class="text-low">(optional)</span></label>
        <input class="input" type="text" id="<?= e($modalId) ?>-occupancy" name="occupancy_type" value="<?= e((string) ($plan['occupancy_type'] ?? '')) ?>" placeholder="e.g. Double occupancy">
      </div>
      <div class="field mb-4">
        <label for="<?= e($modalId) ?>-base-price">Base Price (₹ / night)</label>
        <input class="input" type="number" min="0" step="0.01" id="<?= e($modalId) ?>-base-price" name="base_price" value="<?= e((string) ($plan['base_price'] ?? '')) ?>" required>
      </div>
      <div class="modal__actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= $plan !== null ? 'Save Plan' : 'Add Plan' ?></button>
      </div>
    </form>
  </div>
</div>
