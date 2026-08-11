/**
 * booking-form.js — the booking entry form's interactivity: async
 * booking-ID uniqueness check, the room-line repeater, per-hotel
 * room-type/rate-plan lookups, capacity warnings, and a live
 * calculation preview that mirrors App\Services\BookingCalculator
 * exactly. This is a *preview only* — the server recomputes every
 * figure from the submitted room lines on save and never trusts
 * whatever this file displays.
 */

import { api } from './api.js';
import { animateCountUp } from './animations.js';
import { formatIndianCurrency } from './format.js';

const GST_LOW_RATE = 5.0;
const GST_HIGH_RATE = 18.0;
const GST_THRESHOLD = 7499.0;
const TDS_RATE = 0.1;
const TCS_RATE = 0.5;
const COMMISSION_GST_RATE = 18.0;

let roomTypesData = [];
let ratePlansData = [];
let roomLineCounter = 0;

function round2(n) {
  return Math.round((n + Number.EPSILON) * 100) / 100;
}

function nightsBetween(checkin, checkout) {
  if (!checkin || !checkout) return 0;
  const diff = Math.round((new Date(checkout) - new Date(checkin)) / 86400000);
  return diff > 0 ? diff : 0;
}

/** Mirrors App\Services\BookingCalculator::calculate() exactly. */
function calculate(roomLines, nights, otaCommissionPct, hotelCommissionPct, source) {
  let totalRoomRent = 0;
  let hotelGst = 0;

  roomLines.forEach(({ nightlyRate, quantity }) => {
    const subtotal = round2(nightlyRate * quantity * nights);
    totalRoomRent += subtotal;
    const gstRate = nightlyRate <= GST_THRESHOLD ? GST_LOW_RATE : GST_HIGH_RATE;
    hotelGst += round2((subtotal * gstRate) / 100);
  });

  totalRoomRent = round2(totalRoomRent);
  hotelGst = round2(hotelGst);

  const tds = round2((totalRoomRent * TDS_RATE) / 100);
  const tcs = round2((totalRoomRent * TCS_RATE) / 100);
  const otaCommission = round2((totalRoomRent * otaCommissionPct) / 100);
  const hotezoCommission = round2((totalRoomRent * hotelCommissionPct) / 100);
  const gstOnCommission = round2(((otaCommission + hotezoCommission) * COMMISSION_GST_RATE) / 100);
  const totalCommissionTaxes = round2(otaCommission + hotezoCommission + gstOnCommission);
  const hotelEarning = round2(totalRoomRent + hotelGst - otaCommission - hotezoCommission - gstOnCommission - tds);
  const hotelCollection = source === 'ota'
    ? round2(totalRoomRent + hotelGst - otaCommission)
    : round2(totalRoomRent + hotelGst);
  const hotezoCollection = round2(hotezoCommission + gstOnCommission);

  return {
    nights,
    total_room_rent: totalRoomRent,
    hotel_gst: hotelGst,
    tds,
    tcs,
    ota_commission: otaCommission,
    hotezo_commission: hotezoCommission,
    gst_on_commission: gstOnCommission,
    total_commission_taxes: totalCommissionTaxes,
    hotel_earning: hotelEarning,
    hotel_collection: hotelCollection,
    hotezo_collection: hotezoCollection,
  };
}

function deriveSource(otaSelect) {
  const option = otaSelect?.options[otaSelect.selectedIndex];
  const name = option?.textContent.trim();

  if (name === 'Walk-in') return 'walkin';
  if (name === 'Direct Booking') return 'online';

  return name ? 'ota' : 'online';
}

function renderSummary(result) {
  Object.entries(result).forEach(([key, value]) => {
    const el = document.querySelector(`[data-calc="${key}"]`);
    if (!el) return;

    if (key === 'nights') {
      el.textContent = String(value);
      return;
    }

    animateCountUp(el, value, { prefix: '₹', decimals: 2, duration: 0.3, format: formatIndianCurrency });
  });
}

