<?php

/**
 * @var string $title
 * @var string $description
 * @var bool $calendar
 */
$calendar ??= false;
?>
<div class="coming-soon glass card">
  <?php if ($calendar): ?>
    <div class="coming-soon__calendar" aria-hidden="true">
      <?php for ($i = 0; $i < 28; $i++): ?><span></span><?php endfor; ?>
    </div>
  <?php endif; ?>
  <div class="coming-soon__badge"><?= icon('sliders', 'icon icon-sm') ?><span>Coming Soon</span></div>
  <h3><?= e($title) ?></h3>
  <p class="text-med"><?= e($description) ?></p>
</div>
