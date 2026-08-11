/**
 * animations.js — GSAP-powered motion. Every animation checks
 * prefers-reduced-motion and falls back to a CSS-only state.
 */

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function initPageEnter(selector = '[data-animate]') {
  const targets = document.querySelectorAll(selector);
  if (!targets.length) return;

  if (reduceMotion || typeof gsap === 'undefined') {
    targets.forEach((el) => {
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    return;
  }

  gsap.set(targets, { opacity: 0, y: 24 });
  gsap.to(targets, {
    opacity: 1,
    y: 0,
    duration: 0.4,
    ease: 'power3.out',
    stagger: 0.08,
  });
}

/**
 * Animates a single element's text content from its current numeric
 * value (or 0) up to `end`. Exported standalone so dynamically-loaded
 * data (e.g. dashboard KPIs fetched after page load) can trigger a
 * count-up on demand, not just once at DOMContentLoaded.
 */
export function animateCountUp(el, end, { prefix = '', suffix = '', decimals = 0, duration = 1.1, from = 0, format = null } = {}) {
  if (Number.isNaN(end)) return;

  const render = (value) => `${prefix}${format ? format(value, decimals) : value.toFixed(decimals)}${suffix}`;

  if (reduceMotion || typeof gsap === 'undefined') {
    el.textContent = render(end);
    return;
  }

  const counter = { value: from };
  gsap.to(counter, {
    value: end,
    duration,
    ease: 'power2.out',
    onUpdate: () => {
      el.textContent = render(counter.value);
    },
  });
}

export function initCountUp(selector = '[data-countup]') {
  document.querySelectorAll(selector).forEach((el) => {
    const end = parseFloat(el.getAttribute('data-countup') ?? el.textContent.replace(/[^0-9.-]/g, ''));

    animateCountUp(el, end, {
      prefix: el.getAttribute('data-countup-prefix') ?? '',
      suffix: el.getAttribute('data-countup-suffix') ?? '',
      decimals: parseInt(el.getAttribute('data-countup-decimals') ?? '0', 10),
    });
  });
}

export function initHoverLift(selector = '.card-hover, .btn') {
  if (reduceMotion) return;

  document.querySelectorAll(selector).forEach((el) => {
    el.addEventListener('pointerenter', () => {
      el.style.willChange = 'transform';
    });
    el.addEventListener('pointerleave', () => {
      el.style.willChange = 'auto';
    });
  });
}
