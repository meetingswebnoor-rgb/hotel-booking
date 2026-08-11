/**
 * dashboard.js — fetches GET /dashboard/data and renders the
 * analytics dashboard: KPI count-ups with trend badges, Chart.js
 * charts, the per-hotel breakdown table (click a row to drill in,
 * click again to clear), and recent bookings with status pills.
 * Polls every 15s so new bookings show up live, pausing while the
 * tab is hidden.
 */

import { api } from './api.js';
import { animateCountUp } from './animations.js';
import {
  createLineChart,
  createBarChart,
  createDonutChart,
  createHorizontalBarChart,
  updateChartData,
} from './charts.js';

const POLL_INTERVAL_MS = 15000;

const chartInstances = {};
let drillDownHotelId = null;
let pollTimer = null;

/** Mirrors the money() PHP helper's Indian digit grouping (1,00,000). */
function formatIndianCurrency(value, decimals = 0) {
  const negative = value < 0;
  const fixed = Math.abs(value).toFixed(decimals);
  const [intPart, decPart] = fixed.split('.');
  const lastThree = intPart.slice(-3);
  const rest = intPart.slice(0, -3);
  const grouped = rest ? `${rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',')},${lastThree}` : lastThree;

  return `${negative ? '-' : ''}${grouped}${decPart ? `.${decPart}` : ''}`;
}

const STATUS_BADGE = {
  pending: 'badge--warning',
  confirmed: 'badge--info',
  checked_in: 'badge--success',
  checked_out: 'badge--neutral',
  cancelled: 'badge--danger',
  no_show: 'badge--danger',
  rejected: 'badge--danger',
};

