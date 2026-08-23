/**
 * commission-invoice-form.js — the commission-invoice generator: fetch
 * a live breakup preview whenever hotel/month/billing-entity are all
 * selected, let every figure be edited afterward with a live-recomputed
 * summary (client-side arithmetic only — the server re-derives
 * total_tax/grand_total/net_receivable from the submitted breakdown on
 * save, same "don't trust the client for the final number" rule as the
 * booking form, just not a full re-pull of bookings since editing is
 * the point here), and warn on navigating away with unsaved edits.
 */

import { api } from './api.js';
import { formatIndianCurrency } from './format.js';

const FIELDS = [
  'total_bookings', 'total_room_nights', 'total_room_rent', 'total_ota_commission', 'taxable_value',
  'cgst_rate', 'cgst_amount', 'sgst_rate', 'sgst_amount', 'igst_rate', 'igst_amount',
  'tds_amount', 'tcs_amount',
];

let isDirty = false;
let isSubmitting = false;

function formatMoney(value) {
  return `₹${formatIndianCurrency(value, 2)}`;
}

function fieldValue(form, name) {
  return parseFloat(form.querySelector(`[data-field="${name}"]`)?.value) || 0;
}

function recalcSummary(form) {
  const taxableValue = fieldValue(form, 'taxable_value');
  const totalTax = fieldValue(form, 'cgst_amount') + fieldValue(form, 'sgst_amount') + fieldValue(form, 'igst_amount');
  const grandTotal = taxableValue + totalTax;
  const tds = fieldValue(form, 'tds_amount');
  const tcs = fieldValue(form, 'tcs_amount');
  const netReceivable = grandTotal - tds - tcs;

  const summary = { taxable_value: taxableValue, total_tax: totalTax, grand_total: grandTotal, tds_amount: tds, tcs_amount: tcs, net_receivable: netReceivable };

  Object.entries(summary).forEach(([key, value]) => {
    const el = form.parentElement?.querySelector(`[data-summary="${key}"]`) ?? document.querySelector(`[data-summary="${key}"]`);
    if (el) el.textContent = formatMoney(value);
  });
}

/**
 * Changing the taxable value or a GST rate re-derives that tax's own
 * amount field (amount = taxable_value * rate / 100) so the two stay
 * consistent by default. The amount field itself stays independently
 * editable afterward for a manual override — typing into it directly
 * only feeds recalcSummary(), it doesn't get silently overwritten
 * unless taxable_value or the rate changes again.
 */
function deriveGstAmounts(form) {
  const taxableValue = fieldValue(form, 'taxable_value');

  ['cgst', 'sgst', 'igst'].forEach((tax) => {
    const rateEl = form.querySelector(`[data-field="${tax}_rate"]`);
    const amountEl = form.querySelector(`[data-field="${tax}_amount"]`);
    if (!rateEl || !amountEl) return;

    const rate = parseFloat(rateEl.value) || 0;
    const amount = Math.round(((taxableValue * rate) / 100) * 100) / 100;
    amountEl.value = amount.toFixed(2);
  });
}

function populateFields(form, breakup) {
  FIELDS.forEach((name) => {
    const el = form.querySelector(`[data-field="${name}"]`);
    if (el && breakup[name] !== undefined) el.value = breakup[name];
  });
}

function toggleGstFields(form, isIntraState) {
  const intraFields = form.querySelector('[data-intra-state-fields]');
  const interFields = form.querySelector('[data-inter-state-fields]');
  const badge = form.querySelector('[data-gst-type-badge]');

  if (intraFields) intraFields.hidden = !isIntraState;
  if (interFields) interFields.hidden = isIntraState;
  if (badge) badge.textContent = isIntraState ? 'Intra-state (CGST + SGST)' : 'Inter-state (IGST)';
}

async function loadPreview(form) {
  const hotelId = form.querySelector('#hotel_id').value;
  const month = form.querySelector('#month').value;
  const billingEntityId = form.querySelector('#billing_entity_id').value;
  const statusEl = form.querySelector('[data-invoice-status]');
  const section = form.querySelector('[data-breakup-section]');
  const generateBtn = form.querySelector('[data-generate-btn]');

  if (!hotelId || !month || !billingEntityId) return;

  if (statusEl) statusEl.textContent = 'Pulling confirmed bookings…';

  let data;

  try {
    data = await api(`/commission-invoices/preview?hotel_id=${encodeURIComponent(hotelId)}&month=${encodeURIComponent(month)}&billing_entity_id=${encodeURIComponent(billingEntityId)}`);
  } catch {
    if (statusEl) statusEl.textContent = 'Could not load bookings for that selection.';
    return;
  }

  populateFields(form, data.breakup);
  toggleGstFields(form, data.breakup.is_intra_state);
  form.querySelector('[data-tds-rate-label]').textContent = `(${data.breakup.tds_rate}%)`;
  form.querySelector('[data-tcs-rate-label]').textContent = `(${data.breakup.tcs_rate}%)`;

  if (section) section.hidden = false;
  recalcSummary(form);

  if (data.breakup.total_bookings === 0) {
    if (statusEl) statusEl.textContent = `No confirmed bookings found for this hotel in ${month} — nothing to invoice.`;
    if (generateBtn) generateBtn.disabled = true;
  } else {
    if (statusEl) statusEl.textContent = `${data.breakup.total_bookings} confirmed booking(s) found for ${data.period_start} to ${data.period_end}.`;
    if (generateBtn) generateBtn.disabled = false;
  }

  isDirty = false;
}

function initUnsavedWarning(form) {
  form.addEventListener('input', () => {
    isDirty = true;
  });

  form.addEventListener('submit', () => {
    isSubmitting = true;
  });

  window.addEventListener('beforeunload', (event) => {
    if (!isDirty || isSubmitting) return;
    event.preventDefault();
    event.returnValue = '';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-invoice-form]');
  if (!form) return;

  form.querySelectorAll('[data-invoice-input]').forEach((el) => {
    el.addEventListener('change', () => loadPreview(form));
  });

  form.querySelectorAll('[data-field]').forEach((el) => {
    el.addEventListener('input', () => recalcSummary(form));
  });

  ['taxable_value', 'cgst_rate', 'sgst_rate', 'igst_rate'].forEach((name) => {
    form.querySelector(`[data-field="${name}"]`)?.addEventListener('input', () => {
      deriveGstAmounts(form);
      recalcSummary(form);
    });
  });

  initUnsavedWarning(form);
});
