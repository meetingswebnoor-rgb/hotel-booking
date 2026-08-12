<?php

use App\Core\View;

View::section('styles');
?>
<link rel="stylesheet" href="<?= asset('css/auth.css') ?>">
<?php View::endSection(); ?>

<div class="auth-page">
  <div class="auth-page__glow" aria-hidden="true"></div>

  <div class="auth-card-v2 glass" data-animate>
    <div class="auth-card-v2__badge" aria-hidden="true"><?= icon('lock', 'icon icon-lg') ?></div>

    <div class="auth-card-v2__grid">
      <aside class="auth-illustration">
        <span class="auth-illustration__pill">Hotel Operating System</span>

        <div class="auth-illustration__art" aria-hidden="true">
          <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.4/dist/dotlottie-wc.js" type="module"></script>
          <dotlottie-wc src="https://lottie.host/182bd9a4-6d05-4826-afe0-62d332cfd03c/CVGVZIxGnl.json" style="width: 300px;height: 300px" autoplay loop></dotlottie-wc>
        </div>

        <h2 class="auth-illustration__heading">Run every hotel <span class="gradient-text">from one dashboard.</span></h2>
        <p class="auth-illustration__desc">Bookings, OTA commissions, settlements, and GST-compliant
          invoicing — synced across your Super Admin, Hotel Admin, and Front Desk views in real time.</p>

        <div class="auth-illustration__tagline">
          <span class="auth-illustration__tagline-dot" aria-hidden="true"></span>
          Trusted across 1,000+ properties on Hotezo
        </div>
      </aside>

      <main class="auth-form-panel">
        <a href="<?= route('home') ?>" class="auth-form-panel__brand">
          <span class="public-nav__logo" aria-hidden="true"></span>
          Hotezo
        </a>

        <h1>Welcome back</h1>
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
            <label for="email">Email address</label>
            <div class="input-icon">
              <span class="input-icon__icon" aria-hidden="true"><?= icon('mail', 'icon icon-sm') ?></span>
              <input
                class="input input-icon__field"
                type="email"
                id="email"
                name="email"
                value="<?= e(old('email')) ?>"
                required
                autofocus
                autocomplete="username"
                placeholder="you@hotel.com"
              >
            </div>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input-icon">
              <span class="input-icon__icon" aria-hidden="true"><?= icon('lock', 'icon icon-sm') ?></span>
              <input
                class="input input-icon__field input-icon__field--with-toggle"
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
              >
              <button
                type="button"
                class="input-icon__toggle"
                data-password-toggle
                aria-label="Show password"
                aria-pressed="false"
                aria-controls="password"
              >
                <?= icon('eye', 'icon icon-sm') ?>
                <?= icon('eye-off', 'icon icon-sm') ?>
              </button>
            </div>
          </div>

          <div class="auth-row">
            <label class="auth-remember">
              <input type="checkbox" name="remember" value="1">
              Remember me
            </label>
            <span class="auth-row__forgot" title="Forgot your password? Contact your Hotel Admin.">Forgot password?</span>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">Sign In</button>
        </form>

        <div class="auth-divider"><span>OR CONTINUE WITH</span></div>

        <button
          type="button"
          class="btn btn-ghost auth-google-btn"
          style="width: 100%;"
          disabled
          title="Google sign-in isn't connected yet — use your email and password"
        >
          <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
            <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
            <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
            <path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/>
            <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/>
          </svg>
          <span>Google</span>
        </button>

        <p class="auth-form-panel__footer">
          New to Hotezo? <span title="Accounts are provisioned by your Super Admin">Ask your admin for access</span>
        </p>
      </main>
    </div>
  </div>
</div>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/auth.js') ?>"></script>
<?php View::endSection(); ?>
