/**
 * ui.js — small DOM helpers: toasts, modals, tabs, dropdowns, theme,
 * sidebar collapse/mobile nav, global search, notifications bell.
 * No framework, no build step — plain ES module.
 */

import { api } from './api.js';

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

const THEME_TRANSITION_MS = 320;

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

    // Briefly transition every element's colors so the switch reads as
    // a smooth cross-fade rather than an instant flip.
    document.documentElement.classList.add('theme-transitioning');
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('hotezo-theme', next);
    window.setTimeout(() => {
      document.documentElement.classList.remove('theme-transitioning');
    }, THEME_TRANSITION_MS);
  });
}

export function initSidebarCollapse() {
  const sidebar = document.querySelector('.sidebar');
  if (!sidebar) return;

  if (localStorage.getItem('hotezo-sidebar-collapsed') === '1') {
    sidebar.classList.add('collapsed');
  }

  document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-sidebar-toggle]')) return;
    const collapsed = sidebar.classList.toggle('collapsed');
    localStorage.setItem('hotezo-sidebar-collapsed', collapsed ? '1' : '0');
  });
}

export function initMobileNav() {
  const sidebar = document.querySelector('.sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');
  if (!sidebar) return;

  const open = () => {
    sidebar.classList.add('mobile-open');
    backdrop?.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  const close = () => {
    sidebar.classList.remove('mobile-open');
    backdrop?.classList.remove('open');
    document.body.style.overflow = '';
  };

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-mobile-nav-toggle]')) {
      open();
      return;
    }

    if (event.target.closest('[data-mobile-nav-close]')) {
      close();
      return;
    }

    if (window.innerWidth <= 900 && event.target.closest('.sidebar__link[href]:not([aria-disabled="true"])')) {
      close();
    }
  });
}

export function initSearch() {
  const input = document.querySelector('[data-search-input]');
  const results = document.querySelector('[data-search-results]');
  if (!input || !results) return;

  let timer = null;

  const render = (items) => {
    if (!items.length) {
      results.innerHTML = '<div class="search-results__empty">No matches</div>';
      results.hidden = false;
      return;
    }

    results.innerHTML = items
      .map(
        (item) => `
          <div class="search-results__item">
            <span class="search-results__label">${item.label}</span>
            <span class="search-results__meta">${item.meta || ''}</span>
          </div>
        `
      )
      .join('');
    results.hidden = false;
  };

  input.addEventListener('input', () => {
    window.clearTimeout(timer);
    const q = input.value.trim();

    if (q.length < 2) {
      results.hidden = true;
      return;
    }

    timer = window.setTimeout(async () => {
      try {
        const data = await api(`/search?q=${encodeURIComponent(q)}`);
        render(data.results ?? []);
      } catch {
        results.hidden = true;
      }
    }, 250);
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.search-box')) {
      results.hidden = true;
    }
  });
}

export function initNotifications() {
  const trigger = document.querySelector('[data-notif-trigger]');
  const badge = document.querySelector('[data-notif-badge]');
  const list = document.querySelector('[data-notif-list]');
  if (!trigger) return;

  const setBadge = (count) => {
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count > 9 ? '9+' : String(count);
      badge.hidden = false;
    } else {
      badge.hidden = true;
    }
  };

  api('/notifications/count')
    .then((data) => setBadge(data.unread_count ?? 0))
    .catch(() => {});

  trigger.addEventListener('click', async () => {
    if (!list) return;

    try {
      const data = await api('/notifications');
      const items = data.items ?? [];

      list.innerHTML = items.length
        ? items
            .map(
              (n) => `
                <div class="notif-item">
                  <div class="notif-item__subject">${n.subject ?? n.event_key}</div>
                  <div class="notif-item__time">${n.created_at}</div>
                </div>
              `
            )
            .join('')
        : '<div class="dropdown__menu-header"><span class="dropdown__menu-email">No notifications yet</span></div>';

      setBadge(0);
    } catch {
      // Silent — the badge simply won't update this time.
    }
  });
}

/**
 * The public navbar (partials/nav-public.php, every public page): a
 * solid/blur background past a small scroll threshold — deliberately
 * not tied to any one page's hero height, since /explore and
 * /hotel/{slug} share this same navbar without a matching hero — plus
 * the mobile slide-over menu.
 */
export function initPublicNav() {
  const nav = document.querySelector('[data-public-nav]');
  if (!nav) return;

  const SCROLL_THRESHOLD = 24;
  const onScroll = () => nav.classList.toggle('public-nav--scrolled', window.scrollY > SCROLL_THRESHOLD);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  const toggleBtn = document.querySelector('[data-public-nav-toggle]');
  const mobileMenu = document.querySelector('[data-public-nav-mobile]');
  if (!toggleBtn || !mobileMenu) return;

  const close = () => {
    mobileMenu.hidden = true;
    toggleBtn.setAttribute('aria-expanded', 'false');
  };

  toggleBtn.addEventListener('click', () => {
    const willOpen = mobileMenu.hidden;
    mobileMenu.hidden = !willOpen;
    toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  });

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-public-nav-close]')) close();
  });
}

/**
 * Generic auto-submit for server-rendered filter bars (Rooms, Rate
 * Plans — pages small enough not to need the Bookings list's AJAX/JSON
 * treatment): any select/input marked data-auto-submit submits its
 * enclosing form on change, so picking a filter reloads the page with
 * the new query string immediately, no explicit "Apply" button needed.
 */
export function initFilterAutoSubmit() {
  document.addEventListener('change', (event) => {
    const field = event.target.closest('[data-auto-submit]');
    if (field) field.closest('form')?.submit();
  });
}
