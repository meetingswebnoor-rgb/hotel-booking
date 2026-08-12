<?php
/**
 * Minimal layout for print-optimized pages (booking vouchers). Reuses
 * the app's fonts/tokens via app.css so the on-screen preview looks
 * consistent with the rest of the admin, but adds print.css for the
 * actual @media print rules and this page's own layout.
 *
 * @var string $content
 * @var string $title
 */
?>
<!doctype html>
<html lang="en">
<head>
<?= partial('head-meta', ['title' => $title ?? null, 'description' => '']) ?>
<link rel="stylesheet" href="<?= asset('css/print.css') ?>">
</head>
<body class="print-body">
<?= $content ?>
<script type="module" src="<?= asset('js/voucher-print.js') ?>"></script>
</body>
</html>
