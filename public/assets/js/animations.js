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

export function initCountUp(selector = '[data-countup]') {
  const targets = document.querySelectorAll(selector);

  targets.forEach((el) => {
    const end = parseFloat(el.getAttribute('data-countup') ?? el.textContent.replace(/[^0-9.-]/g, ''));
    const prefix = el.getAttribute('data-countup-prefix') ?? '';
    const suffix = el.getAttribute('data-countup-suffix') ?? '';
    const decimals = parseInt(el.getAttribute('data-countup-decimals') ?? '0', 10);

    if (Number.isNaN(end)) return;

    if (reduceMotion || typeof gsap === 'undefined') {
      el.textContent = `${prefix}${end.toFixed(decimals)}${suffix}`;
      return;
    }

    const counter = { value: 0 };
    gsap.to(counter, {
      value: end,
      duration: 1.1,
      ease: 'power2.out',
      onUpdate: () => {
        el.textContent = `${prefix}${counter.value.toFixed(decimals)}${suffix}`;
      },
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
