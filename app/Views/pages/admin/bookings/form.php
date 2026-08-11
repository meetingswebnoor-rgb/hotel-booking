<?php

use App\Core\View;

/**
 * Single rich glass form — shared by create and edit. The hotel
 * (Property) is only editable for a brand new booking; once a booking
 * exists it's locked (a new hotel means new room/rate inventory, so
 * changing it mid-edit would invalidate the room lines).
 *
 * @var array<string, mixed>|null $booking
 * @var array<int, array<string, mixed>> $hotels
 * @var array<int, array<string, mixed>> $otas
 * @var string $suggestedBookingId
 * @var string $formAction
 */

$statusOptions = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'checked_in' => 'Checked In',
    'checked_out' => 'Checked Out',
    'cancelled' => 'Cancelled',
    'rejected' => 'Rejected',
    'no_show' => 'No Show',
];

$paymentStatusOptions = [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'hold' => 'Hold',
    'disputed' => 'Disputed',
];

$errors = form_errors();

$roomLinesData = $booking !== null
    ? (json_decode((string) $booking['rooms'], true) ?: [])
    : old_array('rooms');

if ($roomLinesData === []) {
    $roomLinesData = [[]];
}

$fixedHotel = $hotels[0] ?? null;

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', [
    'items' => [
        ['label' => 'Home', 'href' => route('home')],
        ['label' => 'Bookings', 'href' => route('bookings.index')],
        ['label' => $booking !== null ? 'Edit' : 'New Booking'],
    ],
]) ?>
<?php View::endSection(); ?>

<?php if ($hotels === []): ?>
  <?= partial('empty-state', [
      'title' => 'No hotels assigned',
      'description' => 'Ask your Admin to assign you to a hotel before creating bookings.',
      'icon' => '🏨',
  ]) ?>
<?php else: ?>

<form
  method="POST"
  action="<?= e($formAction) ?>"
  class="booking-form-grid"
  data-booking-form
  data-hotel-id="<?= e((string) ($booking['hotel_id'] ?? '')) ?>"
  data-hotel-commission="<?= e((string) ($fixedHotel['commission_pct'] ?? 0)) ?>"
