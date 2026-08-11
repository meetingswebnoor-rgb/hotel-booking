/**
 * booking-list.js — the filterable, paginated bookings table. Filters
 * combine (AND) and are synced to the URL query string via
 * history.replaceState (so the exact filtered view survives a
 * refresh or gets bookmarked/shared) and drive GET /bookings/data.
 * Clicking a row fetches GET /bookings/{id}/detail into a slide-over
 * drawer with the full financial breakdown.
 */

import { api } from './api.js';
import { animateCountUp } from './animations.js';
import {
  formatIndianCurrency,
  statusBadgeClass,
  paymentStatusBadgeClass,
  statusLabel,
  otaBadgeColor,
} from './format.js';

const FILTER_KEYS = ['hotel_id', 'ota_id', 'status', 'date_from', 'date_to', 'q'];
const PER_PAGE_OPTIONS = [25, 50, 100];
const SEARCH_DEBOUNCE_MS = 400;

let state = { page: 1, per_page: 25 };
let searchTimer = null;
let currentDrawerBookingId = null;
let lockedHotelId = null;

function formatMoney(value) {
  return `₹${formatIndianCurrency(value, 2)}`;
}

function readStateFromUrl() {
  const params = new URLSearchParams(window.location.search);
  const next = { page: 1, per_page: 25 };

  FILTER_KEYS.forEach((key) => {
    const value = params.get(key);
    if (value) next[key] = value;
  });

  const page = parseInt(params.get('page'), 10);
  if (page > 0) next.page = page;

  const perPage = parseInt(params.get('per_page'), 10);
  if (PER_PAGE_OPTIONS.includes(perPage)) next.per_page = perPage;

  return next;
}

function writeStateToUrl() {
  // Embedded on the hotel hub's Bookings tab, this list shares the URL
  // with the tab's own ?tab= query param — rewriting it here would
  // silently drop that and knock the user back to the Details tab on
  // refresh, so the embedded view just keeps filter state in memory.
  if (lockedHotelId) return;

  const params = new URLSearchParams();

  FILTER_KEYS.forEach((key) => {
    if (state[key]) params.set(key, state[key]);
  });

  if (state.page > 1) params.set('page', String(state.page));
  if (state.per_page !== 25) params.set('per_page', String(state.per_page));

  const query = params.toString();
  const url = query ? `${window.location.pathname}?${query}` : window.location.pathname;
  window.history.replaceState(null, '', url);
}

function applyStateToControls(form) {
  FILTER_KEYS.forEach((key) => {
    const el = form.querySelector(`[data-filter="${key}"]`);
    if (el) el.value = state[key] || '';
  });

  const perPageEl = document.querySelector('[data-per-page]');
  if (perPageEl) perPageEl.value = String(state.per_page);
}

function buildQuery() {
  const params = new URLSearchParams();

  FILTER_KEYS.forEach((key) => {
    if (state[key]) params.set(key, state[key]);
  });

  params.set('page', String(state.page));
  params.set('per_page', String(state.per_page));

  return params.toString();
}

function animateStat(key, value, opts) {
  const el = document.querySelector(`[data-stat="${key}"]`);
  if (!el) return;

  el.classList.remove('skeleton');
  animateCountUp(el, value, { duration: 0.4, ...opts });
}

function renderStats(data) {
  animateStat('page_count', data.page_stats.count, {});
  animateStat('page_revenue', data.page_stats.revenue, { prefix: '₹', decimals: 2, format: formatIndianCurrency });
  animateStat('page_guests', data.page_stats.guests, {});
  animateStat('total_matches', data.total, {});
}

function renderRow(row, canViewReports) {
  const otaName = row.ota_name || 'Direct';
  const initial = otaName.charAt(0).toUpperCase();
  const color = otaBadgeColor(otaName);
  const earningCell = canViewReports ? `<td>${formatMoney(row.hotel_earning)}</td>` : '';

  return `
    <tr class="booking-row" data-booking-id="${row.id}">
      <td class="font-mono">${row.booking_id}</td>
      <td>${row.guest_name}</td>
      <td>${row.hotel_name}</td>
      <td>
        <span class="ota-badge" style="background:${color}" aria-hidden="true">${initial}</span>
        ${otaName}
      </td>
      <td>${row.checkin_date} &rarr; ${row.checkout_date}</td>
      <td>${row.nights}</td>
      <td>${formatMoney(row.total_room_rent)}</td>
      ${earningCell}
      <td><span class="badge ${statusBadgeClass(row.status)}">${statusLabel(row.status)}</span></td>
      <td><span class="badge ${paymentStatusBadgeClass(row.ota_payment_status)}">${statusLabel(row.ota_payment_status)}</span></td>
      <td class="booking-row__actions">
        <a href="/bookings/${row.id}/edit" title="Edit">Edit</a>
        <a href="/bookings/${row.id}/voucher" target="_blank" title="Voucher">Voucher</a>
      </td>
    </tr>
  `;
}

