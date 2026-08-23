/**
 * service-invoice-form.js — one-off service invoice generator. Unlike
 * the commission-invoice form, there's nothing to pull from the server
 * (no bookings involved): the hotel/billing-entity state codes are
 * already embedded as data-state-code on their <option>s at render
 * time, so the whole GST split computes live in the browser the
 * moment hotel + billing entity + amount are all filled in. The server
 * still re-derives total_tax/grand_total from whatever was actually
 * submitted on save — same "trust the submitted breakdown, not a
 * client-computed total" rule as the commission-invoice form.
 */

import { formatIndianCurrency } from './format.js';

function formatMoney(value) {
  return `₹${formatIndianCurrency(value, 2)}`;
}

function fieldValue(form, name) {
  return parseFloat(form.querySelector(`[data-field="${name}"]`)?.value) || 0;
}

/** Mirrors app/Helpers/helpers.php's gst() exactly. */
function computeGst(amount, ratePercent, isIntraState) {
  const totalTax = Math.round(amount * ratePercent) / 100;

  if (isIntraState) {
    return { cgst: Math.round((totalTax / 2) * 100) / 100, sgst: Math.round((totalTax / 2) * 100) / 100, igst: 0, totalTax };
  }

  return { cgst: 0, sgst: 0, igst: totalTax, totalTax };
}

function toggleGstFields(form, isIntraState) {
  const intraFields = form.querySelector('[data-intra-state-fields]');
  const interFields = form.querySelector('[data-inter-state-fields]');
  const badge = form.querySelector('[data-gst-type-badge]');

  if (intraFields) intraFields.hidden = !isIntraState;
  if (interFields) interFields.hidden = isIntraState;
  if (badge) badge.textContent = isIntraState ? 'Intra-state (CGST + SGST)' : 'Inter-state (IGST)';
}

function recalcSummary(form) {
  const taxableValue = parseFloat(form.querySelector('#taxable_value')?.value) || 0;
  const totalTax = fieldValue(form, 'cgst_amount') + fieldValue(form, 'sgst_amount') + fieldValue(form, 'igst_amount');
  const grandTotal = taxableValue + totalTax;

  const summary = { taxable_value: taxableValue, total_tax: totalTax, grand_total: grandTotal };

  Object.entries(summary).forEach(([key, value]) => {
    const el = document.querySelector(`[data-summary="${key}"]`);
    if (el) el.textContent = formatMoney(value);
  });
}

function deriveGstAmounts(form) {
  const taxableValue = parseFloat(form.querySelector('#taxable_value')?.value) || 0;

  ['cgst', 'sgst', 'igst'].forEach((tax) => {
    const rateEl = form.querySelector(`[data-field="${tax}_rate"]`);
    const amountEl = form.querySelector(`[data-field="${tax}_amount"]`);
    if (!rateEl || !amountEl) return;

    const rate = parseFloat(rateEl.value) || 0;
    amountEl.value = (Math.round(((taxableValue * rate) / 100) * 100) / 100).toFixed(2);
  });
}

function recompute(form) {
  const hotelSelect = form.querySelector('#hotel_id');
  const billingEntitySelect = form.querySelector('#billing_entity_id');
  const amount = parseFloat(form.querySelector('#taxable_value')?.value) || 0;
  const gstRate = parseFloat(form.querySelector('#gst_rate')?.value) || 0;
  const section = form.querySelector('[data-breakup-section]');
  const generateBtn = form.querySelector('[data-generate-btn]');
  const placeOfSupplyEl = form.querySelector('[data-place-of-supply]');

  const hotelOption = hotelSelect?.options[hotelSelect.selectedIndex];
  const billingEntityOption = billingEntitySelect?.options[billingEntitySelect.selectedIndex];
  const hotelStateCode = hotelOption?.dataset.stateCode || '';
  const billingEntityStateCode = billingEntityOption?.dataset.stateCode || '';

  if (!hotelSelect.value || !billingEntitySelect.value || amount <= 0) {
    if (section) section.hidden = true;
    if (generateBtn) generateBtn.disabled = true;
    return;
  }

  const isIntraState = hotelStateCode !== '' && hotelStateCode === billingEntityStateCode;
  const { cgst, sgst, igst } = computeGst(amount, gstRate, isIntraState);

  const cgstRateEl = form.querySelector('[data-field="cgst_rate"]');
  const sgstRateEl = form.querySelector('[data-field="sgst_rate"]');
  const igstRateEl = form.querySelector('[data-field="igst_rate"]');
  if (cgstRateEl) cgstRateEl.value = isIntraState ? gstRate / 2 : 0;
  if (sgstRateEl) sgstRateEl.value = isIntraState ? gstRate / 2 : 0;
  if (igstRateEl) igstRateEl.value = isIntraState ? 0 : gstRate;

  form.querySelector('[data-field="cgst_amount"]').value = cgst.toFixed(2);
  form.querySelector('[data-field="sgst_amount"]').value = sgst.toFixed(2);
  form.querySelector('[data-field="igst_amount"]').value = igst.toFixed(2);

  toggleGstFields(form, isIntraState);

  if (placeOfSupplyEl) {
    placeOfSupplyEl.textContent = hotelStateCode
      ? `Place of Supply derived from the hotel's GSTIN state code (${hotelStateCode}).`
      : `This hotel has no GSTIN on file — place of supply could not be derived; defaults to inter-state (IGST).`;
  }

  if (section) section.hidden = false;
  if (generateBtn) generateBtn.disabled = false;

  recalcSummary(form);
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-service-invoice-form]');
  if (!form) return;

  form.querySelectorAll('[data-field-input]').forEach((el) => {
    el.addEventListener('input', () => recompute(form));
    el.addEventListener('change', () => recompute(form));
  });

  form.querySelector('#taxable_value')?.addEventListener('input', () => {
    deriveGstAmounts(form);
    recalcSummary(form);
  });

  form.querySelectorAll('[data-field]').forEach((el) => {
    el.addEventListener('input', () => recalcSummary(form));
  });
});
