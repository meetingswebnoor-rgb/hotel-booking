/**
 * landing.js — page-specific behavior for the public marketing landing
 * page only (app/Views/pages/public/home.php): scroll-triggered section
 * reveals (GSAP + ScrollTrigger, already loaded by layouts/public.php
 * but never wired up before this page needed it), the hero's mini demo
 * chart, the OTA marquee's reduced-motion pause, and the scroll-count-up
 * stats. Every reveal checks prefers-reduced-motion and just shows the
 * final state instantly instead, same convention as animations.js.
 */

import { initScrollCountUp } from './animations.js';
import { createLineChart } from './charts.js';

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

function initHeroChart() {
  const canvas = document.querySelector('[data-mockup-chart]');
  if (!canvas || typeof Chart === 'undefined') return;

  createLineChart(canvas.getContext('2d'), {
    labels: ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
    datasets: [{ data: [62, 78, 71, 94, 88, 121], label: 'Bookings', pointRadius: 0 }],
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.querySelector('[data-landing-page]')) return;

  initScrollReveals();
  initParallaxShapes();
  initMarquee();
  initHeroChart();
  initScrollCountUp();
});
