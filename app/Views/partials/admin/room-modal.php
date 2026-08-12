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
 * @var array<int, array<string, mixed>>|null $hotels present only for the standalone Rooms page's Add-Room modal (cross-hotel picker); the hub's per-hotel tab never passes it, and edit modals never show it — a room's hotel is fixed once it exists.
 * @var string|null $redirectHotelId the standalone page's current ?hotel_id= filter, round-tripped via a hidden field so save/delete lands back on the same filtered view
 */
$hotels ??= null;
$redirectHotelId ??= null;
$existingImages = $room !== null && !empty($room['images']) ? (json_decode((string) $room['images'], true) ?: []) : [];
?>
<div class="modal-backdrop" id="<?= e($modalId) ?>" hidden>
  <div class="modal glass">
    <div class="modal__header">
      <h3><?= e($heading) ?></h3>
      <button type="button" class="modal__close" data-modal-close aria-label="Close"><?= icon('x', 'icon') ?></button>
    </div>
    <form method="POST" action="<?= e($formAction) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <?php if ($redirectHotelId !== null): ?>
        <input type="hidden" name="_redirect_hotel_id" value="<?= e($redirectHotelId) ?>">
      <?php endif; ?>

      <?php if ($room === null && $hotels !== null): ?>
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
      <div class="field mb-4">
        <label for="<?= e($modalId) ?>-image">Room Image <span class="text-low">(optional)</span></label>
        <?php if ($existingImages !== []): ?>
          <img class="hotel-photo-preview" src="<?= e(asset($existingImages[0])) ?>" alt="Current room image">
        <?php endif; ?>
        <input class="input" type="file" id="<?= e($modalId) ?>-image" name="room_image" accept="image/jpeg,image/png,image/webp">
        <span class="text-low" style="font-size: 0.8125rem;">JPEG, PNG, or WebP — up to 5MB.</span>
      </div>
      <div class="modal__actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= $room !== null ? 'Save Room' : 'Add Room' ?></button>
      </div>
    </form>
  </div>
</div>
