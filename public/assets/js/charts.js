/**
 * charts.js — Chart.js factory helpers themed with Hotezo's design
 * tokens. Chart.js is loaded globally via CDN (window.Chart).
 */

function cssVar(name) {
  return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

function baseOptions() {
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        labels: {
          color: cssVar('--text-med'),
          font: { family: 'Plus Jakarta Sans', size: 12 },
        },
      },
    },
    scales: {
      x: {
        grid: { color: cssVar('--border') },
        ticks: { color: cssVar('--text-low') },
      },
      y: {
        grid: { color: cssVar('--border') },
        ticks: { color: cssVar('--text-low') },
      },
    },
  };
}

export function createLineChart(ctx, { labels, datasets }) {
  return new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: datasets.map((ds) => ({
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 0,
        fill: true,
        backgroundColor: (context) => {
          const { chart } = context;
          const { ctx: c, chartArea } = chart;
          if (!chartArea) return null;
          const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
          gradient.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
          gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');
          return gradient;
        },
        borderColor: cssVar('--color-primary'),
        ...ds,
      })),
    },
    options: baseOptions(),
  });
}

export function createBarChart(ctx, { labels, datasets }) {
  return new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: datasets.map((ds) => ({
        borderRadius: 8,
        backgroundColor: cssVar('--color-primary-2'),
        ...ds,
      })),
    },
    options: baseOptions(),
  });
}

export function createDonutChart(ctx, { labels, data, colors }) {
  const palette = colors ?? [
    cssVar('--color-primary'),
    cssVar('--color-cyan'),
    cssVar('--color-amber'),
    cssVar('--color-emerald'),
    cssVar('--color-rose'),
  ];

  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data, backgroundColor: palette, borderWidth: 0 }],
    },
    options: {
      ...baseOptions(),
      cutout: '68%',
      scales: {},
    },
  });
}
