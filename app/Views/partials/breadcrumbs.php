<?php
/**
 * @var array<int, array{label: string, href?: string}> $items
 */
$items ??= [];
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <?php foreach ($items as $i => $item): ?>
    <?php if ($i > 0): ?><span class="sep">/</span><?php endif; ?>
    <?php if (!empty($item['href']) && $i < count($items) - 1): ?>
      <a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
    <?php else: ?>
      <span class="text-high"><?= e($item['label']) ?></span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
