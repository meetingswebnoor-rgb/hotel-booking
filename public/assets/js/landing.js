/**
 * landing.js — page-specific behavior for the public marketing landing
 * page only (app/Views/pages/public/home.php): scroll-triggered section
 * reveals (GSAP + ScrollTrigger, already loaded by layouts/public.php
 * but never wired up before this page needed it), the hero mockup
 * card's own entrance sequence, the OTA marquee's reduced-motion pause,
 * and the scroll-count-up stats. Every reveal checks
 * prefers-reduced-motion and just shows the final state instantly
 * instead, same convention as animations.js.
 */

import { animateCountUp, initScrollCountUp } from './animations.js';
import { createLineChart } from './charts.js';
import { formatIndianCurrency } from './format.js';

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initScrollReveals() {
  const groups = document.querySelectorAll('[data-reveal-group]');
  if (!groups.length) return;

  if (reduceMotion || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
    groups.forEach((group) => {
      group.querySelectorAll('[data-reveal-item]').forEach((el) => {
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
    });
    return;
  }

  gsap.registerPlugin(ScrollTrigger);

  groups.forEach((group) => {
    const items = group.querySelectorAll('[data-reveal-item]');
    if (!items.length) return;

    gsap.set(items, { opacity: 0, y: 28 });
    gsap.to(items, {
      opacity: 1,
      y: 0,
      duration: 0.5,
      ease: 'power3.out',
      stagger: 0.08,
      scrollTrigger: {
        trigger: group,
        start: 'top 80%',
        once: true,
      },
    });
  });
}

function initParallaxShapes() {
  if (reduceMotion || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;

  const shapes = document.querySelectorAll('[data-parallax-shape]');
  if (!shapes.length) return;

  shapes.forEach((shape, i) => {
    gsap.to(shape, {
      y: (i % 2 === 0 ? 1 : -1) * 60,
      ease: 'none',
      scrollTrigger: {
        trigger: shape.closest('.hero'),
        start: 'top top',
        end: 'bottom top',
        scrub: true,
      },
    });
  });
}

function initMarquee() {
  const marquee = document.querySelector('[data-marquee]');
  if (!marquee) return;

  marquee.classList.toggle('marquee--paused', reduceMotion);
}

function createHeroChart() {
  const canvas = document.querySelector('[data-mockup-chart]');
  if (!canvas || typeof Chart === 'undefined') return;

  createLineChart(canvas.getContext('2d'), {
    labels: ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
    datasets: [{ data: [62, 78, 71, 94, 88, 121], label: 'Bookings', pointRadius: 0 }],
  });
}

function runKpiCountUps(kpiEls) {
  kpiEls.forEach((kpi) => {
    const valueEl = kpi.querySelector('[data-countup]');
    if (!valueEl) return;

    const prefix = valueEl.getAttribute('data-countup-prefix') ?? '';
    animateCountUp(valueEl, parseFloat(valueEl.getAttribute('data-countup')), {
      prefix,
      decimals: parseInt(valueEl.getAttribute('data-countup-decimals') ?? '0', 10),
      duration: 1.3,
      format: prefix ? formatIndianCurrency : null,
    });
  });
}

/**
 * The hero's dashboard mockup gets its own deliberate entrance instead
 * of joining the generic data-animate page-load batch: the card slides
 * in first, then its KPI tiles stagger in, then their numbers count up,
 * then the chart is created last — so its own draw-in animation is
 * seen clearly rather than firing simultaneously with everything else
 * still moving.
 */
function initHeroMockup() {
  const mockup = document.querySelector('[data-hero-mockup]');
  if (!mockup) return;

  const kpiEls = mockup.querySelectorAll('[data-hero-kpi]');

  if (reduceMotion || typeof gsap === 'undefined') {
    mockup.style.opacity = '1';
    mockup.style.transform = 'none';
    kpiEls.forEach((el) => {
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    runKpiCountUps(kpiEls);
    createHeroChart();
    return;
  }

  gsap.set(mockup, { opacity: 0, x: 40 });
  gsap.set(kpiEls, { opacity: 0, y: 14 });

  gsap
    .timeline({ delay: 0.3 })
    .to(mockup, { opacity: 1, x: 0, duration: 0.6, ease: 'power3.out' })
    .to(kpiEls, { opacity: 1, y: 0, duration: 0.45, ease: 'power3.out', stagger: 0.12 }, '-=0.25')
    .add(() => runKpiCountUps(kpiEls), '-=0.15')
    .add(() => createHeroChart(), '-=0.4');
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.querySelector('[data-landing-page]')) return;

  initScrollReveals();
  initParallaxShapes();
  initMarquee();
  initHeroMockup();
  initScrollCountUp();
});
