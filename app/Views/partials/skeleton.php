<?php
/**
 * @var int $rows
 */
$rows ??= 3;
?>
<div class="grid gap-3" role="status" aria-label="Loading">
  <?php for ($i = 0; $i < $rows; $i++): ?>
    <div class="skeleton" style="height: 52px; border-radius: var(--radius-md);"></div>
  <?php endfor; ?>
</div>
