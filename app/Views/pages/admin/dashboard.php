<?php

use App\Core\RoleLevel;
use App\Core\View;

/**
 * Skeleton-first shell: every number/chart/table renders as a
 * skeleton immediately, then public/assets/js/dashboard.js fetches
 * GET /dashboard/data and populates everything in place (count-up,
 * chart instantiation, table rows). Financial KPIs/charts are only
 * rendered at all when $canViewReports is true — see
 * DashboardController for the matching server-side gate. Cross-hotel
 * comparison widgets (Total Hotels, Top 5 Hotels, the breakdown table)
 * are additionally gated on $isMultiHotel — meaningless noise for
 * anyone (any role) whose access resolves to a single hotel; this is
 * what actually differentiates a hotel_manager's dashboard from an
 * admin's/super_admin's, since both of those roles share the same
 * can('reports','view') grant and only differ in hotel scope.
 *
 * @var array<string, mixed>|null $user
 * @var string|null $roleName
 * @var int $roleLevel
 * @var bool $canViewReports
 * @var bool $isMultiHotel
 */

$roleLabel = $roleName !== null ? ucwords(str_replace('_', ' ', $roleName)) : '';

$kpiDefs = [
    ['key' => 'total_bookings', 'label' => 'Total Bookings', 'icon' => 'calendar', 'restricted' => false],
    ['key' => 'total_revenue', 'label' => 'Total Revenue', 'icon' => 'bar-chart', 'restricted' => !$canViewReports, 'prefix' => '₹'],
    ['key' => 'hotel_earnings', 'label' => 'Hotel Earnings', 'icon' => 'trending-up', 'restricted' => !$canViewReports, 'prefix' => '₹'],
    ['key' => 'commissions_taxes', 'label' => 'Commissions & Taxes', 'icon' => 'file-text', 'restricted' => !$canViewReports, 'prefix' => '₹'],
    ['key' => 'total_guests', 'label' => 'Total Guests', 'icon' => 'users', 'restricted' => false],
    ['key' => 'avg_guests_per_booking', 'label' => 'Avg Guests / Booking', 'icon' => 'user', 'restricted' => false, 'decimals' => 1],
    ['key' => 'ota_bookings', 'label' => 'OTA Bookings', 'icon' => 'share', 'restricted' => false],
    ['key' => 'total_hotels', 'label' => 'Total Hotels', 'icon' => 'home', 'restricted' => false],
];

if (!$isMultiHotel) {
    $kpiDefs = array_values(array_filter($kpiDefs, static fn (array $k): bool => $k['key'] !== 'total_hotels'));
}

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'Dashboard']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-2" data-animate>
  <div>
    <h2>Welcome, <?= e((string) ($user['full_name'] ?? 'there')) ?></h2>
    <p class="text-med mt-2">
      Role: <span class="badge badge--info"><?= e($roleLabel) ?></span>
      <span class="text-low">· Level <?= (int) $roleLevel ?></span>
      <span class="text-low" data-period-label></span>
    </p>
  </div>

  <?php if (can('bookings', 'create')): ?>
    <a href="<?= route('bookings.create') ?>" class="btn btn-primary">+ New Booking</a>
  <?php endif; ?>
</div>

<?php if (role_at_least(RoleLevel::SUPER_ADMIN)): ?>
  <div class="card glass mt-6" data-animate>
    <h3 class="mb-4">Quick Actions</h3>
    <div class="showcase-row">
      <a href="<?= route('hotels.index') ?>" class="btn btn-ghost btn-sm"><?= icon('home', 'icon icon-sm') ?><span>Manage Hotels</span></a>
      <a href="#" class="btn btn-ghost btn-sm" aria-disabled="true"><?= icon('users', 'icon icon-sm') ?><span>Manage Users</span></a>
      <a href="#" class="btn btn-ghost btn-sm" aria-disabled="true"><?= icon('trash', 'icon icon-sm') ?><span>Trash</span></a>
      <a href="#" class="btn btn-ghost btn-sm" aria-disabled="true"><?= icon('sliders', 'icon icon-sm') ?><span>Settings</span></a>
    </div>
  </div>
<?php endif; ?>

<div class="alert alert--info mt-4" data-drilldown-banner hidden>
  <span>Showing data for <strong data-drilldown-hotel-name></strong> only.</span>
  <button type="button" class="btn btn-ghost btn-sm" data-clear-drilldown>Clear</button>
</div>

<div data-dashboard-empty hidden class="mt-8">
  <?= partial('empty-state', [
      'title' => 'No bookings yet',
      'description' => 'Once bookings start coming in — online, via an OTA, or walk-in — your analytics will appear here.',
      'icon' => '📊',
  ]) ?>
</div>