function renderTable(data) {
  const skeleton = document.querySelector('[data-bookings-skeleton]');
  const table = document.querySelector('[data-bookings-table]');
  const body = document.querySelector('[data-bookings-body]');
  const empty = document.querySelector('[data-bookings-empty]');
  const paginationBar = document.querySelector('[data-pagination-bar]');

  if (skeleton) skeleton.hidden = true;

  if (!data.rows.length) {
    if (empty) empty.hidden = false;
    if (table) table.hidden = true;
    if (paginationBar) paginationBar.hidden = true;
    return;
  }

  if (empty) empty.hidden = true;
  if (table) table.hidden = false;
  if (paginationBar) paginationBar.hidden = false;

  if (body) {
    body.innerHTML = data.rows.map((row) => renderRow(row, data.can_view_reports)).join('');
  }
}

function renderPagination(data) {
  const info = document.querySelector('[data-pagination-info]');
  const pageLabel = document.querySelector('[data-page-label]');
  const prevBtn = document.querySelector('[data-prev-page]');
  const nextBtn = document.querySelector('[data-next-page]');

  const start = data.total === 0 ? 0 : (data.page - 1) * data.per_page + 1;
  const end = Math.min(data.page * data.per_page, data.total);

  if (info) info.textContent = `Showing ${start}-${end} of ${data.total}`;
  if (pageLabel) pageLabel.textContent = `Page ${data.page} of ${data.total_pages}`;
  if (prevBtn) prevBtn.disabled = data.page <= 1;
  if (nextBtn) nextBtn.disabled = data.page >= data.total_pages;
}

async function loadBookings() {
  let data;

  try {
    data = await api(`/bookings/data?${buildQuery()}`);
  } catch {
    return;
  }

  renderStats(data);
  renderTable(data);
  renderPagination(data);
}

function drawerRow(label, value, highlight = false) {
  return `
    <div class="calc-summary__row ${highlight ? 'calc-summary__row--highlight' : ''}">
      <dt>${label}</dt>
      <dd>${formatMoney(value)}</dd>
    </div>
  `;
}

function renderDrawerContent(b) {
  const rooms = (b.rooms || [])
    .map(
      (r) => `
        <tr>
          <td>${r.room_type}</td>
          <td>${r.quantity}</td>
          <td>${formatMoney(r.nightly_rate)}</td>
          <td>${r.adults}A / ${r.children}C</td>
        </tr>
      `
    )
    .join('');

  const financials = b.financials
    ? `
      <div class="drawer-section">
        <h4 class="mb-3">Financial breakdown</h4>
        <dl class="calc-summary__list">
          ${drawerRow('Total Room Rent', b.total_room_rent)}
          ${drawerRow('Hotel GST', b.financials.hotel_gst)}
          ${drawerRow('TDS', b.financials.tds)}
          ${drawerRow('TCS', b.financials.tcs)}
          ${drawerRow('OTA Commission', b.financials.ota_commission)}
          ${drawerRow('Hotezo Commission', b.financials.hotezo_commission)}
          ${drawerRow('GST on Commission', b.financials.gst_on_commission)}
          ${drawerRow('Total Commission &amp; Taxes', b.financials.total_commission_taxes)}
          ${drawerRow('Hotel Earning', b.financials.hotel_earning, true)}
          ${drawerRow('Hotel Collection', b.financials.hotel_collection)}
          ${drawerRow('Hotezo Collection', b.financials.hotezo_collection)}
        </dl>
      </div>
    `
    : `
      <div class="drawer-section">
        <h4 class="mb-3">Amount</h4>
        <dl class="calc-summary__list">${drawerRow('Total Room Rent', b.total_room_rent)}</dl>
      </div>
    `;

  return `
    <div class="drawer-section">
      <div class="flex-between mb-2">
        <span class="badge ${statusBadgeClass(b.status)}">${statusLabel(b.status)}</span>
        <span class="badge ${paymentStatusBadgeClass(b.ota_payment_status)}">${statusLabel(b.ota_payment_status)}</span>
      </div>
      <p class="text-low font-mono" style="font-size: 0.8125rem;">${b.booking_id}</p>
    </div>

    <div class="drawer-section">
      <h4 class="mb-2">Guest</h4>
      <p>${b.guest_name}</p>
      <p class="text-med">${b.guest_mobile}${b.guest_email ? ` &middot; ${b.guest_email}` : ''}</p>
    </div>

    <div class="drawer-section">
      <h4 class="mb-2">Stay</h4>
      <p>${b.hotel_name} &middot; ${b.ota_name}</p>
      <p class="text-med">${b.checkin_date} &rarr; ${b.checkout_date} (${b.nights} nights)</p>
      <p class="text-med">${b.adults} adults, ${b.children} children</p>
    </div>

    <div class="drawer-section">
      <h4 class="mb-2">Rooms</h4>
      <table class="table">
        <thead><tr><th>Type</th><th>Qty</th><th>Rate</th><th>Occupancy</th></tr></thead>
        <tbody>${rooms}</tbody>
      </table>
    </div>

    ${financials}

    ${b.payment_remarks ? `<div class="drawer-section"><h4 class="mb-2">Payment remarks</h4><p class="text-med">${b.payment_remarks}</p></div>` : ''}
    ${b.internal_notes ? `<div class="drawer-section"><h4 class="mb-2">Internal notes</h4><p class="text-med">${b.internal_notes}</p></div>` : ''}
  `;
}

