/**
 * app.js — entry point loaded on every page. Wires up theme, nav,
 * toasts/modals/tabs, and page-enter motion.
 */

import { initTheme, initModals, initTabs, initDropdowns, toast } from './ui.js';
import { initPageEnter, initCountUp, initHoverLift } from './animations.js';

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initModals();
  initTabs();
  initDropdowns();
  initHoverLift();
  initPageEnter();
  initCountUp();

  const mobileToggle = document.querySelector('[data-sidebar-toggle]');
  const sidebar = document.querySelector('.sidebar');
  mobileToggle?.addEventListener('click', () => sidebar?.classList.toggle('collapsed'));

  // Render any server-side flash message as a toast.
  const flash = document.querySelector('[data-flash]');
  if (flash) {
    toast(flash.dataset.flash, { type: flash.dataset.flashType || 'info' });
  }
});

window.Hotezo = { toast };
