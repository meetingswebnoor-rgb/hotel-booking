/**
 * voucher-print.js — wires the voucher page's Print button. Kept as
 * its own tiny external file (rather than an inline onclick=) so the
 * print layout never needs 'unsafe-inline' in its script-src.
 */

document.addEventListener('click', (event) => {
  if (event.target.closest('[data-print-trigger]')) {
    window.print();
  }
});
