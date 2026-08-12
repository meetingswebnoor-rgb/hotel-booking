/**
 * rooms.js — the standalone Rooms page's grid/table view toggle.
 * Both panels are server-rendered from the same data; this just shows
 * one at a time and remembers the choice, same localStorage pattern as
 * the sidebar's collapse state and the theme toggle.
 */

const STORAGE_KEY = 'hotezo-rooms-view';

function setView(view) {
  document.querySelectorAll('[data-view-panel]').forEach((panel) => {
    panel.hidden = panel.getAttribute('data-view-panel') !== view;
  });

  document.querySelectorAll('[data-view-toggle]').forEach((btn) => {
    btn.classList.toggle('active', btn.getAttribute('data-view-toggle') === view);
  });

  localStorage.setItem(STORAGE_KEY, view);
}

document.addEventListener('DOMContentLoaded', () => {
  const toggles = document.querySelectorAll('[data-view-toggle]');
  if (!toggles.length) return;

  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored === 'table' || stored === 'grid') setView(stored);

  toggles.forEach((btn) => {
    btn.addEventListener('click', () => setView(btn.getAttribute('data-view-toggle')));
  });
});
