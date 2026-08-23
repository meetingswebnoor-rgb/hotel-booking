/**
 * otas.js — the OTA Management page's two interactive widgets: the
 * custom-payment-statuses tag input (one per OTA modal) and the
 * star-rating picker (the Add Review modal). Both just keep a hidden
 * field in sync; the actual save is a normal form POST.
 */

const MAX_TAG_LENGTH = 40;
const MAX_TAGS = 20;

function renderChips(container, hiddenInput, tags) {
  container.innerHTML = tags
    .map(
      (tag, i) => `
        <span class="tag-input__chip">
          ${tag}
          <button type="button" data-remove-tag="${i}" aria-label="Remove ${tag}">&times;</button>
        </span>
      `
    )
    .join('');
  hiddenInput.value = JSON.stringify(tags);
}

function initTagInput(root) {
  const field = root.querySelector('[data-tag-input-field]');
  const chips = root.querySelector('[data-tag-chips]');
  const hidden = root.querySelector('[data-tag-input-value]');
  if (!field || !chips || !hidden) return;

  let tags = [];

  try {
    const parsed = JSON.parse(hidden.value || '[]');
    if (Array.isArray(parsed)) tags = parsed;
  } catch {
    tags = [];
  }

  renderChips(chips, hidden, tags);

  const addTag = () => {
    const value = field.value.trim();
    field.value = '';

    if (!value || value.length > MAX_TAG_LENGTH || tags.length >= MAX_TAGS || tags.includes(value)) return;

    tags.push(value);
    renderChips(chips, hidden, tags);
  };

  field.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ',') {
      event.preventDefault();
      addTag();
    }
  });

  field.addEventListener('blur', addTag);

  chips.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-remove-tag]');
    if (!btn) return;

    tags.splice(parseInt(btn.dataset.removeTag, 10), 1);
    renderChips(chips, hidden, tags);
  });
}

function initStarRating(root) {
  const stars = Array.from(root.querySelectorAll('[data-star]'));
  const hidden = root.parentElement?.querySelector('[data-star-rating-value]');
  if (!stars.length || !hidden) return;

  const paint = (value) => {
    stars.forEach((star) => {
      star.classList.toggle('star-rating__star--active', parseInt(star.dataset.star, 10) <= value);
    });
  };

  paint(parseInt(hidden.value, 10) || 5);

  stars.forEach((star) => {
    star.addEventListener('click', () => {
      const value = parseInt(star.dataset.star, 10);
      hidden.value = String(value);
      paint(value);
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-tag-input]').forEach(initTagInput);
  document.querySelectorAll('[data-star-rating]').forEach(initStarRating);
});
