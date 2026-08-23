<?php

/**
 * Add-review modal — a star-rating input (public/assets/js/otas.js
 * toggles which stars look filled and writes the 1-5 value into the
 * hidden field) plus author/text. Reviews always attach to an existing
 * OTA, so this is add-only; there's no edit, only remove.
 *
 * @var array<int, array<string, mixed>> $otas
 * @var string|null $selectedOtaId
 */
?>
<div class="modal-backdrop" id="ota-review-add" hidden>
  <div class="modal glass">
    <div class="modal__header">
      <h3>Add Review</h3>
      <button type="button" class="modal__close" data-modal-close aria-label="Close"><?= icon('x', 'icon') ?></button>
    </div>
    <form method="POST" action="<?= route('otas.reviews.store') ?>">
      <?= csrf_field() ?>

      <div class="field mb-4">
        <label for="ota-review-ota">OTA</label>
        <select class="select" id="ota-review-ota" name="ota_id" required>
          <option value="">Select an OTA…</option>
          <?php foreach ($otas as $o): ?>
            <option value="<?= e($o['id']) ?>" <?= $selectedOtaId === $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field mb-4">
        <label>Rating</label>
        <div class="star-rating" data-star-rating>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="star-rating__star" data-star="<?= $i ?>" aria-label="<?= $i ?> star<?= $i === 1 ? '' : 's' ?>"><?= icon('star', 'icon') ?></button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" data-star-rating-value value="5">
      </div>

      <div class="field mb-4">
        <label for="ota-review-author">Author</label>
        <input class="input" type="text" id="ota-review-author" name="author_name" placeholder="e.g. Front Desk — Demo Grand Hotel" required>
      </div>

      <div class="field mb-4">
        <label for="ota-review-text">Review</label>
        <textarea class="input" id="ota-review-text" name="review_text" rows="3" required></textarea>
      </div>

      <div class="modal__actions">
        <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Add Review</button>
      </div>
    </form>
  </div>
</div>