function recalc(form) {
  const checkin = form.querySelector('[data-checkin]')?.value;
  const checkout = form.querySelector('[data-checkout]')?.value;
  const nights = nightsBetween(checkin, checkout);

  const nightsDisplay = form.querySelector('[data-nights-display]');
  if (nightsDisplay) nightsDisplay.textContent = String(nights);

  const roomLines = Array.from(form.querySelectorAll('[data-room-line]'))
    .map((line) => ({
      nightlyRate: parseFloat(line.querySelector('[data-nightly-rate]')?.value) || 0,
      quantity: parseInt(line.querySelector('[data-quantity]')?.value, 10) || 0,
    }))
    .filter((l) => l.quantity > 0);

  const otaSelect = form.querySelector('[data-ota-select]');
  const otaOption = otaSelect?.options[otaSelect.selectedIndex];
  const otaCommissionPct = otaOption ? parseFloat(otaOption.dataset.commission) || 0 : 0;
  const source = deriveSource(otaSelect);

  const hotelSelect = form.querySelector('[data-hotel-select]');
  let hotelCommissionPct;

  if (hotelSelect) {
    const hotelOption = hotelSelect.options[hotelSelect.selectedIndex];
    hotelCommissionPct = hotelOption ? parseFloat(hotelOption.dataset.commission) || 0 : 0;
  } else {
    hotelCommissionPct = parseFloat(form.dataset.hotelCommission) || 0;
  }

  renderSummary(calculate(roomLines, nights, otaCommissionPct, hotelCommissionPct, source));
}

function checkCapacity(line) {
  const roomTypeSelect = line.querySelector('[data-room-type]');
  const warningEl = line.querySelector('[data-capacity-warning]');
  if (!roomTypeSelect || !warningEl) return;

  const meta = roomTypesData.find((rt) => rt.room_type === roomTypeSelect.value);

  if (!meta) {
    warningEl.hidden = true;
    return;
  }

  const adults = parseInt(line.querySelector('[data-adults]')?.value, 10) || 0;
  const children = parseInt(line.querySelector('[data-children]')?.value, 10) || 0;

  if (adults > meta.max_adults || children > meta.max_children) {
    warningEl.hidden = false;
    warningEl.textContent = `⚠ Exceeds room capacity — max ${meta.max_adults} adults, ${meta.max_children} children for ${meta.room_type}.`;
  } else {
    warningEl.hidden = true;
  }
}

function populateRatePlans(line) {
  const roomTypeSelect = line.querySelector('[data-room-type]');
  const ratePlanSelect = line.querySelector('[data-rate-plan]');
  if (!roomTypeSelect || !ratePlanSelect) return;

  const currentValue = ratePlanSelect.value;
  const matches = ratePlansData.filter((rp) => rp.room_type === roomTypeSelect.value);

  ratePlanSelect.innerHTML = '<option value="">Custom rate</option>'
    + matches.map((rp) => `<option value="${rp.id}" data-base-price="${rp.base_price}">${rp.plan_name} (${rp.season})</option>`).join('');

  if (matches.some((rp) => rp.id === currentValue)) {
    ratePlanSelect.value = currentValue;
  }
}

function updateRemoveButtonsVisibility() {
  const lines = document.querySelectorAll('[data-room-line]');
  lines.forEach((line) => {
    const btn = line.querySelector('[data-remove-room]');
    if (btn) btn.hidden = lines.length <= 1;
  });
}