async function openDrawer(bookingId) {
  currentDrawerBookingId = bookingId;

  const backdrop = document.querySelector('[data-booking-drawer]');
  const body = document.querySelector('[data-drawer-body]');
  const title = document.querySelector('[data-drawer-title]');
  if (!backdrop || !body) return;

  backdrop.classList.add('open');
  document.body.style.overflow = 'hidden';
  body.innerHTML = '<div class="skeleton" style="height: 220px; border-radius: var(--radius-md);"></div>';

  let data;

  try {
    data = await api(`/bookings/${bookingId}/detail`);
  } catch {
    body.innerHTML = '<p class="text-med">Could not load this booking.</p>';
    return;
  }

  if (currentDrawerBookingId !== bookingId) return; // closed/replaced while fetching

  if (title) title.textContent = data.booking_id;
  body.innerHTML = renderDrawerContent(data);

  const editLink = document.querySelector('[data-drawer-edit]');
  const voucherLink = document.querySelector('[data-drawer-voucher]');

  if (editLink) {
    editLink.href = data.edit_url;
    editLink.hidden = !data.can_edit;
  }

  if (voucherLink) voucherLink.href = data.voucher_url;
}

function closeDrawer() {
  const backdrop = document.querySelector('[data-booking-drawer]');
  if (backdrop) backdrop.classList.remove('open');
  document.body.style.overflow = '';
  currentDrawerBookingId = null;
}

function initDrawer() {
  document.addEventListener('click', (event) => {
    const row = event.target.closest('.booking-row');

    if (row && !event.target.closest('.booking-row__actions')) {
      openDrawer(row.dataset.bookingId);
      return;
    }

    if (event.target.closest('[data-drawer-close]')) {
      closeDrawer();
      return;
    }

    if (event.target === document.querySelector('[data-booking-drawer]')) {
      closeDrawer();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && currentDrawerBookingId) {
      closeDrawer();
    }
  });
}

function initFilters(form) {
  FILTER_KEYS.forEach((key) => {
    const el = form.querySelector(`[data-filter="${key}"]`);
    if (!el) return;

    const apply = () => {
      state[key] = el.value;
      state.page = 1;
      writeStateToUrl();
      loadBookings();
    };

    if (key === 'q') {
      el.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(apply, SEARCH_DEBOUNCE_MS);
      });
    } else {
      el.addEventListener('change', apply);
    }
  });

  document.querySelector('[data-clear-filters]')?.addEventListener('click', () => {
    FILTER_KEYS.forEach((key) => delete state[key]);
    if (lockedHotelId) state.hotel_id = lockedHotelId;
    state.page = 1;
    applyStateToControls(form);
    writeStateToUrl();
    loadBookings();
  });
}

function initPagination() {
  document.querySelector('[data-prev-page]')?.addEventListener('click', () => {
    if (state.page <= 1) return;
    state.page -= 1;
    writeStateToUrl();
    loadBookings();
  });

  document.querySelector('[data-next-page]')?.addEventListener('click', () => {
    state.page += 1;
    writeStateToUrl();
    loadBookings();
  });

  document.querySelector('[data-per-page]')?.addEventListener('change', (event) => {
    const value = parseInt(event.target.value, 10);
    state.per_page = PER_PAGE_OPTIONS.includes(value) ? value : 25;
    state.page = 1;
    writeStateToUrl();
    loadBookings();
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const filterBar = document.querySelector('.filter-bar');
  if (!filterBar) return;

  lockedHotelId = document.querySelector('[data-locked-hotel-id]')?.dataset.lockedHotelId || null;
  state = lockedHotelId ? { page: 1, per_page: 25, hotel_id: lockedHotelId } : readStateFromUrl();
  applyStateToControls(filterBar);

  initFilters(filterBar);
  initPagination();
  initDrawer();
  loadBookings();
});
