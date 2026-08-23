<?php

/**
 * One add-OTA modal, or one per existing OTA (id'd per OTA) — same
 * "server-pre-filled, no JS population" convention as room-modal.php /
 * rate-plan-modal.php. The custom-payment-statuses tag input is the one
 * genuinely interactive piece (public/assets/js/otas.js).
 *
 * @var string $modalId
 * @var string $formAction
 * @var array<string, mixed>|null $ota
 * @var array<int, string> $statusOptions
 * @var string $heading
 */
$existingCustomStatuses = $ota !== null && !empty($ota['custom_payment_statuses'])
    ? (json_decode((string) $ota['custom_payment_statuses'], true) ?: [])
    : [];
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
        <label for="<?= e($modalId) ?>-name">OTA Name</label>
        <input class="input" type="text" id="<?= e($modalId) ?>-name" name="name" value="<?= e((string) ($ota['name'] ?? '')) ?>" required autofocus>
      </div>

      <div class="booking-form__cols-2 mb-4">
        <div class="field">
          <label for="<?= e($modalId) ?>-commission">Commission %</label>
          <input class="input" type="number" min="0" max="100" step="0.01" id="<?= e($modalId) ?>-commission" name="commission_pct" value="<?= e((string) ($ota['commission_pct'] ?? '15')) ?>" required>
        </div>
        <div class="field">
          <label for="<?= e($modalId) ?>-color">Brand Color</label>
          <input class="input" type="color" id="<?= e($modalId) ?>-color" name="brand_color" value="<?= e((string) ($ota['brand_color'] ?? '#6366F1')) ?>" style="height: 44px; padding: 4px;">
        </div>
      </div>

      <div class="field mb-4">
        <label for="<?= e($modalId) ?>-status">Status</label>
        <select class="select" id="<?= e($modalId) ?>-status" name="status">
          <?php foreach ($statusOptions as $status): ?>
            <option value="<?= e($status) ?>" <?= ($ota['status'] ?? 'active') === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field mb-4">
        <label for="<?= e($modalId) ?>-settlement">Settlement Rules <span class="text-low">(optional)</span></label>
        <textarea class="input" id="<?= e($modalId) ?>-settlement" name="settlement_rules" rows="2" placeholder="e.g. Net 30 after checkout, paid via NEFT"><?= e((string) ($ota['settlement_rules'] ?? '')) ?></textarea>
      </div>

      <div class="field mb-4">
        <label>Custom Payment Statuses <span class="text-low">(optional — beyond Pending/Paid/Hold/Disputed; shown in the booking form only for this OTA)</span></label>
        <div class="tag-input" data-tag-input>
          <div class="tag-input__chips" data-tag-chips></div>
          <input type="text" class="tag-input__field" placeholder="Type a status, press Enter" data-tag-input-field>
          <input type="hidden" name="custom_payment_statuses" data-tag-input-value value='<?= e((string) json_encode($existingCustomStatuses)) ?>'>
        </div>
      </div>

      <div class="modal__actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= $ota !== null ? 'Save OTA' : 'Add OTA' ?></button>
      </div>
    </form>
  </div>
</div>
