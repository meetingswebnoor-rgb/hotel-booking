<?php

/**
 * Shared by every public page (landing, hotel directory, hotel detail).
 * Product/For Hotels links point at the landing page's own sections —
 * if this partial renders somewhere other than "/", they still resolve
 * correctly since they're full route('home') + "#anchor" URLs, not bare
 * fragments.
 */
$registerUrl = config('app.register_route_enabled') ? route('register') : route('login');
$contactHref = config('app.contact_route_enabled') ? route('contact') : 'mailto:' . config('app.contact_email');
?>
<footer class="public-footer">
  <div class="container public-footer__grid">
    <div class="public-footer__col">
      <div class="public-nav__brand mb-4">
        <span class="public-nav__logo" aria-hidden="true"></span>
        Hotezo
      </div>
      <p>The booking hub and back office for independent hotels.</p>
      <div class="public-footer__social">
        <a href="#" aria-disabled="true" aria-label="Twitter"><?= icon('twitter', 'icon icon-sm') ?></a>
        <a href="#" aria-disabled="true" aria-label="LinkedIn"><?= icon('linkedin', 'icon icon-sm') ?></a>
        <a href="#" aria-disabled="true" aria-label="Instagram"><?= icon('instagram', 'icon icon-sm') ?></a>
      </div>
    </div>

    <div class="public-footer__col">
      <h4>Product</h4>
      <a href="<?= route('home') ?>#features">Features</a>
      <a href="<?= route('home') ?>#how">How it Works</a>
      <a href="<?= route('home') ?>#otas">OTA Integrations</a>
    </div>

    <div class="public-footer__col">
      <h4>For Hotels</h4>
      <a href="<?= route('home') ?>#roles">Built for Every Role</a>
      <a href="<?= route('public.hotels.index') ?>">Browse Hotels</a>
      <a href="<?= e($registerUrl) ?>">List Your Hotel</a>
    </div>

    <div class="public-footer__col">
      <h4>Company</h4>
      <a href="#" aria-disabled="true">About</a>
      <a href="<?= e($contactHref) ?>">Contact</a>
      <a href="mailto:<?= e(config('app.contact_email')) ?>"><?= e(config('app.contact_email')) ?></a>
    </div>

    <div class="public-footer__col">
      <h4>Legal</h4>
      <a href="<?= route('privacy') ?>">Privacy</a>
      <a href="<?= route('terms') ?>">Terms</a>
    </div>
  </div>

  <div class="container public-footer__bottom">
    <p>&copy; <?= date('Y') ?> Hotezo. All rights reserved.</p>
    <div class="public-footer__bottom-links">
      <a href="<?= route('privacy') ?>">Privacy</a>
      <a href="<?= route('terms') ?>">Terms</a>
    </div>
  </div>
</footer>
