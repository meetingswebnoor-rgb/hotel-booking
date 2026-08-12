<?php
use App\Core\View;

/**
 * Public marketing/booking layout.
 * @var string $content
 * @var string $title
 * @var string $description
 * @var array<string, mixed>|null $structuredData
 */
?>
<!doctype html>
<html lang="en">
<head>
<?= partial('head-meta', ['title' => $title ?? null, 'description' => $description ?? null, 'structuredData' => $structuredData ?? null]) ?>
</head>
<body>
<?= partial('nav-public') ?>

<main><?= $content ?></main>

<?= partial('footer-public') ?>
<?= partial('toasts') ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
<script type="module" src="<?= asset('js/app.js') ?>"></script>
<?= View::yieldSection('scripts') ?>
</body>
</html>
