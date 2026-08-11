<nav class="public-nav">
  <a href="<?= route('home') ?>" class="public-nav__brand">
    <span class="public-nav__logo" aria-hidden="true"></span>
    Hotezo
  </a>

  <div class="public-nav__links">
    <a href="#features">Platform</a>
    <a href="#for-hotels">For Hotels</a>
    <a href="#" aria-disabled="true">Pricing</a>
  </div>

  <div class="topbar__actions">
    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle color theme">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="5"></circle>
        <path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"></path>
      </svg>
    </button>
    <a href="#" class="btn btn-ghost btn-sm" aria-disabled="true">Log In</a>
    <a href="#" class="btn btn-primary btn-sm" aria-disabled="true">List Your Hotel</a>
  </div>
</nav>
