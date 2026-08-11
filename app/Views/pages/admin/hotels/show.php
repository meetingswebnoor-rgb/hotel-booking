<?php

use App\Core\View;

/**
 * The hotel management hub: one server-rendered page, tabs switched
 * client-side (public/assets/js/ui.js initTabs()). Every tab a role
 * can't reach is omitted entirely — not just visually hidden — same
 * gating convention as the dashboard's reports-only figures.
 *
 * @var array<string, mixed> $hotel
 * @var array<int, array<string, mixed>> $rooms
 * @var array<int, array<string, mixed>> $ratePlans
 * @var array<int, array<string, mixed>> $guests
 * @var array<int, array<string, mixed>> $invoices
 * @var array<int, array<string, mixed>> $staff
 * @var int $bookingCount
 * @var bool $canEdit
 * @var bool $canDelete
 * @var bool $canViewRooms
 * @var bool $canManageRooms
 * @var bool $canDeleteRooms
 * @var bool $canViewRatePlans
 * @var bool $canManageRatePlans
 * @var bool $canDeleteRatePlans
 * @var bool $canViewBookings
 * @var bool $canCreateBooking
 * @var bool $canViewInvoices
 * @var bool $canViewStaff
 * @var bool $canViewReports
 * @var array<int, string> $settlementTypes
 * @var array<int, string> $gstTypes
 * @var array<string, string> $statusOptions
 * @var array<int, string> $roomTypes
 * @var array<int, string> $roomStatuses
 * @var array<int, string> $seasons
 * @var array<int, array<string, mixed>> $otas
 * @var array<string, string> $bookingStatusOptions
 * @var array<string, string> $paymentStatusOptions
 * @var array<int, int> $perPageOptions
 */

$tabs = [
    ['key' => 'details', 'label' => 'Details', 'visible' => true],
    ['key' => 'rooms', 'label' => 'Rooms', 'visible' => $canViewRooms],
    ['key' => 'rate-plans', 'label' => 'Rate Plans', 'visible' => $canViewRatePlans],
    ['key' => 'inventory', 'label' => 'Inventory', 'visible' => true],
    ['key' => 'bookings', 'label' => 'Bookings', 'visible' => $canViewBookings],
    ['key' => 'guests', 'label' => 'Guests', 'visible' => $canViewBookings],
    ['key' => 'invoices', 'label' => 'Invoices', 'visible' => $canViewInvoices],
    ['key' => 'staff', 'label' => 'Staff', 'visible' => $canViewStaff],
    ['key' => 'reports', 'label' => 'Reports', 'visible' => $canViewReports],
    ['key' => 'settings', 'label' => 'Settings', 'visible' => true],
];
$tabs = array_values(array_filter($tabs, static fn (array $t): bool => $t['visible']));

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', [
    'items' => [
        ['label' => 'Home', 'href' => route('home')],
        ['label' => 'Hotels', 'href' => route('hotels.index')],
        ['label' => $hotel['name']],
    ],
]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <div class="flex items-center gap-3">
    <h2><?= e($hotel['name']) ?></h2>
    <span class="badge badge--<?= $hotel['status'] === 'active' ? 'success' : ($hotel['status'] === 'inactive' ? 'danger' : 'warning') ?>">
      <?= e(ucwords(str_replace('_', ' ', $hotel['status']))) ?>
    </span>
  </div>
  <?php if ($canDelete): ?>
    <form
      method="POST"
      action="<?= route('hotels.destroy', ['id' => $hotel['id']]) ?>"
      data-confirm-submit
      data-confirm-title="Delete this hotel?"
      data-confirm-message="This soft-deletes &quot;<?= e($hotel['name']) ?>&quot; along with its rooms, bookings, guest invoices, staff assignments, and rate plans. This can be reversed from Trash."
      data-confirm-label="Delete Hotel"
    >
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger btn-sm">
        <?= icon('trash', 'icon icon-sm') ?>
        <span>Delete Hotel</span>
      </button>
    </form>
  <?php endif; ?>
</div>

