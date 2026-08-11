<?php
/**
 * Renders any queued flash message as a hidden element that app.js
 * picks up on load and turns into a toast.
 */
$flashType = has_flash('success') ? 'success' : (has_flash('error') ? 'error' : (has_flash('info') ? 'info' : null));
$flashMessage = $flashType ? flash($flashType) : null;
?>
<div class="toast-container" aria-live="polite"></div>
<?php if ($flashMessage): ?>
  <span hidden data-flash="<?= e((string) $flashMessage) ?>" data-flash-type="<?= e($flashType) ?>"></span>
<?php endif; ?>
