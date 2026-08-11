<?php
/**
 * Admin sidebar nav. Every module below lands as its own controller +
 * routes in a future step — items stay disabled placeholders until then.
 *
 * @var string $active
 */
$active ??= '';

$navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => '&#9635;', 'href' => route('dashboard')],
    ['key' => 'bookings', 'label' => 'Bookings', 'icon' => '&#128197;'],
    ['key' => 'hotels', 'label' => 'Hotels', 'icon' => '&#127976;'],
    ['key' => 'settlements', 'label' => 'Settlements', 'icon' => '&#128176;'],
    ['key' => 'invoices', 'label' => 'Invoices', 'icon' => '&#129534;'],
    ['key' => 'reports', 'label' => 'Reports', 'icon' => '&#128202;'],
    ['key' => 'settings', 'label' => 'Settings', 'icon' => '&#9881;'],
];
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar__brand">
    <span class="sidebar__logo" aria-hidden="true"></span>
    <span>Hotezo</span>
  </div>

  <nav class="sidebar__nav" aria-label="Admin navigation">
    <?php foreach ($navItems as $item): ?>
      <a
        href="<?= e($item['href'] ?? '#') ?>"
        class="sidebar__link <?= $active === $item['key'] ? 'active' : '' ?>"
        aria-disabled="<?= isset($item['href']) ? 'false' : 'true' ?>"
        title="<?= e($item['label']) ?>"
      >
        <span aria-hidden="true"><?= $item['icon'] ?></span>
        <span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <button type="button" class="sidebar__link" data-sidebar-toggle aria-label="Collapse sidebar">
    <span aria-hidden="true">&#8676;</span>
    <span>Collapse</span>
  </button>
</aside>
