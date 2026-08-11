/**
 * confirm.js — the shared confirm-dialog modal (see
 * app/Views/partials/confirm-dialog.php). Two ways to use it:
 *
 *   1. Declarative, for delete forms:
 *        <form method="POST" action="/hotels/123" data-confirm-submit
 *              data-confirm-title="Delete hotel?"
 *              data-confirm-message="This can't be undone.">
 *
 *   2. Programmatic, for JS-driven flows:
 *        const ok = await confirmDialog({ title, message });
 */

let resolvePending = null;

function getModal() {
  return document.getElementById('confirm-dialog');
}

export function confirmDialog({
  title = 'Are you sure?',
  message = '',
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  danger = true,
} = {}) {
  const modal = getModal();

  if (!modal) {
    return Promise.resolve(window.confirm(message || title));
  }

  modal.querySelector('[data-confirm-title]').textContent = title;
  modal.querySelector('[data-confirm-message]').textContent = message;

  const confirmBtn = modal.querySelector('[data-confirm-ok]');
  confirmBtn.textContent = confirmLabel;
  confirmBtn.classList.toggle('btn-danger', danger);
  confirmBtn.classList.toggle('btn-primary', !danger);

  modal.querySelector('[data-confirm-cancel]').textContent = cancelLabel;

  modal.hidden = false;
  document.body.style.overflow = 'hidden';

  return new Promise((resolve) => {
    resolvePending = resolve;
  });
}

function close(result) {
  const modal = getModal();

  if (modal) {
    modal.hidden = true;
    document.body.style.overflow = '';
  }

  if (resolvePending) {
    resolvePending(result);
    resolvePending = null;
  }
}

export function initConfirmDialog() {
  const modal = getModal();
  if (!modal) return;

  modal.addEventListener('click', (event) => {
    if (event.target.closest('[data-confirm-ok]')) {
      close(true);
    } else if (event.target.closest('[data-confirm-cancel]') || event.target === modal) {
      close(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) close(false);
  });

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm-submit')) return;
    if (form.dataset.confirmed === 'true') return;

    event.preventDefault();

    const ok = await confirmDialog({
      title: form.dataset.confirmTitle || 'Are you sure?',
      message: form.dataset.confirmMessage || 'This action cannot be undone.',
      confirmLabel: form.dataset.confirmLabel || 'Delete',
      danger: form.dataset.confirmDanger !== 'false',
    });

    if (ok) {
      form.dataset.confirmed = 'true';
      form.submit();
    }
  });
}
