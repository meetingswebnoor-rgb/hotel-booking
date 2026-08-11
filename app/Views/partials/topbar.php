<?php
/**
 * @var string $pageTitle
 * @var array<string, mixed>|null $user
 */
$pageTitle ??= 'Dashboard';
$user ??= null;
$initial = $user !== null ? strtoupper(mb_substr((string) $user['full_name'], 0, 1)) : '?';
?>
<header class="topbar">
  <div class="flex-center gap-4">
    <h4><?= e($pageTitle) ?></h4>
  </div>

  <div class="topbar__actions">
    <div class="hotel-filter" aria-disabled="true">
      <span aria-hidden="true">&#127976;</span>
      <span>All Hotels</span>
    </div>

    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle color theme">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="5"></circle>
        <path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"></path>
      </svg>
    </button>

    <?php if ($user !== null): ?>
      <div class="dropdown">
        <button type="button" class="avatar" data-dropdown-trigger title="<?= e((string) $user['full_name']) ?>">
          <?= e($initial) ?>
        </button>
        <div class="dropdown__menu glass">
          <div class="dropdown__menu-header">
            <div class="dropdown__menu-name"><?= e((string) $user['full_name']) ?></div>
            <div class="dropdown__menu-email"><?= e((string) $user['email']) ?></div>
          </div>
          <form method="POST" action="<?= route('logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar__link">Sign out</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="avatar" aria-hidden="true">?</div>
    <?php endif; ?>
  </div>
</header>
