<?php

/**
 * One add-room modal, or one per existing room (id'd per room) so
 * editing needs no JS field population — the form is just pre-filled
 * server-side. Room counts per hotel are small enough that this
 * doesn't bloat the page meaningfully.
 *
 * @var string $modalId
 * @var string $formAction
 * @var array<string, mixed>|null $room
 * @var array<int, string> $roomTypes
 * @var array<int, string> $roomStatuses
 * @var string $heading
 */
?>
<div class="modal-backdrop" id="<?= e($modalId) ?>" hidden>
  <div class="modal glass">
    <div class="modal__header">
      <h3><?= e($heading) ?></h3>
      <button type="button" class="modal__close" data-modal-close aria-label="Close"><?= icon('x', 'icon') ?></button>
    </div>
    <form method="POST" action="<?= e($formAction) ?>">
      <?= csrf_field() ?>
      <div class="field mb-4">
        <label for="<?= e($modalId) ?>-room-number">Room Number</label>
        <input class="input" type="text" id="<?= e($modalId) ?>-room-number" name="room_number" value="<?= e((string) ($room['room_number'] ?? '')) ?>" required autofocus>
      </div>
      <div class="booking-form__cols-2 mb-4">
        <div class="field">
          <label for="<?= e($modalId) ?>-room-type">Room Type</label>
          <select class="select" id="<?= e($modalId) ?>-room-type" name="room_type">
            <?php foreach ($roomTypes as $type): ?>
              <option value="<?= e($type) ?>" <?= ($room['room_type'] ?? 'Single') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="<?= e($modalId) ?>-status">Status</label>
          <select class="select" id="<?= e($modalId) ?>-status" name="status">
            <?php foreach ($roomStatuses as $status): ?>
              <option value="<?= e($status) ?>" <?= ($room['status'] ?? 'Available') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="booking-form__cols-3 mb-4">
        <div class="field">
          <label for="<?= e($modalId) ?>-pax">Pax</label>
          <input class="input" type="number" min="1" id="<?= e($modalId) ?>-pax" name="pax" value="<?= e((string) ($room['pax'] ?? 2)) ?>" required>
        </div>
        <div class="field">
          <label for="<?= e($modalId) ?>-max-adults">Max Adults</label>
          <input class="input" type="number" min="1" id="<?= e($modalId) ?>-max-adults" name="max_adults" value="<?= e((string) ($room['max_adults'] ?? 2)) ?>" required>
        </div>
        <div class="field">
          <label for="<?= e($modalId) ?>-max-children">Max Children</label>
          <input class="input" type="number" min="0" id="<?= e($modalId) ?>-max-children" name="max_children" value="<?= e((string) ($room['max_children'] ?? 0)) ?>" required>
        </div>
      </div>
      <div class="field mb-4">
        <label for="<?= e($modalId) ?>-base-price">Base Price (₹ / night)</label>
        <input class="input" type="number" min="0" step="0.01" id="<?= e($modalId) ?>-base-price" name="base_price" value="<?= e((string) ($room['base_price'] ?? '')) ?>" required>
      </div>
      <div class="modal__actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= $room !== null ? 'Save Room' : 'Add Room' ?></button>
      </div>
    </form>
  </div>
</div>
