<?php

use App\Core\Auth;

/**
 * Sticky glass navbar for every public page (landing, hotel directory,
 * hotel detail). Gets a solid/blur background once the page scrolls
 * past a small threshold (App\..\ui.js::initPublicNav(), not tied to
 * any one page's hero height) and collapses to a slide-over on mobile.
 */
$isLoggedIn = Auth::check();
$registerUrl = config('app.register_route_enabled') ? route('register') : route('login');
?>
<header class="public-nav" data-public-nav>
  <nav class="public-nav__bar container" aria-label="Primary">
    <a href="<?= route('home') ?>" class="public-nav__brand">
      <span class="public-nav__logo" aria-hidden="true"></span>
      Hotezo
    </a>

    <button type="button" class="public-nav__menu-btn" data-public-nav-toggle aria-label="Open menu" aria-expanded="false">
      <?= icon('menu', 'icon') ?>
    </button>

    <div class="public-nav__links">
      <a href="#features">Features</a>
      <a href="#how">How it Works</a>
      <a href="#roles">For Hotels</a>
      <a href="#otas">OTAs</a>
      <a href="mailto:<?= e(config('app.contact_email')) ?>">Contact</a>
    </div>

    <div class="public-nav__actions">
      <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle color theme">
        <?= icon('sun', 'icon theme-toggle__icon theme-toggle__icon--sun') ?>
        <?= icon('moon', 'icon theme-toggle__icon theme-toggle__icon--moon') ?>
      </button>

      <a href="<?= route('public.hotels.index') ?>" class="btn btn-ghost btn-sm">Browse Hotels</a>

      <?php if ($isLoggedIn): ?>
        <a href="<?= route('dashboard') ?>" class="btn btn-ghost btn-sm">Go to Dashboard</a>
      <?php else: ?>
        <a href="<?= route('login') ?>" class="btn btn-ghost btn-sm">Log In</a>
      <?php endif; ?>

      <a href="<?= e($registerUrl) ?>" class="btn btn-primary btn-sm">List Your Hotel</a>
    </div>
  </nav>

  <div class="public-nav__mobile" data-public-nav-mobile hidden>
    <a href="#features" data-public-nav-close>Features</a>
    <a href="#how" data-public-nav-close>How it Works</a>
    <a href="#roles" data-public-nav-close>For Hotels</a>
    <a href="#otas" data-public-nav-close>OTAs</a>
    <a href="mailto:<?= e(config('app.contact_email')) ?>" data-public-nav-close>Contact</a>
    <a href="<?= route('public.hotels.index') ?>" data-public-nav-close>Browse Hotels</a>
    <?php if ($isLoggedIn): ?>
      <a href="<?= route('dashboard') ?>" data-public-nav-close>Go to Dashboard</a>
    <?php else: ?>
      <a href="<?= route('login') ?>" data-public-nav-close>Log In</a>
    <?php endif; ?>
    <a href="<?= e($registerUrl) ?>" class="btn btn-primary" data-public-nav-close>List Your Hotel</a>
  </div>
</header>
