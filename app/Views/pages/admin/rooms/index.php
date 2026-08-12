<?php

use App\Core\View;

/**
 * Standalone, cross-hotel Rooms page — server-rendered (room counts
 * across even a large portfolio are small relative to bookings, so
 * this follows the Hotels-list precedent, not the AJAX/JSON/pagination
 * treatment Bookings gets). Grid and table render the same rows; a
 * small view-toggle (public/assets/js/rooms.js) shows one at a time,
 * persisted in localStorage.
 *
 * @var array<int, array<string, mixed>> $rooms
 * @var array<int, array<string, mixed>> $filterHotels
 * @var array<int, array<string, mixed>> $addableHotels
 * @var string|null $selectedHotelId
 * @var array<int, string> $roomTypes
 * @var array<int, string> $roomStatuses
 * @var bool $canCreate
 */

$statusBadge = static fn (string $status): string => match ($status) {
    'Available' => 'success',
    'Maintenance' => 'warning',
    default => 'neutral',
};

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'Rooms']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <div>
    <h2>Rooms</h2>
    <p class="text-med mt-2"><?= count($rooms) ?> room<?= count($rooms) === 1 ? '' : 's' ?><?= $selectedHotelId !== null ? ' at this hotel' : ' across your hotels' ?></p>
  </div>
  <?php if ($canCreate): ?>
    <button type="button" class="btn btn-primary" data-modal-open="room-add">
      <?= icon('plus', 'icon icon-sm') ?>
      <span>Add Room</span>
    </button>
  <?php endif; ?>
</div>

<form method="GET" action="<?= route('rooms.index') ?>" class="card glass filter-bar mb-6" data-animate>
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

<?php if ($rooms === []): ?>
  <?= partial('empty-state', [
      'title' => 'No rooms yet',
      'description' => $canCreate ? 'Add a room to start building rate plans and bookings.' : 'Ask your Admin to add rooms to your assigned hotels.',
      'icon' => '🛏️',
  ]) ?>
<?php else: ?>

  <div class="view-toggle mb-6" role="group" aria-label="View" data-animate>
    <button type="button" class="view-toggle__btn active" data-view-toggle="grid">
      <?= icon('grid', 'icon icon-sm') ?>
      <span>Grid</span>
    </button>
    <button type="button" class="view-toggle__btn" data-view-toggle="table">
      <?= icon('list', 'icon icon-sm') ?>
      <span>Table</span>
    </button>
  </div>

  <div data-view-panel="grid">
    <div class="room-grid" data-animate>
      <?php foreach ($rooms as $room): ?>
        <?php $images = !empty($room['images']) ? (json_decode((string) $room['images'], true) ?: []) : []; ?>
        <div class="room-card glass card-hover">
          <div class="room-card__image" <?= $images !== [] ? 'style="background-image: url(\'' . e(asset($images[0])) . '\')"' : '' ?>>
            <?php if ($images === []): ?><?= icon('image', 'room-card__placeholder-icon') ?><?php endif; ?>
            <span class="badge room-card__status badge--<?= $statusBadge($room['status']) ?>"><?= e($room['status']) ?></span>
          </div>
          <div class="room-card__body">
            <div class="flex-between">
              <h3 class="room-card__number font-mono"><?= e($room['room_number']) ?></h3>
              <span class="badge badge--info"><?= e($room['room_type']) ?></span>
            </div>
            <p class="room-card__hotel">
              <?= icon('home', 'icon icon-sm') ?>
              <span><?= e($room['hotel_name']) ?></span>
            </p>
            <p class="room-card__capacity">
              <?= icon('users', 'icon icon-sm') ?>
              <span><?= (int) $room['max_adults'] ?> Adults · <?= (int) $room['max_children'] ?> Children</span>
            </p>
            <div class="room-card__footer">
              <span class="price-badge"><?= money($room['base_price']) ?><small>/night</small></span>
              <?php if (can('rooms', 'edit', $room['hotel_id']) || can('rooms', 'delete', $room['hotel_id'])): ?>
                <div class="booking-row__actions">
                  <?php if (can('rooms', 'edit', $room['hotel_id'])): ?>
                    <button type="button" class="link-btn" data-modal-open="room-edit-<?= e($room['id']) ?>"><?= icon('edit-2', 'icon icon-sm') ?> Edit</button>
                  <?php endif; ?>
                  <?php if (can('rooms', 'delete', $room['hotel_id'])): ?>
                    <form method="POST" action="<?= route('rooms.destroy', ['id' => $room['id']]) ?>" data-confirm-submit data-confirm-title="Remove this room?" data-confirm-message="Room <?= e($room['room_number']) ?> at <?= e($room['hotel_name']) ?> will be soft-deleted.">
                      <?= csrf_field() ?>
                      <?php if ($selectedHotelId !== null): ?><input type="hidden" name="_redirect_hotel_id" value="<?= e($selectedHotelId) ?>"><?php endif; ?>
                      <button type="submit" class="link-btn link-btn--danger"><?= icon('trash', 'icon icon-sm') ?> Remove</button>
                    </form>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div data-view-panel="table" hidden>
    <div class="card glass" data-animate>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>Room #</th><th>Hotel</th><th>Type</th><th>Pax</th><th>Max Adults</th><th>Max Children</th><th>Base Price</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($rooms as $room): ?>
              <tr>
                <td class="font-mono"><?= e($room['room_number']) ?></td>
                <td><?= e($room['hotel_name']) ?></td>
                <td><?= e($room['room_type']) ?></td>
                <td><?= (int) $room['pax'] ?></td>
                <td><?= (int) $room['max_adults'] ?></td>
                <td><?= (int) $room['max_children'] ?></td>
                <td><?= money($room['base_price']) ?></td>
                <td><span class="badge badge--<?= $statusBadge($room['status']) ?>"><?= e($room['status']) ?></span></td>
                <td class="booking-row__actions">
                  <?php if (can('rooms', 'edit', $room['hotel_id'])): ?>
                    <button type="button" class="link-btn" data-modal-open="room-edit-<?= e($room['id']) ?>"><?= icon('edit-2', 'icon icon-sm') ?> Edit</button>
                  <?php endif; ?>
                  <?php if (can('rooms', 'delete', $room['hotel_id'])): ?>
                    <form method="POST" action="<?= route('rooms.destroy', ['id' => $room['id']]) ?>" data-confirm-submit data-confirm-title="Remove this room?" data-confirm-message="Room <?= e($room['room_number']) ?> at <?= e($room['hotel_name']) ?> will be soft-deleted.">
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
  </div>

  <?php foreach ($rooms as $room): ?>
    <?php if (can('rooms', 'edit', $room['hotel_id'])): ?>
      <?= partial('admin/room-modal', [
          'modalId' => 'room-edit-' . $room['id'],
          'formAction' => route('rooms.update', ['id' => $room['id']]),
          'room' => $room,
          'roomTypes' => $roomTypes,
          'roomStatuses' => $roomStatuses,
          'heading' => 'Edit Room ' . $room['room_number'] . ' — ' . $room['hotel_name'],
          'redirectHotelId' => $selectedHotelId,
      ]) ?>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>

<?php if ($canCreate): ?>
  <?= partial('admin/room-modal', [
      'modalId' => 'room-add',
      'formAction' => route('rooms.store'),
      'room' => null,
      'roomTypes' => $roomTypes,
      'roomStatuses' => $roomStatuses,
      'heading' => 'Add Room',
      'hotels' => $addableHotels,
      'redirectHotelId' => $selectedHotelId,
  ]) ?>
<?php endif; ?>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/rooms.js') ?>"></script>
<?php View::endSection(); ?>
