<?php

use App\Core\View;

/**
 * @var string $title
 * @var string $description
 * @var array<string, mixed>|null $structuredData optional JSON-LD payload (e.g. Organization schema)
 */
$title ??= config('app.name');
$description ??= '';
$structuredData ??= null;
$canonical ??= rtrim((string) config('app.url'), '/') . (string) ($_SERVER['REQUEST_URI'] ?? '/');
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:site_name" content="Hotezo">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= e($title) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="theme-color" content="#6366F1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 32 32%22><rect width=%2232%22 height=%2232%22 rx=%228%22 fill=%22%236366F1%22/></svg>">

<script nonce="<?= e(csp_nonce()) ?>">
  try {
    var storedTheme = localStorage.getItem('hotezo-theme');
    if (storedTheme) document.documentElement.setAttribute('data-theme', storedTheme);
  } catch (e) {}
</script>

<?php if ($structuredData !== null): ?>
<script type="application/ld+json" nonce="<?= e(csp_nonce()) ?>"><?= json_encode($structuredData, JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>

<link rel="preconnect" href="https://api.fontshare.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=clash-display@500,600,700&f[]=satoshi@400,500,700&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">

<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?= View::yieldSection('styles') ?>
