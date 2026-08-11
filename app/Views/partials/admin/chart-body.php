<?php
/**
 * Shared skeleton -> empty -> canvas states for one dashboard chart.
 * dashboard.js toggles which of the three is visible once data arrives.
 *
 * @var string $key
 */
?>
<div class="chart-body">
  <div class="chart-skeleton skeleton" data-chart-skeleton="<?= e($key) ?>"></div>

  <div class="chart-empty" data-chart-empty="<?= e($key) ?>" hidden>
    <?= partial('empty-state', ['title' => 'No data for this period', 'icon' => '📉']) ?>
  </div>

  <div class="chart-canvas-wrap" data-chart-wrap="<?= e($key) ?>" hidden>
    <canvas data-chart="<?= e($key) ?>"></canvas>
  </div>
</div>
