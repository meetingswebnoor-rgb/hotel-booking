<?php
use App\Core\View;

/**
 * @var string $title
 * @var string $description
 * @var string $icon
 */
$title ??= 'Nothing here yet';
$description ??= '';
$icon ??= '📭';
?>
<div class="empty-state">
  <div class="empty-state__icon" aria-hidden="true"><?= e($icon) ?></div>
  <h3><?= e($title) ?></h3>
  <?php if ($description): ?><p><?= e($description) ?></p><?php endif; ?>
  <?php if (View::hasSection('empty-state-action')): ?>
    <div class="mt-4"><?= View::yieldSection('empty-state-action') ?></div>
  <?php endif; ?>
</div>
