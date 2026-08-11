<?php
/**
 * @var array<string, mixed> $booking
 * @var array<string, mixed>|null $hotel
 * @var array<string, mixed>|null $ota
 * @var array<int, array<string, mixed>> $rooms
 */
$nights = (int) $booking['nights'];
$grandTotal = (float) $booking['total_room_rent'] + (float) $booking['hotel_gst'];
?>
<div class="voucher-actions no-print">
  <button type="button" class="btn btn-primary" onclick="window.print()">
    <?= icon('printer', 'icon icon-sm') ?>
    <span>Print</span>
  </button>
  <a href="<?= route('bookings.edit', ['id' => $booking['id']]) ?>" class="btn btn-ghost">Back to booking</a>
</div>

<div class="voucher-paper">
  <header class="voucher-header">
    <div>
      <h1><?= e((string) ($hotel['name'] ?? 'Hotel')) ?></h1>
      <p><?= e((string) ($hotel['address'] ?? '')) ?><?= !empty($hotel['city']) ? ', ' . e((string) $hotel['city']) : '' ?></p>
      <p>
        <?= e((string) ($hotel['mobile'] ?? '')) ?>
        <?= !empty($hotel['email']) ? ' · ' . e((string) $hotel['email']) : '' ?>
      </p>
    </div>
    <div class="voucher-badge">
      <strong>Booking Voucher</strong>
      <span class="font-mono"><?= e((string) $booking['booking_id']) ?></span>
    </div>
  </header>

  <section class="voucher-grid">
    <div><span class="voucher-label">Guest</span><p><?= e((string) $booking['guest_name']) ?></p></div>
    <div><span class="voucher-label">Mobile</span><p><?= e((string) $booking['guest_mobile']) ?></p></div>
    <div><span class="voucher-label">Source</span><p><?= e((string) ($ota['name'] ?? 'Direct')) ?></p></div>
    <div><span class="voucher-label">Status</span><p><?= e(ucwords(str_replace('_', ' ', (string) $booking['status']))) ?></p></div>
    <div><span class="voucher-label">Check-in</span><p><?= e((string) $booking['checkin_date']) ?></p></div>
    <div><span class="voucher-label">Check-out</span><p><?= e((string) $booking['checkout_date']) ?></p></div>
    <div><span class="voucher-label">Nights</span><p><?= $nights ?></p></div>
    <div><span class="voucher-label">Guests</span><p><?= (int) $booking['adults'] ?> Adults, <?= (int) $booking['children'] ?> Children</p></div>
  </section>

  <table class="voucher-table">
    <thead>
      <tr>
        <th>Room Type</th>
        <th>Qty</th>
        <th>Rate / Night</th>
        <th>Nights</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rooms as $line): ?>
        <?php $subtotal = (float) ($line['nightly_rate'] ?? 0) * (int) ($line['quantity'] ?? 1) * $nights; ?>
        <tr>
          <td><?= e((string) ($line['room_type'] ?? '')) ?></td>
          <td><?= (int) ($line['quantity'] ?? 1) ?></td>
          <td><?= money((float) ($line['nightly_rate'] ?? 0)) ?></td>
          <td><?= $nights ?></td>
          <td><?= money($subtotal) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4">Total Room Rent</td>
        <td><?= money((float) $booking['total_room_rent']) ?></td>
      </tr>
      <tr>
        <td colspan="4">GST</td>
        <td><?= money((float) $booking['hotel_gst']) ?></td>
      </tr>
      <tr class="voucher-grand-total">
        <td colspan="4">Grand Total</td>
        <td><?= money($grandTotal) ?></td>
      </tr>
    </tfoot>
  </table>

  <?php if (!empty($booking['payment_remarks'])): ?>
    <p class="voucher-notes"><strong>Payment remarks:</strong> <?= e((string) $booking['payment_remarks']) ?></p>
  <?php endif; ?>

  <footer class="voucher-footer">
    <p>Generated via Hotezo on <?= date('d M Y, h:i A') ?></p>
  </footer>
</div>
