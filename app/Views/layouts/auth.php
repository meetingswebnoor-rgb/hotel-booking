<?php
use App\Core\View;

/**
 * Minimal full-bleed layout for auth pages — no nav/footer chrome, so
 * a split-screen login can go edge to edge.
 *
 * @var string $content
 * @var string $title
 */
?>
<!doctype html>
<html lang="en">
<head>
<?= partial('head-meta', ['title' => $title ?? null, 'description' => '']) ?>
</head>
<body>
<?= $content ?>
<?= partial('toasts') ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script type="module" src="<?= asset('js/app.js') ?>"></script>
<?= View::yieldSection('scripts') ?>
</body>
</html>
