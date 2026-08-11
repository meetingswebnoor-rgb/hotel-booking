<?php
use App\Core\App;
use App\Core\Auth;
use App\Core\Database;

/**
 * @var string $pageTitle
 * @var array<string, mixed>|null $user
 */
$pageTitle ??= 'Dashboard';
$user ??= Auth::user();
$initial = $user !== null ? strtoupper(mb_substr((string) $user['full_name'], 0, 1)) : '?';
$roleLabel = Auth::roleName() !== null ? ucwords(str_replace('_', ' ', (string) Auth::roleName())) : null;

// Hotel filter options: the user's FULL allowed set, independent of
// whatever's currently selected (that would just show one option).
$hasGlobalAccess = Auth::hasGlobalHotelAccess();
$filterHotels = [];

if (Auth::check()) {
    $allowedHotelIds = $hasGlobalAccess ? null : Auth::hotelIds();

    if ($allowedHotelIds === null) {
        $filterHotels = Database::all('hotels', ['is_deleted' => 0], 'id, name', 'name');
    } elseif ($allowedHotelIds !== []) {
        $placeholders = implode(', ', array_fill(0, count($allowedHotelIds), '?'));
        $filterHotels = Database::query(
            "SELECT id, name FROM hotels WHERE is_deleted = 0 AND id IN ({$placeholders}) ORDER BY name",
            $allowedHotelIds
        )->fetchAll();
    }
}

$selectedHotelId = App::request()?->scope('selected_hotel_id');
$selectedHotelName = null;

foreach ($filterHotels as $hotel) {
    if ($hotel['id'] === $selectedHotelId) {
        $selectedHotelName = $hotel['name'];
        break;
    }
}

// No explicit selection: global-access users default to "All Hotels";
// scoped users default to their one hotel's name, or "My Hotels" if
// they have several (never "All Hotels" — they don't have that option).
if ($selectedHotelName === null) {
    if ($hasGlobalAccess) {
        $selectedHotelName = 'All Hotels';
    } elseif (count($filterHotels) === 1) {
        $selectedHotelName = $filterHotels[0]['name'];
    } elseif ($filterHotels === []) {
        $selectedHotelName = 'No hotels';
    } else {
        $selectedHotelName = 'My Hotels';
    }
}
?>
<header class="topbar">
  <div class="flex-center gap-4">
    <button type="button" class="topbar__menu-btn" data-mobile-nav-toggle aria-label="Open navigation">
      <?= icon('menu', 'icon') ?>
    </button>
    <h4><?= e($pageTitle) ?></h4>
  </div>

  <div class="search-box">
    <?= icon('search', 'icon icon-sm search-box__icon') ?>
    <input
      type="search"
      class="input search-box__input"
      placeholder="Search hotels..."
      aria-label="Search"
      data-search-input
      autocomplete="off"
    >
    <div class="search-results glass" data-search-results hidden></div>
  </div>

  <div class="topbar__actions">
    <div class="dropdown">
      <button type="button" class="hotel-filter" data-dropdown-trigger>
        <?= icon('home', 'icon icon-sm') ?>
        <span><?= e($selectedHotelName) ?></span>
        <?= icon('chevron-down', 'icon icon-xs') ?>
      </button>
      <div class="dropdown__menu dropdown__menu--scroll glass">
        <?php if ($hasGlobalAccess): ?>
          <form method="POST" action="<?= route('hotel-filter.set') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="hotel_id" value="">
            <button type="submit" class="sidebar__link <?= $selectedHotelId === null ? 'active' : '' ?>">
              All Hotels
            </button>
          </form>
        <?php endif; ?>

        <?php foreach ($filterHotels as $hotel): ?>
          <form method="POST" action="<?= route('hotel-filter.set') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="hotel_id" value="<?= e($hotel['id']) ?>">
            <button type="submit" class="sidebar__link <?= $selectedHotelId === $hotel['id'] ? 'active' : '' ?>">
              <?= e($hotel['name']) ?>
            </button>
          </form>
        <?php endforeach; ?>

        <?php if ($filterHotels === []): ?>
          <div class="dropdown__menu-header">
            <span class="dropdown__menu-email">No hotels assigned</span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle color theme">
      <?= icon('sun', 'icon theme-toggle__icon theme-toggle__icon--sun') ?>
      <?= icon('moon', 'icon theme-toggle__icon theme-toggle__icon--moon') ?>
    </button>

    <div class="dropdown">
      <button type="button" class="theme-toggle notif-bell" data-notif-trigger aria-label="Notifications">
        <?= icon('bell', 'icon') ?>
        <span class="notif-badge" data-notif-badge hidden></span>
      </button>
      <div class="dropdown__menu dropdown__menu--scroll glass">
        <div class="dropdown__menu-header">
          <span class="dropdown__menu-name">Notifications</span>
        </div>
        <div data-notif-list>
          <div class="dropdown__menu-header">
            <span class="dropdown__menu-email">No notifications yet</span>
          </div>
        </div>
      </div>
    </div>

    <?php if ($user !== null): ?>
      <div class="dropdown">
        <button type="button" class="avatar" data-dropdown-trigger title="<?= e((string) $user['full_name']) ?>">
          <?= e($initial) ?>
        </button>
        <div class="dropdown__menu glass">
          <div class="dropdown__menu-header">
            <div class="dropdown__menu-name"><?= e((string) $user['full_name']) ?></div>
            <div class="dropdown__menu-email"><?= e((string) $user['email']) ?></div>
            <?php if ($roleLabel !== null): ?>
              <span class="badge badge--info mt-2"><?= e($roleLabel) ?></span>
            <?php endif; ?>
          </div>
          <a href="#" class="sidebar__link" aria-disabled="true">
            <?= icon('user', 'icon icon-sm') ?>
            <span>Profile</span>
          </a>
          <form method="POST" action="<?= route('logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar__link">
              <?= icon('log-out', 'icon icon-sm') ?>
              <span>Sign out</span>
            </button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="avatar" aria-hidden="true">?</div>
    <?php endif; ?>
  </div>
</header>
