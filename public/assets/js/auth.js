/**
 * auth.js — page-specific behavior for the login page only
 * (app/Views/pages/auth/login.php): the password show/hide toggle.
 */

function initPasswordToggle() {
  const toggle = document.querySelector('[data-password-toggle]');
  if (!toggle) return;

  const controlsId = toggle.getAttribute('aria-controls');
  const input = controlsId ? document.getElementById(controlsId) : null;
  if (!input) return;

  toggle.addEventListener('click', () => {
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    toggle.classList.toggle('is-visible', !showing);
    toggle.setAttribute('aria-pressed', String(!showing));
    toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initPasswordToggle();
});
