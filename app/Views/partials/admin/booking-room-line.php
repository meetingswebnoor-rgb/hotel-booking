<?php
/**
 * One room line, used both for server-rendered initial lines (numeric
 * $index) and as the <template> source JS clones for "Add another
 * room" (index placeholder __INDEX__, string-replaced on clone).
 *
 * @var int|string $index
 * @var array<string, mixed> $line
 */
$roomType = $line['room_type'] ?? '';
$adults = $line['adults'] ?? 1;
$children = $line['children'] ?? 0;
$quantity = $line['quantity'] ?? 1;
$nightlyRate = $line['nightly_rate'] ?? '';
?>
<div class="room-line glass" data-room-line>
  <div class="room-line__grid">
    <div class="field">
      <label>Room Type</label>
      <select class="select" name="rooms[<?= $index ?>][room_type]" data-room-type>
        <option value="">Select…</option>
        <?php foreach (['Single', 'Double', 'Suite', 'Deluxe'] as $type): ?>
          <option value="<?= e($type) ?>" <?= $roomType === $type ? 'selected' : '' ?>><?= e($type) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label>Rate Plan</label>
      <select class="select" name="rooms[<?= $index ?>][rate_plan_id]" data-rate-plan>
        <option value="">Custom rate</option>
      </select>
    </div>

    <div class="field">
      <label>Adults</label>
      <input class="input" type="number" min="1" name="rooms[<?= $index ?>][adults]" value="<?= e((string) $adults) ?>" data-adults>
    </div>

    <div class="field">
      <label>Children</label>
      <input class="input" type="number" min="0" name="rooms[<?= $index ?>][children]" value="<?= e((string) $children) ?>" data-children>
    </div>

    <div class="field">
      <label>Quantity</label>
      <input class="input" type="number" min="1" name="rooms[<?= $index ?>][quantity]" value="<?= e((string) $quantity) ?>" data-quantity>
    </div>

    <div class="field">
      <label>Per-Day Rent (₹)</label>
      <input class="input" type="number" min="0" step="0.01" name="rooms[<?= $index ?>][nightly_rate]" value="<?= e((string) $nightlyRate) ?>" data-nightly-rate>
    </div>
  </div>

  <p class="capacity-warning" data-capacity-warning hidden></p>

  <button type="button" class="btn btn-ghost btn-sm room-line__remove" data-remove-room>
    <?= icon('trash', 'icon icon-sm') ?>
    <span>Remove room</span>
  </button>
</div>
