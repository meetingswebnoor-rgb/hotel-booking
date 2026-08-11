<div class="auth-shell">
  <aside class="auth-hero" data-animate>
    <div class="auth-hero__glow" aria-hidden="true"></div>
    <div class="auth-hero__content">
      <a href="<?= route('home') ?>" class="public-nav__brand" style="color: #fff;">
        <span class="public-nav__logo" aria-hidden="true"></span>
        Hotezo
      </a>

      <h1>Run every hotel from one dashboard.</h1>
      <p class="mt-4">Bookings, OTA commissions, settlements, and GST-compliant invoicing —
        synced across your Super Admin, Hotel Admin, and Front Desk views in real time.</p>

      <div class="hero__stats mt-16">
        <div>
          <div class="hero__stat-value font-mono" style="color: #fff;" data-countup="1000" data-countup-suffix="+">0</div>
          <div class="hero__stat-label" style="color: rgba(255, 255, 255, 0.75);">Hotels on the platform</div>
        </div>
        <div>
          <div class="hero__stat-value font-mono" style="color: #fff;" data-countup="10">0</div>
          <div class="hero__stat-label" style="color: rgba(255, 255, 255, 0.75);">Staff roles supported</div>
        </div>
      </div>
    </div>
  </aside>

  <main class="auth-panel">
    <div class="auth-card glass" data-animate>
      <h2>Welcome back</h2>
      <p class="text-med mt-2">Sign in to your Hotezo account.</p>

      <?php if (has_flash('error')): ?>
        <div class="alert alert--error mt-6" role="alert"><?= e((string) flash('error')) ?></div>
      <?php endif; ?>
      <?php if (has_flash('success')): ?>
        <div class="alert alert--success mt-6" role="status"><?= e((string) flash('success')) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= route('login.submit') ?>" class="grid gap-4 mt-6">
        <?= csrf_field() ?>

        <div class="field">
          <label for="email">Email</label>
          <input
            class="input"
            type="email"
            id="email"
            name="email"
            value="<?= e(old('email')) ?>"
            required
            autofocus
            autocomplete="username"
          >
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input
            class="input"
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
          >
        </div>

        <label class="auth-remember">
          <input type="checkbox" name="remember" value="1">
          Remember me for 30 days
        </label>

        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">Sign in</button>
      </form>

      <p class="text-low mt-6" style="font-size: 0.8125rem; text-align: center;">
        Forgot your password? Contact your Hotel Admin.
      </p>
    </div>
  </main>
</div>