>
  <?= csrf_field() ?>

  <div class="booking-form__main">
    <div class="card glass mb-6" data-animate>
      <h3 class="mb-4">Basic Details</h3>
      <div class="booking-form__cols-2">
        <div class="field">
          <label for="booking_id">Booking ID</label>
          <div class="field-with-status">
            <input
              class="input"
              type="text"
              id="booking_id"
              name="booking_id"
              value="<?= e((string) ($booking['booking_id'] ?? old('booking_id', $suggestedBookingId))) ?>"
              data-booking-id-input
              data-exclude-id="<?= e((string) ($booking['id'] ?? '')) ?>"
              autocomplete="off"
              required
            >
            <span class="field-status-icon" data-booking-id-status></span>
          </div>
          <?php if (isset($errors['booking_id'])): ?><span class="field-error"><?= e($errors['booking_id'][0]) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="guest_name">Guest Name</label>
          <input class="input" type="text" id="guest_name" name="guest_name" value="<?= e((string) ($booking['guest_name'] ?? old('guest_name'))) ?>" required>
          <?php if (isset($errors['guest_name'])): ?><span class="field-error"><?= e($errors['guest_name'][0]) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="guest_mobile">Guest Mobile</label>
          <input class="input" type="tel" id="guest_mobile" name="guest_mobile" value="<?= e((string) ($booking['guest_mobile'] ?? old('guest_mobile'))) ?>" required>
          <?php if (isset($errors['guest_mobile'])): ?><span class="field-error"><?= e($errors['guest_mobile'][0]) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="guest_email">Guest Email <span class="text-low">(optional)</span></label>
          <input class="input" type="email" id="guest_email" name="guest_email" value="<?= e((string) ($booking['guest_email'] ?? old('guest_email'))) ?>">
        </div>

        <div class="field">
          <label for="hotel_id">Property</label>
          <?php if ($booking !== null): ?>
            <input class="input" type="text" value="<?= e((string) ($fixedHotel['name'] ?? '')) ?>" disabled>
            <input type="hidden" name="hotel_id" value="<?= e((string) $booking['hotel_id']) ?>">
          <?php else: ?>
            <select class="select" id="hotel_id" name="hotel_id" data-hotel-select required>
              <option value="">Select a hotel…</option>
              <?php foreach ($hotels as $h): ?>
                <option
                  value="<?= e($h['id']) ?>"
                  data-commission="<?= e((string) $h['commission_pct']) ?>"
                  <?= old('hotel_id') === $h['id'] ? 'selected' : '' ?>
                ><?= e($h['name']) ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
          <?php if (isset($errors['hotel_id'])): ?><span class="field-error"><?= e($errors['hotel_id'][0]) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="ota_id">OTA Source</label>
          <select class="select" id="ota_id" name="ota_id" data-ota-select required>
            <?php foreach ($otas as $o): ?>
              <option
                value="<?= e($o['id']) ?>"
                data-commission="<?= e((string) $o['commission_pct']) ?>"
                <?= ((string) ($booking['ota_id'] ?? old('ota_id'))) === $o['id'] ? 'selected' : '' ?>
              ><?= e($o['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="status">Booking Status</label>
          <select class="select" id="status" name="status">
            <?php foreach ($statusOptions as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= ((string) ($booking['status'] ?? old('status', 'pending'))) === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="card glass mb-6" data-animate>
      <h3 class="mb-4">Dates</h3>
      <div class="booking-form__cols-3">
        <div class="field">
          <label for="booking_date">Booking Date</label>
          <input class="input" type="date" id="booking_date" name="booking_date" value="<?= e((string) ($booking['booking_date'] ?? old('booking_date', date('Y-m-d')))) ?>" required>
        </div>
        <div class="field">
          <label for="checkin_date">Check-in</label>
          <input class="input" type="date" id="checkin_date" name="checkin_date" value="<?= e((string) ($booking['checkin_date'] ?? old('checkin_date'))) ?>" data-checkin required>
        </div>
        <div class="field">
          <label for="checkout_date">Check-out</label>
          <input class="input" type="date" id="checkout_date" name="checkout_date" value="<?= e((string) ($booking['checkout_date'] ?? old('checkout_date'))) ?>" data-checkout required>
          <?php if (isset($errors['checkout_date'])): ?><span class="field-error"><?= e($errors['checkout_date'][0]) ?></span><?php endif; ?>
        </div>
      </div>
      <p class="text-low mt-3" style="font-size: 0.8125rem;">Nights: <strong data-nights-display>0</strong></p>
    </div>

    <div class="card glass mb-6" data-animate>
      <div class="flex-between mb-4">
        <h3>Rooms</h3>
        <button type="button" class="btn btn-ghost btn-sm" data-add-room>
          <?= icon('plus', 'icon icon-sm') ?>
          <span>Add another room</span>
        </button>
      </div>

      <?php if (isset($errors['rooms'])): ?><div class="alert alert--error mb-4"><?= e($errors['rooms'][0]) ?></div><?php endif; ?>

      <div class="grid gap-4" data-room-lines>
        <?php foreach ($roomLinesData as $i => $line): ?>
          <?= partial('admin/booking-room-line', ['index' => $i, 'line' => $line]) ?>
        <?php endforeach; ?>
      </div>

      <template data-room-line-template>
        <?= partial('admin/booking-room-line', ['index' => '__INDEX__', 'line' => []]) ?>
      </template>
    </div>

    <div class="card glass mb-6" data-animate>
      <h3 class="mb-4">Payment</h3>
      <div class="field">
        <label for="ota_payment_status">OTA Payment Status</label>
        <select class="select" id="ota_payment_status" name="ota_payment_status" style="max-width: 280px;">
          <?php foreach ($paymentStatusOptions as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= ((string) ($booking['ota_payment_status'] ?? old('ota_payment_status', 'pending'))) === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field mt-4">
        <label for="payment_remarks">Payment Remarks</label>
        <textarea class="input" id="payment_remarks" name="payment_remarks" rows="2"><?= e((string) ($booking['payment_remarks'] ?? old('payment_remarks'))) ?></textarea>
      </div>
      <div class="field mt-4">
        <label for="internal_notes">Internal Notes</label>
        <textarea class="input" id="internal_notes" name="internal_notes" rows="2"><?= e((string) ($booking['internal_notes'] ?? old('internal_notes'))) ?></textarea>
      </div>
    </div>

    <div class="flex gap-3" data-animate>
      <button type="submit" class="btn btn-primary btn-lg">Save Booking</button>
      <a href="<?= route('bookings.index') ?>" class="btn btn-ghost btn-lg">Cancel</a>
      <?php if ($booking !== null): ?>
        <a href="<?= route('bookings.voucher', ['id' => $booking['id']]) ?>" target="_blank" class="btn btn-ghost btn-lg">
          <?= icon('printer', 'icon icon-sm') ?>
          <span>Print Voucher</span>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?= partial('admin/booking-calc-summary') ?>
</form>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/booking-form.js') ?>"></script>
<?php View::endSection(); ?>
<?php endif; ?>