function statusLabel(status) {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function renderTrend(el, kpi) {
  el.hidden = false;
  el.title = 'vs. same period last month';

  if (kpi.direction === 'flat') {
    el.className = 'stat-card__trend';
    el.textContent = '— flat';
    return;
  }

  const isUp = kpi.direction === 'up';
  el.className = `stat-card__trend stat-card__trend--${isUp ? 'up' : 'down'}`;
  el.textContent = kpi.trend === null
    ? (isUp ? '↗ New' : '↘')
    : `${isUp ? '↗' : '↘'} ${Math.abs(kpi.trend)}%`;
}

function renderKpis(kpis) {
  Object.entries(kpis).forEach(([key, kpi]) => {
    const card = document.querySelector(`[data-kpi="${key}"]`);
    if (!card) return;

    const valueEl = card.querySelector('[data-kpi-value]');
    const trendEl = card.querySelector('[data-kpi-trend]');
    if (!valueEl) return;

    valueEl.classList.remove('skeleton');
    const prefix = valueEl.getAttribute('data-kpi-prefix') || '';
    const decimals = parseInt(valueEl.getAttribute('data-kpi-decimals') || '0', 10);

    animateCountUp(valueEl, kpi.value, {
      prefix,
      decimals,
      format: prefix ? formatIndianCurrency : null,
    });

    if (trendEl) renderTrend(trendEl, kpi);
  });
}

function renderChart(key, chartData, type) {
  const skeleton = document.querySelector(`[data-chart-skeleton="${key}"]`);
  const empty = document.querySelector(`[data-chart-empty="${key}"]`);
  const wrap = document.querySelector(`[data-chart-wrap="${key}"]`);
  const canvas = document.querySelector(`canvas[data-chart="${key}"]`);
  if (!canvas) return;

  if (skeleton) skeleton.hidden = true;

  const total = (chartData.data || []).reduce((sum, v) => sum + v, 0);

  if (!chartData.labels.length || total <= 0) {
    if (empty) empty.hidden = false;
    if (wrap) wrap.hidden = true;
    return;
  }

  if (empty) empty.hidden = true;
  if (wrap) wrap.hidden = false;

  if (chartInstances[key]) {
    if (type === 'line') {
      updateChartData(chartInstances[key], { labels: chartData.labels, datasets: [{ data: chartData.data }] });
    } else {
      updateChartData(chartInstances[key], { labels: chartData.labels, data: chartData.data });
    }
    return;
  }

  const ctx = canvas.getContext('2d');

  if (type === 'line') {
    chartInstances[key] = createLineChart(ctx, { labels: chartData.labels, datasets: [{ data: chartData.data, label: 'Bookings' }] });
  } else if (type === 'donut') {
    chartInstances[key] = createDonutChart(ctx, { labels: chartData.labels, data: chartData.data });
  } else if (type === 'hbar') {
    chartInstances[key] = createHorizontalBarChart(ctx, { labels: chartData.labels, datasets: [{ data: chartData.data, label: 'Bookings' }] });
  } else if (type === 'bar') {
    chartInstances[key] = createBarChart(ctx, { labels: chartData.labels, datasets: [{ data: chartData.data, label: 'Earnings (₹)' }] });
  }
}

function renderCharts(charts) {
  renderChart('monthly_trend', charts.monthly_trend, 'line');
  renderChart('room_type_distribution', charts.room_type_distribution, 'hbar');
  renderChart('ota_vs_direct', charts.ota_vs_direct, 'donut');

  if (charts.revenue_by_ota) renderChart('revenue_by_ota', charts.revenue_by_ota, 'donut');
  if (charts.top_hotels) renderChart('top_hotels', charts.top_hotels, 'bar');
}

function renderBreakdown(rows) {
  const body = document.querySelector('[data-breakdown-body]');
  if (!body) return; // not rendered at all for restricted roles

  const skeleton = document.querySelector('[data-breakdown-skeleton]');
  const table = document.querySelector('[data-breakdown-table]');
  const empty = document.querySelector('[data-breakdown-empty]');

  if (skeleton) skeleton.hidden = true;

  if (!rows.length) {
    if (empty) empty.hidden = false;
    if (table) table.hidden = true;
    return;
  }

  if (empty) empty.hidden = true;
  if (table) table.hidden = false;

  body.innerHTML = rows
    .map(
      (row) => `
        <tr class="breakdown-row" data-hotel-id="${row.hotel_id}">
          <td>${row.hotel_name}</td>
          <td>${row.bookings}</td>
          <td>₹${formatIndianCurrency(row.revenue)}</td>
          <td>₹${formatIndianCurrency(row.earnings)}</td>
          <td>₹${formatIndianCurrency(row.commission_tax)}</td>
        </tr>
      `
    )
    .join('');
}

function renderRecentBookings(rows, canViewReports) {
  const body = document.querySelector('[data-recent-body]');
  if (!body) return;

  const skeleton = document.querySelector('[data-recent-skeleton]');
  const table = document.querySelector('[data-recent-table]');
  const empty = document.querySelector('[data-recent-empty]');

  if (skeleton) skeleton.hidden = true;

  if (!rows.length) {
    if (empty) empty.hidden = false;
    if (table) table.hidden = true;
    return;
  }

  if (empty) empty.hidden = true;
  if (table) table.hidden = false;

  body.innerHTML = rows
    .map(
      (row) => `
        <tr>
          <td class="font-mono">${row.booking_id}</td>
          <td>${row.guest_name}</td>
          <td>${row.hotel_name}</td>
          <td>${row.checkin_date}</td>
          <td><span class="badge ${STATUS_BADGE[row.status] ?? 'badge--neutral'}">${statusLabel(row.status)}</span></td>
          ${canViewReports ? `<td>₹${formatIndianCurrency(row.total_room_rent)}</td>` : ''}
        </tr>
      `
    )
    .join('');
}

function renderDrilldownBanner(hotelName) {
  const banner = document.querySelector('[data-drilldown-banner]');
  if (!banner) return;

  banner.hidden = !hotelName;

  if (hotelName) {
    const nameEl = banner.querySelector('[data-drilldown-hotel-name]');
    if (nameEl) nameEl.textContent = hotelName;
  }
}

async function loadDashboard() {
  const query = drillDownHotelId ? `?hotel_id=${encodeURIComponent(drillDownHotelId)}` : '';

  let data;
  try {
    data = await api(`/dashboard/data${query}`);
  } catch {
    return;
  }

  const periodEl = document.querySelector('[data-period-label]');
  if (periodEl) periodEl.textContent = `· ${data.period_label} (month to date)`;

  const emptyState = document.querySelector('[data-dashboard-empty]');
  const content = document.querySelector('[data-dashboard-content]');

  if (!data.has_data) {
    if (emptyState) emptyState.hidden = false;
    if (content) content.hidden = true;
    return;
  }

  if (emptyState) emptyState.hidden = true;
  if (content) content.hidden = false;

  renderKpis(data.kpis);
  renderCharts(data.charts);
  renderBreakdown(data.breakdown);
  renderRecentBookings(data.recent_bookings, window.HotezoDashboard?.canViewReports ?? false);
}

function initDrillDown() {
  document.addEventListener('click', (event) => {
    const row = event.target.closest('.breakdown-row');

    if (row) {
      const hotelId = row.getAttribute('data-hotel-id');
      const hotelName = row.querySelector('td')?.textContent ?? null;

      drillDownHotelId = drillDownHotelId === hotelId ? null : hotelId;
      renderDrilldownBanner(drillDownHotelId ? hotelName : null);
      loadDashboard();

      return;
    }

    if (event.target.closest('[data-clear-drilldown]')) {
      drillDownHotelId = null;
      renderDrilldownBanner(null);
      loadDashboard();
    }
  });
}

function initPolling() {
  const start = () => {
    if (pollTimer) return;
    pollTimer = window.setInterval(loadDashboard, POLL_INTERVAL_MS);
  };

  const stop = () => {
    window.clearInterval(pollTimer);
    pollTimer = null;
  };

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stop();
    } else {
      start();
    }
  });

  start();
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.querySelector('[data-dashboard-content]')) return;

  initDrillDown();
  loadDashboard();
  initPolling();
});
