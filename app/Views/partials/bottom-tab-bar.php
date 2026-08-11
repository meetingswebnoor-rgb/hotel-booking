<?php
/**
 * Mobile-only quick-access bar (<900px). A handful of the most-used
 * items as icons, plus "More" which opens the same full nav as the
 * slide-over sidebar — see public/assets/js/ui.js initMobileNav().
 *
 * @var string $active
 */
$active ??= '';

$quickItems = [
    ['key' => 'dashboard', 'label' => 'Home', 'icon' => 'grid', 'href' => route('dashboard'), 'visible' => true],
    ['key' => 'bookings', 'label' => 'Bookings', 'icon' => 'calendar', 'visible' => can('bookings', 'view')],
    ['key' => 'hotels', 'label' => 'Hotels', 'icon' => 'home', 'visible' => can('hotels', 'view')],
    ['key' => 'invoices', 'label' => 'Invoices', 'icon' => 'file-text', 'visible' => can('invoices', 'view')],
];

$quickItems = array_values(array_filter($quickItems, static fn (array $item): bool => $item['visible']));
$quickItems = array_slice($quickItems, 0, 4);
?>
<nav class="bottom-tab-bar" aria-label="Quick navigation">
  <?php foreach ($quickItems as $item): ?>
    <a
      href="<?= e($item['href'] ?? '#') ?>"
      class="bottom-tab-bar__item <?= $active === $item['key'] ? 'active' : '' ?>"
      aria-disabled="<?= isset($item['href']) ? 'false' : 'true' ?>"
    >
      <?= icon($item['icon'], 'icon icon-sm') ?>
      <span><?= e($item['label']) ?></span>
    </a>
  <?php endforeach; ?>

  <button type="button" class="bottom-tab-bar__item" data-mobile-nav-toggle>
    <?= icon('menu', 'icon icon-sm') ?>
    <span>More</span>
  </button>
</nav>
