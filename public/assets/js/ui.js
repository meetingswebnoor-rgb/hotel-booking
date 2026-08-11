/**
 * ui.js — small DOM helpers: toasts, modals, tabs, dropdowns.
 * No framework, no build step — plain ES module.
 */

let toastContainer = null;

function getToastContainer() {
  if (!toastContainer) {
    toastContainer = document.querySelector('.toast-container');
  }
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    toastContainer.setAttribute('aria-live', 'polite');
    document.body.appendChild(toastContainer);
  }
  return toastContainer;
}

const TOAST_ICONS = {
  success: '✓',
  error: '✕',
  warning: '!',
  info: 'i',
};

export function toast(message, { type = 'info', title = '', duration = 4200 } = {}) {
  const container = getToastContainer();
  const el = document.createElement('div');
  el.className = `toast glass toast--${type}`;
  el.setAttribute('role', 'status');
  el.innerHTML = `
    <span aria-hidden="true">${TOAST_ICONS[type] ?? TOAST_ICONS.info}</span>
    <span>
      ${title ? `<div class="toast__title">${title}</div>` : ''}
      <div class="toast__body">${message}</div>
    </span>
  `;
  container.appendChild(el);

  requestAnimationFrame(() => el.classList.add('toast--in'));

  const remove = () => {
    el.style.opacity = '0';
    el.style.transform = 'translateX(12px)';
    setTimeout(() => el.remove(), 200);
  };

  const timer = setTimeout(remove, duration);
  el.addEventListener('click', () => {
    clearTimeout(timer);
    remove();
  });

  return el;
}

export function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
  const focusable = modal.querySelector('[data-autofocus], input, button');
  focusable?.focus();
}

export function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.hidden = true;
  document.body.style.overflow = '';
}

export function initModals() {
  document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-modal-open]');
    if (opener) {
      openModal(opener.getAttribute('data-modal-open'));
      return;
    }

    const closer = event.target.closest('[data-modal-close]');
    if (closer) {
      const modal = closer.closest('.modal-backdrop');
      if (modal) closeModal(modal.id);
      return;
    }

    if (event.target.classList.contains('modal-backdrop')) {
      closeModal(event.target.id);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.modal-backdrop:not([hidden])').forEach((modal) => closeModal(modal.id));
  });
}

export function initTabs() {
  document.querySelectorAll('.tabs').forEach((tabGroup) => {
    tabGroup.addEventListener('click', (event) => {
      const tab = event.target.closest('.tab');
      if (!tab) return;

      tabGroup.querySelectorAll('.tab').forEach((t) => t.classList.remove('active'));
      tab.classList.add('active');

      const panelId = tab.getAttribute('data-tab-target');
      if (!panelId) return;

      const panelGroup = tab.closest('[data-tabs-group]') ?? document;
      panelGroup.querySelectorAll('[data-tab-panel]').forEach((panel) => {
        panel.hidden = panel.getAttribute('data-tab-panel') !== panelId;
      });
    });
  });
}

export function initDropdowns() {
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-dropdown-trigger]');

    document.querySelectorAll('.dropdown.open').forEach((open) => {
      if (!trigger || open !== trigger.closest('.dropdown')) {
        open.classList.remove('open');
      }
    });

    if (trigger) {
      trigger.closest('.dropdown')?.classList.toggle('open');
    }
  });
}

export function initTheme() {
  const stored = localStorage.getItem('hotezo-theme');
  if (stored) {
    document.documentElement.setAttribute('data-theme', stored);
  }

  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');
    if (!toggle) return;

    const current = document.documentElement.getAttribute('data-theme')
      ?? (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    const next = current === 'dark' ? 'light' : 'dark';

    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('hotezo-theme', next);
  });
}
