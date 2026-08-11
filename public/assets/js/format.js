/**
 * format.js — shared display formatting, mirroring the money() PHP
 * helper's Indian digit grouping (1,00,000) so client-side previews
 * (dashboard KPIs, the booking form's live calculation summary) match
 * what the server would render.
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