<div data-dashboard-content data-can-view-reports="<?= $canViewReports ? 'true' : 'false' ?>">
  <div class="showcase-grid mt-6" data-animate>
    <?php foreach ($kpiDefs as $kpi): ?>
      <?php if ($kpi['restricted']): ?>
        <div class="stat-card glass stat-card--restricted">
          <div class="stat-card__icon"><?= icon($kpi['icon']) ?></div>
          <div class="stat-card__value stat-card__value--restricted">Restricted</div>
          <div class="stat-card__label"><?= e($kpi['label']) ?></div>
        </div>
      <?php else: ?>
        <div class="stat-card glass" data-kpi="<?= e($kpi['key']) ?>">
          <div class="stat-card__icon"><?= icon($kpi['icon']) ?></div>
          <div
            class="stat-card__value font-mono skeleton"
            data-kpi-value
            data-kpi-prefix="<?= e($kpi['prefix'] ?? '') ?>"
            data-kpi-decimals="<?= (int) ($kpi['decimals'] ?? 0) ?>"
          >&nbsp;</div>
          <div class="stat-card__label"><?= e($kpi['label']) ?></div>
          <span class="stat-card__trend" data-kpi-trend hidden></span>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div class="card glass mt-8" data-animate>
    <h3 class="mb-1">Today's Operations</h3>
    <p class="text-low mb-4" style="font-size: 0.8125rem;">Check-ins and check-outs due today</p>

    <div data-today-skeleton><?= partial('skeleton', ['rows' => 3]) ?></div>

    <div class="today-ops-grid" data-today-content hidden>
      <div>
        <h4 class="today-ops-heading">Check-ins <span class="badge badge--info" data-today-checkins-count>0</span></h4>
        <ul class="today-ops-list" data-today-checkins-list></ul>
        <div data-today-checkins-empty hidden><?= partial('empty-state', ['title' => 'No check-ins today', 'icon' => '🛎️']) ?></div>
      </div>
      <div>
        <h4 class="today-ops-heading">Check-outs <span class="badge badge--warning" data-today-checkouts-count>0</span></h4>
        <ul class="today-ops-list" data-today-checkouts-list></ul>
        <div data-today-checkouts-empty hidden><?= partial('empty-state', ['title' => 'No check-outs today', 'icon' => '🧳']) ?></div>
      </div>
    </div>
  </div>

  <div class="card glass chart-card chart-card--wide mt-8" data-animate>
    <h3 class="mb-4">Monthly Booking Trend</h3>
    <?= partial('admin/chart-body', ['key' => 'monthly_trend']) ?>
  </div>

  <div class="charts-grid mt-6" data-animate>
    <?php if ($canViewReports): ?>
      <div class="card glass chart-card">
        <h3 class="mb-4">Revenue by OTA Source</h3>
        <?= partial('admin/chart-body', ['key' => 'revenue_by_ota']) ?>
      </div>
    <?php else: ?>
      <?= partial('admin/chart-restricted', ['title' => 'Revenue by OTA Source']) ?>
    <?php endif; ?>

    <div class="card glass chart-card">
      <h3 class="mb-4">Room Type Distribution</h3>
      <?= partial('admin/chart-body', ['key' => 'room_type_distribution']) ?>
    </div>

    <?php if ($canViewReports && $isMultiHotel): ?>
      <div class="card glass chart-card">
        <h3 class="mb-4">Top 5 Hotels by Earnings</h3>
        <?= partial('admin/chart-body', ['key' => 'top_hotels']) ?>
      </div>
    <?php elseif (!$canViewReports): ?>
      <?= partial('admin/chart-restricted', ['title' => 'Top 5 Hotels by Earnings']) ?>
    <?php endif; ?>

    <div class="card glass chart-card">
      <h3 class="mb-4">OTA vs Direct</h3>
      <?= partial('admin/chart-body', ['key' => 'ota_vs_direct']) ?>
    </div>
  </div>

  <?php if ($canViewReports && $isMultiHotel): ?>
    <div class="card glass mt-8" data-animate>
      <h3 class="mb-1">Hotel Breakdown</h3>
      <p class="text-low mb-4" style="font-size: 0.8125rem;">Last 6 months · click a row to drill in</p>

      <div data-breakdown-skeleton><?= partial('skeleton', ['rows' => 3]) ?></div>

      <div class="table-wrap" data-breakdown-table hidden>
        <table class="table">
          <thead>
            <tr>
              <th>Hotel</th>
              <th>Bookings</th>
              <th>Revenue</th>
              <th>Earnings</th>
              <th>Commission + Tax</th>
            </tr>
          </thead>
          <tbody data-breakdown-body></tbody>
        </table>
      </div>

      <div data-breakdown-empty hidden>
        <?= partial('empty-state', ['title' => 'No hotel data yet', 'icon' => '🏨']) ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="card glass mt-8" data-animate>
    <h3 class="mb-4">Recent Bookings</h3>

    <div data-recent-skeleton><?= partial('skeleton', ['rows' => 6]) ?></div>

    <div class="table-wrap" data-recent-table hidden>
      <table class="table">
        <thead>
          <tr>
            <th>Booking</th>
            <th>Guest</th>
            <th>Hotel</th>
            <th>Check-in</th>
            <th>Status</th>
            <?php if ($canViewReports): ?>
              <th>Amount</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody data-recent-body></tbody>
      </table>
    </div>

    <div data-recent-empty hidden>
      <?= partial('empty-state', ['title' => 'No bookings yet', 'icon' => '🗓️']) ?>
    </div>
  </div>
</div>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/dashboard.js') ?>"></script>
<?php View::endSection(); ?>
