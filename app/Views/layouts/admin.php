<?php
use App\Core\View;

/**
 * Back-office layout: sidebar + topbar shell. Used by Super Admin,
 * Hotel Admin, and Hotel Manager dashboards alike.
 *
 * @var string $content
 * @var string $title
 * @var string $active
 * @var string $pageTitle
 */
?>
<!doctype html>
<html lang="en">
<head>
<?= partial('head-meta', ['title' => $title ?? null, 'description' => '']) ?>
</head>
<body>
<div class="app-shell">
  <?= partial('sidebar', ['active' => $active ?? '']) ?>

  <div class="app-main">
    <?= partial('topbar', ['pageTitle' => $pageTitle ?? ($title ?? 'Dashboard'), 'user' => $user ?? null]) ?>

    <?php if (View::hasSection('breadcrumbs')): ?>
      <div class="container mt-4"><?= View::yieldSection('breadcrumbs') ?></div>
    <?php endif; ?>

    <div class="app-content"><?= $content ?></div>
  </div>

  <?= partial('bottom-tab-bar', ['active' => $active ?? '']) ?>
</div>

<?= partial('toasts') ?>
<?= partial('confirm-dialog') ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
<script type="module" src="<?= asset('js/app.js') ?>"></script>
<?= View::yieldSection('scripts') ?>
</body>
</html>
