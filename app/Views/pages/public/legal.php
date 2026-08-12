<?php

/**
 * Honest placeholder for /privacy and /terms — real legal copy isn't
 * something to fabricate, but a dead link/404 is worse than an
 * explicit "not published yet" page. See HomeController::privacy()/terms().
 *
 * @var string $pageTitle
 * @var string $slug
 * @var string $contactHref
 */
?>
<section class="section flex-center" style="min-height: 50vh; flex-direction: column; text-align: center;">
  <div class="container" style="max-width: 640px;">
    <span class="badge badge--info mb-4">Coming soon</span>
    <h1><?= e($pageTitle) ?></h1>
    <p class="mt-4">This page hasn't been published yet — Hotezo is still in active development.
      In the meantime, reach us directly at
      <a href="<?= e($contactHref) ?>" class="gradient-text"><?= e(config('app.contact_email')) ?></a>
      with any <?= $slug === 'privacy' ? 'privacy' : 'terms of service' ?> questions.</p>
    <div class="mt-8">
      <a href="<?= route('home') ?>" class="btn btn-primary">Back to home</a>
    </div>
  </div>
</section>
