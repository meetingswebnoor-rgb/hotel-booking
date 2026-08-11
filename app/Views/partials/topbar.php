<?php
/**
 * @var string $pageTitle
 */
$pageTitle ??= 'Dashboard';
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

    <div class="avatar" title="Signed in user" aria-hidden="true">H</div>
  </div>
</header>