function wireRoomLine(line, form) {
  const roomTypeSelect = line.querySelector('[data-room-type]');
  const ratePlanSelect = line.querySelector('[data-rate-plan]');
  const nightlyRateInput = line.querySelector('[data-nightly-rate]');
  const adultsInput = line.querySelector('[data-adults]');
  const childrenInput = line.querySelector('[data-children]');
  const quantityInput = line.querySelector('[data-quantity]');
  const removeBtn = line.querySelector('[data-remove-room]');

  roomTypeSelect?.addEventListener('change', () => {
    populateRatePlans(line);

    const meta = roomTypesData.find((rt) => rt.room_type === roomTypeSelect.value);
    if (meta && nightlyRateInput) nightlyRateInput.value = meta.base_price;

    checkCapacity(line);
    recalc(form);
  });

  ratePlanSelect?.addEventListener('change', () => {
    const option = ratePlanSelect.options[ratePlanSelect.selectedIndex];
    const basePrice = option?.dataset.basePrice;
    if (basePrice && nightlyRateInput) nightlyRateInput.value = basePrice;
    recalc(form);
  });

  [adultsInput, childrenInput].forEach((input) => {
    input?.addEventListener('input', () => {
      checkCapacity(line);
      recalc(form);
    });
  });

  [nightlyRateInput, quantityInput].forEach((input) => {
    input?.addEventListener('input', () => recalc(form));
  });

  removeBtn?.addEventListener('click', () => {
    if (document.querySelectorAll('[data-room-line]').length <= 1) return;
    line.remove();
    updateRemoveButtonsVisibility();
    recalc(form);
  });

  populateRatePlans(line);
  checkCapacity(line);
}

function initAddRoom(form) {
  const template = form.querySelector('[data-room-line-template]');
  const container = form.querySelector('[data-room-lines]');
  const addBtn = form.querySelector('[data-add-room]');
  if (!template || !container || !addBtn) return;

  addBtn.addEventListener('click', () => {
    const html = template.innerHTML.replace(/__INDEX__/g, String(roomLineCounter++));
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const line = wrapper.firstElementChild;

    container.appendChild(line);
    wireRoomLine(line, form);
    updateRemoveButtonsVisibility();
    recalc(form);
  });
}

async function fetchRoomsForHotel(hotelId, form) {
  if (!hotelId) {
    roomTypesData = [];
    ratePlansData = [];
  } else {
    try {
      const data = await api(`/bookings/rooms?hotel_id=${encodeURIComponent(hotelId)}`);
      roomTypesData = data.room_types || [];
      ratePlansData = data.rate_plans || [];
    } catch {
      roomTypesData = [];
      ratePlansData = [];
    }
  }

  document.querySelectorAll('[data-room-line]').forEach((line) => {
    populateRatePlans(line);
    checkCapacity(line);
  });

  recalc(form);
}

function initBookingIdCheck(form) {
  const input = form.querySelector('[data-booking-id-input]');
  const statusEl = form.querySelector('[data-booking-id-status]');
  if (!input || !statusEl) return;

  const excludeId = input.dataset.excludeId || '';
  let timer = null;

  const check = async () => {
    const value = input.value.trim();

    if (!value) {
      statusEl.innerHTML = '';
      return;
    }

    try {
      const query = `booking_id=${encodeURIComponent(value)}${excludeId ? `&exclude_id=${encodeURIComponent(excludeId)}` : ''}`;
      const data = await api(`/bookings/check-id?${query}`);
      statusEl.innerHTML = data.available
        ? '<span class="field-status-icon--ok" title="Available">&#10003;</span>'
        : '<span class="field-status-icon--bad" title="Already in use">&#10007;</span>';
    } catch {
      statusEl.innerHTML = '';
    }
  };

  input.addEventListener('input', () => {
    window.clearTimeout(timer);
    statusEl.innerHTML = '';
    timer = window.setTimeout(check, 500);
  });

  check();
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-booking-form]');
  if (!form) return;

  roomLineCounter = document.querySelectorAll('[data-room-line]').length;

  initBookingIdCheck(form);
  initAddRoom(form);

  document.querySelectorAll('[data-room-line]').forEach((line) => wireRoomLine(line, form));
  updateRemoveButtonsVisibility();

  const hotelSelect = form.querySelector('[data-hotel-select]');
  const initialHotelId = hotelSelect ? hotelSelect.value : form.dataset.hotelId;

  if (initialHotelId) {
    fetchRoomsForHotel(initialHotelId, form);
  }

  hotelSelect?.addEventListener('change', () => fetchRoomsForHotel(hotelSelect.value, form));

  form.querySelectorAll('[data-checkin], [data-checkout], [data-ota-select]').forEach((el) => {
    el.addEventListener('input', () => recalc(form));
    el.addEventListener('change', () => recalc(form));
  });

  recalc(form);
});
