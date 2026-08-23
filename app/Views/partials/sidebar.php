<?php
use App\Core\Auth;
use App\Core\RoleLevel;

/**
 * Admin sidebar nav. Every module below lands as its own controller +
 * routes in a future step — items without a real href stay disabled
 * placeholders until then, but their VISIBILITY is already fully
 * permission-gated via can()/role_at_least() so the nav only ever
 * shows what the current user is actually allowed to reach.
 *
 * @var string $active
 */
$active ??= '';

// Commission Invoices is intentionally its own nav item, not folded
// into the generic "Invoices" placeholder above it — that one is
// gated by the shared 'invoices' permission (hotel_manager/front_desk
// included, for guest/service invoicing); commission invoicing is
// Hotezo's own internal billing, restricted to Super Admin/Admin/
// Accounts specifically, same role check as
// CommissionInvoiceController::canManage().
$canManageCommissionInvoices = Auth::hasRole('accounts') || role_at_least(RoleLevel::ADMIN);

$navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'href' => route('dashboard'), 'visible' => true],
    ['key' => 'bookings', 'label' => 'Bookings', 'icon' => 'calendar', 'href' => can('bookings', 'view') ? route('bookings.index') : null, 'visible' => can('bookings', 'view')],
    ['key' => 'hotels', 'label' => 'Hotels', 'icon' => 'home', 'href' => can('hotels', 'view') ? route('hotels.index') : null, 'visible' => can('hotels', 'view')],
    ['key' => 'rooms', 'label' => 'Rooms', 'icon' => 'layers', 'href' => can('rooms', 'view') ? route('rooms.index') : null, 'visible' => can('rooms', 'view')],
    ['key' => 'rate-plans', 'label' => 'Rate Plans', 'icon' => 'tag', 'href' => can('rate_plans', 'view') ? route('rate-plans.index') : null, 'visible' => can('rate_plans', 'view')],
    ['key' => 'otas', 'label' => 'OTAs', 'icon' => 'share', 'href' => can('otas', 'view') ? route('otas.index') : null, 'visible' => can('otas', 'view')],
    ['key' => 'invoices', 'label' => 'Invoices', 'icon' => 'file-text', 'visible' => can('invoices', 'view')],
    ['key' => 'commission-invoices', 'label' => 'Commission Invoices', 'icon' => 'printer', 'href' => $canManageCommissionInvoices ? route('commission-invoices.index') : null, 'visible' => $canManageCommissionInvoices],
    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'bar-chart', 'visible' => can('reports', 'view')],
    ['key' => 'users', 'label' => 'Users', 'icon' => 'users', 'visible' => can('users', 'view')],
    ['key' => 'emails', 'label' => 'Emails', 'icon' => 'mail', 'visible' => can('emails', 'view')],
    ['key' => 'trash', 'label' => 'Trash', 'icon' => 'trash', 'visible' => role_at_least(RoleLevel::SUPER_ADMIN)],
    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'sliders', 'visible' => can('settings', 'view')],
];

$navItems = array_values(array_filter($navItems, static fn (array $item): bool => $item['visible']));
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar__brand">
    <span class="sidebar__logo" aria-hidden="true"></span>
    <span>Hotezo</span>
    <button type="button" class="sidebar__mobile-close" data-mobile-nav-close aria-label="Close navigation">
      <?= icon('x', 'icon icon-sm') ?>
    </button>
  </div>

  <nav class="sidebar__nav" aria-label="Admin navigation">
    <?php foreach ($navItems as $item): ?>
      <a
        href="<?= e($item['href'] ?? '#') ?>"
        class="sidebar__link <?= $active === $item['key'] ? 'active' : '' ?>"
        aria-disabled="<?= isset($item['href']) ? 'false' : 'true' ?>"
        title="<?= e($item['label']) ?>"
        data-tooltip="<?= e($item['label']) ?>"
      >
        <?= icon($item['icon'], 'icon') ?>
        <span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <button type="button" class="sidebar__link sidebar__collapse-btn" data-sidebar-toggle title="Collapse" data-tooltip="Expand">
    <?= icon('chevron-left', 'icon') ?>
    <span>Collapse</span>
  </button>
</aside>
<div class="sidebar-backdrop" data-mobile-nav-close></div>
