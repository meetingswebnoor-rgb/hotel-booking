/**
 * format.js — shared display formatting: currency grouping, booking
 * status pills, and OTA badge colors. Used by dashboard.js and
 * booking-list.js so the same booking renders identically wherever
 * it shows up.
 */

export function formatIndianCurrency(value, decimals = 0) {
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

const PAYMENT_STATUS_BADGE = {
  pending: 'badge--warning',
  paid: 'badge--success',
  hold: 'badge--info',
  disputed: 'badge--danger',
};

export function statusBadgeClass(status) {
  return STATUS_BADGE[status] ?? 'badge--neutral';
}

export function paymentStatusBadgeClass(status) {
  return PAYMENT_STATUS_BADGE[status] ?? 'badge--neutral';
}

export function statusLabel(status) {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

/**
 * No real OTA brand logos are bundled (no image pipeline, and reusing
 * actual brand marks without license is best avoided) — a deterministic
 * colored initial stands in as a "logo" instead. Hex values mirror the
 * palette tokens in design-system.css.
 */
const BADGE_PALETTE = ['#6366F1', '#06B6D4', '#F59E0B', '#10B981', '#EF4444', '#8B5CF6', '#A855F7'];

export function otaBadgeColor(name) {
  let hash = 0;

  for (let i = 0; i < name.length; i++) {
    hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
  }

  return BADGE_PALETTE[hash % BADGE_PALETTE.length];
}
