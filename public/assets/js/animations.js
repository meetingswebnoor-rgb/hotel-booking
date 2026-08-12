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

function countUpOptionsFrom(el) {
  return {
    prefix: el.getAttribute('data-countup-prefix') ?? '',
    suffix: el.getAttribute('data-countup-suffix') ?? '',
    decimals: parseInt(el.getAttribute('data-countup-decimals') ?? '0', 10),
  };
}

/**
 * Elements with data-countup-onscroll are skipped here — they're
 * handled by initScrollCountUp() instead, once they're actually
 * visible, so a stat below the fold doesn't finish counting before
 * the visitor has scrolled anywhere near it.
 */
export function initCountUp(selector = '[data-countup]:not([data-countup-onscroll])') {
  document.querySelectorAll(selector).forEach((el) => {
    const end = parseFloat(el.getAttribute('data-countup') ?? el.textContent.replace(/[^0-9.-]/g, ''));

    animateCountUp(el, end, countUpOptionsFrom(el));
  });
}

/**
 * Same count-up, triggered the first time each element scrolls into
 * view (IntersectionObserver, not ScrollTrigger — this needs to work
 * even on a page that hasn't registered the GSAP plugin). Used by the
 * landing page's stats band and mini calculation card.
 */
export function initScrollCountUp(selector = '[data-countup-onscroll]') {
  const targets = document.querySelectorAll(selector);
  if (!targets.length) return;

  if (typeof IntersectionObserver === 'undefined') {
    targets.forEach((el) => animateCountUp(el, parseFloat(el.getAttribute('data-countup')), countUpOptionsFrom(el)));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        animateCountUp(el, parseFloat(el.getAttribute('data-countup')), countUpOptionsFrom(el));
        observer.unobserve(el);
      });
    },
    { threshold: 0.4 }
  );

  targets.forEach((el) => observer.observe(el));
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