<div data-tabs-group data-animate>
  <div class="tabs mb-6">
    <?php foreach ($tabs as $i => $tab): ?>
      <button type="button" class="tab <?= $i === 0 ? 'active' : '' ?>" data-tab-target="<?= e($tab['key']) ?>"><?= e($tab['label']) ?></button>
    <?php endforeach; ?>
  </div>

  <div data-tab-panel="details">
    <form method="POST" action="<?= route('hotels.update', ['id' => $hotel['id']]) ?>" enctype="multipart/form-data" class="form-narrow" data-hotel-form>
      <?= csrf_field() ?>
      <?php if (!$canEdit): ?><fieldset disabled style="border: 0; padding: 0; margin: 0;"><?php endif; ?>
      <?= partial('admin/hotel-form-fields', [
          'hotel' => $hotel,
          'settlementTypes' => $settlementTypes,
          'gstTypes' => $gstTypes,
          'statusOptions' => $statusOptions,
      ]) ?>
      <?php if (!$canEdit): ?></fieldset><?php endif; ?>
      <?php if ($canEdit): ?>
        <div class="flex gap-3"><button type="submit" class="btn btn-primary btn-lg">Save Changes</button></div>
      <?php endif; ?>
    </form>
  </div>

  <?php if ($canViewRooms): ?>
    <div data-tab-panel="rooms" hidden>
      <div class="flex-between mb-4">
        <h3>Rooms</h3>
        <?php if ($canManageRooms): ?>
          <button type="button" class="btn btn-primary btn-sm" data-modal-open="room-add">
            <?= icon('plus', 'icon icon-sm') ?>
            <span>Add Room</span>
          </button>
        <?php endif; ?>
      </div>

      <?php if ($rooms === []): ?>
        <?= partial('empty-state', ['title' => 'No rooms yet', 'description' => 'Add this hotel\'s rooms to start building bookings.', 'icon' => '🛏️']) ?>
      <?php else: ?>
        <div class="card glass">
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr><th>Room #</th><th>Type</th><th>Pax</th><th>Max Adults</th><th>Max Children</th><th>Base Price</th><th>Status</th><?php if ($canManageRooms || $canDeleteRooms): ?><th>Actions</th><?php endif; ?></tr>
              </thead>
              <tbody>
                <?php foreach ($rooms as $room): ?>
                  <tr>
                    <td class="font-mono"><?= e($room['room_number']) ?></td>
                    <td><?= e($room['room_type']) ?></td>
                    <td><?= (int) $room['pax'] ?></td>
                    <td><?= (int) $room['max_adults'] ?></td>
                    <td><?= (int) $room['max_children'] ?></td>
                    <td><?= money($room['base_price']) ?></td>
                    <td><span class="badge badge--<?= $room['status'] === 'Available' ? 'success' : ($room['status'] === 'Maintenance' ? 'warning' : 'neutral') ?>"><?= e($room['status']) ?></span></td>
                    <?php if ($canManageRooms || $canDeleteRooms): ?>
                      <td class="booking-row__actions">
                        <?php if ($canManageRooms): ?>
                          <button type="button" class="link-btn" data-modal-open="room-edit-<?= e($room['id']) ?>"><?= icon('edit-2', 'icon icon-sm') ?> Edit</button>
                        <?php endif; ?>
                        <?php if ($canDeleteRooms): ?>
                          <form method="POST" action="<?= route('hotels.rooms.destroy', ['id' => $hotel['id'], 'roomId' => $room['id']]) ?>" data-confirm-submit data-confirm-title="Remove this room?" data-confirm-message="Room <?= e($room['room_number']) ?> will be soft-deleted.">
                            <?= csrf_field() ?>
                            <button type="submit" class="link-btn link-btn--danger"><?= icon('trash', 'icon icon-sm') ?> Remove</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($canManageRooms): ?>
        <?= partial('admin/room-modal', [
            'modalId' => 'room-add',
            'formAction' => route('hotels.rooms.store', ['id' => $hotel['id']]),
            'room' => null,
            'roomTypes' => $roomTypes,
            'roomStatuses' => $roomStatuses,
            'heading' => 'Add Room',
        ]) ?>
        <?php foreach ($rooms as $room): ?>
          <?= partial('admin/room-modal', [
              'modalId' => 'room-edit-' . $room['id'],
              'formAction' => route('hotels.rooms.update', ['id' => $hotel['id'], 'roomId' => $room['id']]),
              'room' => $room,
              'roomTypes' => $roomTypes,
              'roomStatuses' => $roomStatuses,
              'heading' => 'Edit Room ' . $room['room_number'],
          ]) ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($canViewRatePlans): ?>
    <div data-tab-panel="rate-plans" hidden>
      <div class="flex-between mb-4">
        <h3>Rate Plans</h3>
        <?php if ($canManageRatePlans): ?>
          <button type="button" class="btn btn-primary btn-sm" data-modal-open="rateplan-add">
            <?= icon('plus', 'icon icon-sm') ?>
            <span>Add Rate Plan</span>
          </button>
        <?php endif; ?>
      </div>

      <?php if ($ratePlans === []): ?>
        <?= partial('empty-state', ['title' => 'No rate plans yet', 'description' => 'Set seasonal pricing by room type.', 'icon' => '💰']) ?>
      <?php else: ?>
        <div class="card glass">
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr><th>Plan</th><th>Room Type</th><th>Occupancy</th><th>Season</th><th>Base Price</th><?php if ($canManageRatePlans || $canDeleteRatePlans): ?><th>Actions</th><?php endif; ?></tr>
              </thead>
              <tbody>
                <?php foreach ($ratePlans as $plan): ?>
                  <tr>
                    <td><?= e($plan['plan_name']) ?></td>
                    <td><?= e($plan['room_type']) ?></td>
                    <td><?= e($plan['occupancy_type'] ?: '—') ?></td>
                    <td><span class="badge badge--<?= $plan['season'] === 'Peak' ? 'warning' : 'info' ?>"><?= e($plan['season']) ?></span></td>
                    <td><?= money($plan['base_price']) ?></td>
                    <?php if ($canManageRatePlans || $canDeleteRatePlans): ?>
                      <td class="booking-row__actions">
                        <?php if ($canManageRatePlans): ?>
                          <button type="button" class="link-btn" data-modal-open="rateplan-edit-<?= e($plan['id']) ?>"><?= icon('edit-2', 'icon icon-sm') ?> Edit</button>
                        <?php endif; ?>
                        <?php if ($canDeleteRatePlans): ?>
                          <form method="POST" action="<?= route('hotels.rate-plans.destroy', ['id' => $hotel['id'], 'planId' => $plan['id']]) ?>" data-confirm-submit data-confirm-title="Remove this rate plan?" data-confirm-message="<?= e($plan['plan_name']) ?> will be soft-deleted.">
                            <?= csrf_field() ?>
                            <button type="submit" class="link-btn link-btn--danger"><?= icon('trash', 'icon icon-sm') ?> Remove</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($canManageRatePlans): ?>
        <?= partial('admin/rate-plan-modal', [
            'modalId' => 'rateplan-add',
            'formAction' => route('hotels.rate-plans.store', ['id' => $hotel['id']]),
            'plan' => null,
            'roomTypes' => $roomTypes,
            'seasons' => $seasons,
            'heading' => 'Add Rate Plan',
        ]) ?>
        <?php foreach ($ratePlans as $plan): ?>
          <?= partial('admin/rate-plan-modal', [
              'modalId' => 'rateplan-edit-' . $plan['id'],
              'formAction' => route('hotels.rate-plans.update', ['id' => $hotel['id'], 'planId' => $plan['id']]),
              'plan' => $plan,
              'roomTypes' => $roomTypes,
              'seasons' => $seasons,
              'heading' => 'Edit ' . $plan['plan_name'],
          ]) ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div data-tab-panel="inventory" hidden>
    <?= partial('admin/coming-soon', [
        'title' => 'Inventory Calendar',
        'description' => 'A day-by-day availability and rate calendar for every room type is on the way.',
        'calendar' => true,
    ]) ?>
  </div>

  <?php if ($canViewBookings): ?>
    <div data-tab-panel="bookings" hidden>
      <?= partial('admin/bookings-embed', [
          'hotel' => $hotel,
          'otas' => $otas,
          'statusOptions' => $bookingStatusOptions,
          'paymentStatusOptions' => $paymentStatusOptions,
          'perPageOptions' => $perPageOptions,
          'canCreate' => $canCreateBooking,
          'canViewReports' => $canViewReports,
      ]) ?>
    </div>
  <?php endif; ?>

  <?php if ($canViewBookings): ?>
    <div data-tab-panel="guests" hidden>
      <h3 class="mb-4">Guests</h3>
      <?php if ($guests === []): ?>
        <?= partial('empty-state', ['title' => 'No guests yet', 'description' => 'Guests appear here once this hotel has bookings.', 'icon' => '🧳']) ?>
      <?php else: ?>
        <div class="card glass">
          <div class="table-wrap">
            <table class="table">
              <thead><tr><th>Name</th><th>Mobile</th><th>Email</th><th>Company</th><th>Bookings</th><th>Last Stay</th></tr></thead>
              <tbody>
                <?php foreach ($guests as $g): ?>
                  <tr>
                    <td><?= e($g['guest_name']) ?></td>
                    <td class="font-mono"><?= e($g['guest_mobile']) ?></td>
                    <td><?= e($g['guest_email'] ?: '—') ?></td>
                    <td class="text-low">—</td>
                    <td><?= (int) $g['booking_count'] ?></td>
                    <td><?= e($g['last_stay']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <p class="text-low mt-3" style="font-size: 0.8125rem;">"Company" isn't captured on the booking form yet, so it's shown blank rather than guessed.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($canViewInvoices): ?>
    <div data-tab-panel="invoices" hidden>
      <h3 class="mb-4">Invoices</h3>
      <?php if ($invoices === []): ?>
        <?= partial('empty-state', ['title' => 'No invoices yet', 'description' => 'Invoicing is generated from settlements — nothing has been issued for this hotel yet.', 'icon' => '🧾']) ?>
      <?php else: ?>
        <div class="card glass">
          <div class="table-wrap">
            <table class="table">
              <thead><tr><th>Invoice #</th><th>Type</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($invoices as $inv): ?>
                  <tr>
                    <td class="font-mono"><?= e($inv['invoice_number']) ?></td>
                    <td><?= e(ucfirst($inv['invoice_type'])) ?></td>
                    <td><?= e($inv['invoice_date']) ?></td>
                    <td><?= money($inv['grand_total']) ?></td>
                    <td><span class="badge badge--<?= $inv['status'] === 'paid' ? 'success' : ($inv['status'] === 'cancelled' ? 'danger' : 'neutral') ?>"><?= e(ucfirst($inv['status'])) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($canViewStaff): ?>
    <div data-tab-panel="staff" hidden>
      <h3 class="mb-4">Staff</h3>
      <?php if ($staff === []): ?>
        <?= partial('empty-state', ['title' => 'No staff assigned', 'description' => 'Assign users to this hotel from the Users module.', 'icon' => '👥']) ?>
      <?php else: ?>
        <div class="card glass">
          <div class="table-wrap">
            <table class="table">
              <thead><tr><th>Name</th><th>Email</th><th>Designation</th><th>Role</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($staff as $s): ?>
                  <tr>
                    <td><?= e($s['full_name']) ?></td>
                    <td><?= e($s['email']) ?></td>
                    <td><?= e($s['designation'] ?: '—') ?></td>
                    <td><span class="badge badge--info"><?= e(ucwords(str_replace('_', ' ', $s['role_name']))) ?></span></td>
                    <td><?php if ((int) $s['is_primary'] === 1): ?><span class="badge badge--success">Primary</span><?php endif; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($canViewReports): ?>
    <div data-tab-panel="reports" hidden>
      <h3 class="mb-4">Reports</h3>
      <div class="showcase-grid">
        <div class="stat-card glass">
          <div class="stat-card__value font-mono"><?= $bookingCount ?></div>
          <div class="stat-card__label">Total Bookings</div>
        </div>
        <div class="stat-card glass">
          <div class="stat-card__value font-mono"><?= count($rooms) ?></div>
          <div class="stat-card__label">Rooms</div>
        </div>
        <div class="stat-card glass">
          <div class="stat-card__value font-mono"><?= count($ratePlans) ?></div>
          <div class="stat-card__label">Rate Plans</div>
        </div>
        <div class="stat-card glass">
          <div class="stat-card__value font-mono"><?= count($staff) ?></div>
          <div class="stat-card__label">Staff Assigned</div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div data-tab-panel="settings" hidden>
    <?= partial('admin/coming-soon', [
        'title' => 'Hotel Settings',
        'description' => 'Notification preferences, booking-window rules, and per-hotel invoice templates are coming here.',
    ]) ?>
  </div>
</div>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/hotel-hub.js') ?>"></script>
<?php if ($canViewBookings): ?>
<script type="module" src="<?= asset('js/booking-list.js') ?>"></script>
<?php endif; ?>
<?php View::endSection(); ?>
