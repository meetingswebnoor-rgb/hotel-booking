<?php

/**
 * One "Enter Demo as …" card — a CSRF-protected POST to /demo-login
 * carrying only a role key (never a password; see
 * App\Controllers\AuthController::demoLogin()), plus the real
 * credentials shown as text for logging in manually instead.
 *
 * @var string $role super_admin|admin|manager
 * @var string $label
 * @var string $desc
 * @var string $email
 * @var string $password
 */
?>
<div class="card glass demo-card" data-reveal-item>
  <div>
    <span class="badge badge--info"><?= e($label) ?></span>
    <p class="text-med mt-2"><?= e($desc) ?></p>
  </div>

  <form method="POST" action="<?= route('demo-login') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="role" value="<?= e($role) ?>">
    <button type="submit" class="btn btn-primary" style="width: 100%;">Enter Demo as <?= e($label) ?></button>
  </form>

  <details class="demo-card__creds">
    <summary>or log in manually</summary>
    <p class="mt-2">Email: <code><?= e($email) ?></code><br>Password: <code><?= e($password) ?></code></p>
  </details>
</div>
