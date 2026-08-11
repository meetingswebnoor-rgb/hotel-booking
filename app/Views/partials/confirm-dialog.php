<?php
/**
 * Single shared confirm-dialog modal, reused across the app for
 * destructive actions. Content is filled in at open time by
 * public/assets/js/confirm.js — see that file for the JS API
 * (confirmDialog() and the declarative data-confirm-submit form attribute).
 */
?>
<div class="modal-backdrop" id="confirm-dialog" hidden>
  <div class="modal glass" role="alertdialog" aria-modal="true" aria-labelledby="confirm-dialog-title">
    <div class="modal__icon modal__icon--danger" aria-hidden="true">
      <?= icon('alert-triangle') ?>
    </div>
    <h3 id="confirm-dialog-title" data-confirm-title>Are you sure?</h3>
    <p class="text-med mt-2" data-confirm-message></p>
    <div class="modal__actions mt-6">
      <button type="button" class="btn btn-ghost" data-confirm-cancel>Cancel</button>
      <button type="button" class="btn btn-danger" data-confirm-ok>Delete</button>
    </div>
  </div>
</div>
