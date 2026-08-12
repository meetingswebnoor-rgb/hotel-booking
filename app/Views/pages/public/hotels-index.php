<?php

/**
 * Public hotel directory — GET /explore. Deliberately minimal: name,
 * city, room count, a starting price, and a link to the hotel's own
 * page. No financials, no auth required. See PublicHotelController.
 *
 * @var array<int, array<string, mixed>> $hotels
 * @var string $search
 */
?>
<section class="section" style="padding-top: var(--space-10);">
  <div class="container">
    <div class="flex-between mb-6" data-animate>
      <div>
        <h1>Browse Hotels</h1>
        <p class="text-med mt-2">Every hotel currently live on Hotezo.</p>
      </div>
    </div>

    <form method="GET" action="<?= route('public.hotels.index') ?>" class="card glass filter-bar mb-8" data-animate>
      <div class="field">
        <label for="explore-q">Search by name or city</label>
        <div class="search-box" style="max-width: none;">
          <?= icon('search', 'icon icon-sm search-box__icon') ?>
          <input class="input search-box__input" type="search" id="explore-q" name="q" value="<?= e($search) ?>" placeholder="e.g. Mumbai, Sunset Palm Resort">
        </div>
      </div>
      <div class="filter-bar__actions">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if ($search !== ''): ?>
          <a href="<?= route('public.hotels.index') ?>" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($hotels === []): ?>
      <?= partial('empty-state', [
          'title' => $search !== '' ? 'No hotels match that search' : 'No hotels live yet',
          'description' => $search !== '' ? 'Try a different name or city.' : 'Check back soon.',
          'icon' => '🏨',
      ]) ?>
    <?php else: ?>
      <div class="hotel-grid" data-animate>
        <?php foreach ($hotels as $h): ?>
          <a class="hotel-card glass card-hover" href="<?= route('public.hotels.show', ['slug' => $h['slug']]) ?>">
            <div class="hotel-card__image" <?= $h['hero_image'] ? 'style="background-image: url(\'' . e(asset($h['hero_image'])) . '\')"' : '' ?>>
              <?php if (!$h['hero_image']): ?><?= icon('home', 'hotel-card__placeholder-icon') ?><?php endif; ?>
            </div>
            <div class="hotel-card__body">
              <h3 class="hotel-card__name"><?= e($h['name']) ?></h3>
              <p class="hotel-card__location">
                <?= icon('map-pin', 'icon icon-sm') ?>
                <span><?= e(trim(($h['city'] ?: '—') . ', ' . $h['country'], ', ')) ?></span>
              </p>
              <div class="hotel-card__stats">
                <div class="hotel-card__stat">
                  <strong><?= (int) $h['room_count'] ?></strong>
                  <span>Rooms</span>
                </div>
                <?php if ($h['from_price'] !== null): ?>
                  <div class="hotel-card__stat">
                    <strong><?= money((float) $h['from_price'], false) ?></strong>
                    <span>From / night</span>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
