/**
 * hotel-hub.js — small enhancements for the hotel create form and the
 * management hub page: syncing the active tab to ?tab= (so a redirect
 * back from a room/rate-plan save reopens on the right tab, and a
 * refresh doesn't silently bounce back to Details), the commission
 * slider's live readout, a slug suggestion from the hotel name, and a
 * hero-image preview before upload. Tabs themselves are switched by
 * ui.js's initTabs() — this only decides which one starts active and
 * keeps the URL in sync as the user clicks between them.
 */

function syncTabFromUrl() {
  const tabsGroup = document.querySelector('[data-tabs-group]');
  if (!tabsGroup) return;

  const requested = new URLSearchParams(window.location.search).get('tab');
  if (!requested) return;

  const target = tabsGroup.querySelector(`.tab[data-tab-target="${requested}"]`);
  target?.click();
}

function initTabUrlSync() {
  const tabsGroup = document.querySelector('[data-tabs-group]');
  if (!tabsGroup) return;

  tabsGroup.addEventListener('click', (event) => {
    const tab = event.target.closest('.tab');
    if (!tab) return;

    const key = tab.getAttribute('data-tab-target');
    const params = new URLSearchParams(window.location.search);

    if (key && key !== 'details') {
      params.set('tab', key);
    } else {
      params.delete('tab');
    }

    const query = params.toString();
    window.history.replaceState(null, '', query ? `${window.location.pathname}?${query}` : window.location.pathname);
  });
}

function initCommissionSlider() {
  const slider = document.querySelector('[data-commission-slider]');
  const display = document.querySelector('[data-commission-display]');
  if (!slider || !display) return;

  slider.addEventListener('input', () => {
    display.textContent = slider.value;
  });
}

function initSlugSuggest() {
  const nameInput = document.querySelector('[data-hotel-name]');
  const slugInput = document.querySelector('[data-hotel-slug]');
  if (!nameInput || !slugInput) return;

  nameInput.addEventListener('input', () => {
    if (slugInput.value.trim() !== '') return; // never overwrite a slug the user already touched

    slugInput.value = nameInput.value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  });
}

function initImagePreview() {
  document.querySelectorAll('[data-image-preview-input]').forEach((input) => {
    input.addEventListener('change', () => {
      const file = input.files?.[0];
      if (!file) return;

      let preview = input.parentElement.querySelector('.hotel-photo-preview');
      if (!preview) {
        preview = document.createElement('img');
        preview.className = 'hotel-photo-preview';
        input.before(preview);
      }

      const reader = new FileReader();
      reader.onload = () => {
        preview.src = String(reader.result);
      };
      reader.readAsDataURL(file);
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initTabUrlSync();
  syncTabFromUrl();
  initCommissionSlider();
  initSlugSuggest();
  initImagePreview();
});
