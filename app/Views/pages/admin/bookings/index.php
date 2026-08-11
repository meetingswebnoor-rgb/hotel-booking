<?php

use App\Core\View;

/**
 * Skeleton-first shell: the filter bar and stats/table skeletons
 * render immediately, then public/assets/js/booking-list.js fetches
 * GET /bookings/data (filters + pagination as query params, synced to
 * the URL) and populates everything in place. Row click fetches
 * GET /bookings/{id}/detail into the slide-over drawer.
 *
 * @var bool $canCreate
 * @var bool $canViewReports
 * @var array<int, array<string, mixed>> $hotels
 * @var array<int, array<string, mixed>> $otas
 * @var array<string, string> $statusOptions
 * @var array<string, string> $paymentStatusOptions
 * @var array<int, int> $perPageOptions
 */

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'Bookings']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <h2>Bookings</h2>
  <?php if ($canCreate): ?>
    <a href="<?= route('bookings.create') ?>" class="btn btn-primary">
      <?= icon('plus', 'icon icon-sm') ?>
      <span>New Booking</span>
    </a>
  <?php endif; ?>
</div>

<div class="card glass filter-bar mb-6" data-animate>
  <div class="filter-bar__row">
    <div class="field">
      <label for="filter-hotel">Hotel</label>
      <select class="select" id="filter-hotel" data-filter="hotel_id">
        <option value="">All Hotels</option>
        <?php foreach ($hotels as $h): ?>
          <option value="<?= e($h['id']) ?>"><?= e($h['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="filter-ota">OTA</label>
      <select class="select" id="filter-ota" data-filter="ota_id">
        <option value="">All Sources</option>
        <?php foreach ($otas as $o): ?>
          <option value="<?= e($o['id']) ?>"><?= e($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="filter-status">Status</label>
      <select class="select" id="filter-status" data-filter="status">
        <option value="">All Statuses</option>
        <?php foreach ($statusOptions as $value => $label): ?>
          <option value="<?= e($value) ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="filter-date-from">Check-in From</label>
      <input class="input" type="date" id="filter-date-from" data-filter="date_from">
    </div>

    <div class="field">
      <label for="filter-date-to">Check-in To</label>
      <input class="input" type="date" id="filter-date-to" data-filter="date_to">
    </div>

    <div class="field filter-bar__search">
      <label for="filter-q">Search</label>
      <input class="input" type="search" id="filter-q" placeholder="Guest, booking ID, or mobile…" data-filter="q" autocomplete="off">
    </div>
  </div>

  <div class="filter-bar__actions">
    <button type="button" class="btn btn-ghost btn-sm" data-clear-filters>Clear filters</button>
  </div>
</div>

<div class="showcase-grid mb-6" data-animate>
  <div class="stat-card glass stat-card--compact">
    <div class="stat-card__value font-mono skeleton" data-stat="page_count">&nbsp;</div>
    <div class="stat-card__label">Bookings on this page</div>
  </div>
  <div class="stat-card glass stat-card--compact">
    <div class="stat-card__value font-mono skeleton" data-stat="page_revenue">&nbsp;</div>
    <div class="stat-card__label">Page revenue</div>
  </div>
  <div class="stat-card glass stat-card--compact">
    <div class="stat-card__value font-mono skeleton" data-stat="page_guests">&nbsp;</div>
    <div class="stat-card__label">Page guests</div>
  </div>
  <div class="stat-card glass stat-card--compact">
    <div class="stat-card__value font-mono skeleton" data-stat="total_matches">&nbsp;</div>
    <div class="stat-card__label">Total matching filters</div>
  </div>
</div>

<div class="card glass" data-animate>
  <div data-bookings-skeleton><?= partial('skeleton', ['rows' => 8]) ?></div>

  <div class="table-wrap" data-bookings-table hidden>
    <table class="table">
      <thead>
        <tr>
          <th>Booking</th>
          <th>Guest</th>
          <th>Hotel</th>
          <th>OTA</th>
          <th>Dates</th>
          <th>Nights</th>
          <th>Amount</th>
          <?php if ($canViewReports): ?>
            <th>Hotel Earning</th>
          <?php endif; ?>
          <th>Status</th>
          <th>Payment</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody data-bookings-body></tbody>
    </table>
  </div>

  <div data-bookings-empty hidden>
    <?= partial('empty-state', ['title' => 'No bookings match these filters', 'icon' => '🗓️']) ?>
  </div>

  <div class="pagination-bar" data-pagination-bar hidden>
    <div class="pagination-bar__info" data-pagination-info></div>
    <div class="pagination-bar__controls">
      <select class="select" data-per-page>
        <?php foreach ($perPageOptions as $n): ?>
          <option value="<?= $n ?>"><?= $n ?> / page</option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="btn btn-ghost btn-sm" data-prev-page>
        <?= icon('chevron-left', 'icon icon-sm') ?>
        <span>Prev</span>
      </button>
      <span class="pagination-bar__page" data-page-label></span>
      <button type="button" class="btn btn-ghost btn-sm" data-next-page>
        <span>Next</span>
      </button>
    </div>
  </div>
</div>

<div class="drawer-backdrop no-print" data-booking-drawer>
  <aside class="drawer glass">
    <div class="drawer__header">
      <h3 data-drawer-title>Booking</h3>
      <button type="button" class="modal__close" data-drawer-close aria-label="Close">
        <?= icon('x', 'icon') ?>
      </button>
    </div>
    <div class="drawer__body" data-drawer-body></div>
    <div class="drawer__footer">
      <a href="#" class="btn btn-ghost btn-sm" data-drawer-edit>Edit booking</a>
      <a href="#" class="btn btn-ghost btn-sm" data-drawer-voucher target="_blank">Voucher</a>
    </div>
  </aside>
</div>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/booking-list.js') ?>"></script>
<?php View::endSection(); ?>
