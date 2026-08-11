/**
 * app.js — entry point loaded on every page. Wires up theme, nav,
 * toasts/modals/tabs/dropdowns, the admin shell (sidebar collapse,
 * mobile nav, search, notifications), and page-enter motion.
 */

import {
  initTheme,
  initModals,
  initTabs,
  initDropdowns,
  initSidebarCollapse,
  initMobileNav,
  initSearch,
  initNotifications,
  toast,
} from './ui.js';
import { initConfirmDialog, confirmDialog } from './confirm.js';
import { initPageEnter, initCountUp, initHoverLift } from './animations.js';

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initModals();
  initTabs();
  initDropdowns();
  initSidebarCollapse();
  initMobileNav();
  initSearch();
  initNotifications();
  initConfirmDialog();
  initHoverLift();
  initPageEnter('[data-animate], .app-content > *');
  initCountUp();

  // Render any server-side flash message as a toast.
  const flash = document.querySelector('[data-flash]');
  if (flash) {
    toast(flash.dataset.flash, { type: flash.dataset.flashType || 'info' });
  }
});

window.Hotezo = { toast, confirmDialog };
