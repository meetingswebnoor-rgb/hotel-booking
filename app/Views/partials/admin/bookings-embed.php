<?php

/**
 * The Bookings tab's content — Module 6's bookings list, reused
 * as-is (same booking-list.js, same GET /bookings/data endpoint) but
 * scoped to one hotel. data-locked-hotel-id tells the script to force
 * hotel_id into every query and skip the Hotel filter (there isn't
 * one, since it isn't needed) and URL-state syncing.
 *
 * @var array<string, mixed> $hotel
 * @var array<int, array<string, mixed>> $otas
 * @var array<string, string> $statusOptions
 * @var array<string, string> $paymentStatusOptions
 * @var array<int, int> $perPageOptions
 * @var bool $canCreate
 * @var bool $canViewReports
 */
?>
<div data-locked-hotel-id="<?= e($hotel['id']) ?>">
  <div class="flex-between mb-6">
    <h3>Bookings for <?= e($hotel['name']) ?></h3>
    <?php if ($canCreate): ?>
      <a href="<?= route('bookings.create') ?>" class="btn btn-primary btn-sm">
        <?= icon('plus', 'icon icon-sm') ?>
        <span>New Booking</span>
      </a>
    <?php endif; ?>
  </div>

  <div class="card glass filter-bar mb-6">
    <div class="filter-bar__row">
      <div class="field">
        <label for="hub-filter-ota">OTA</label>
        <select class="select" id="hub-filter-ota" data-filter="ota_id">
          <option value="">All Sources</option>
          <?php foreach ($otas as $o): ?>
            <option value="<?= e($o['id']) ?>"><?= e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="hub-filter-status">Status</label>
        <select class="select" id="hub-filter-status" data-filter="status">
          <option value="">All Statuses</option>
          <?php foreach ($statusOptions as $value => $label): ?>
            <option value="<?= e($value) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="hub-filter-date-from">Check-in From</label>
        <input class="input" type="date" id="hub-filter-date-from" data-filter="date_from">
      </div>

      <div class="field">
        <label for="hub-filter-date-to">Check-in To</label>
        <input class="input" type="date" id="hub-filter-date-to" data-filter="date_to">
      </div>

      <div class="field filter-bar__search">
        <label for="hub-filter-q">Search</label>
        <input class="input" type="search" id="hub-filter-q" placeholder="Guest, booking ID, or mobile…" data-filter="q" autocomplete="off">
      </div>
    </div>

    <div class="filter-bar__actions">
      <button type="button" class="btn btn-ghost btn-sm" data-clear-filters>Clear filters</button>
    </div>
  </div>

  <div class="showcase-grid mb-6">
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

  <div class="card glass">
    <div data-bookings-skeleton><?= partial('skeleton', ['rows' => 5]) ?></div>

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
</div>
