<?php

/**
 * Public hotel detail page — GET /hotel/{slug}. No online booking flow
 * exists yet (see README "What's next"), so the CTA here is an enquiry
 * (phone/email), not a reservation form — honest about what's actually
 * built rather than faking a booking button that goes nowhere.
 *
 * @var array<string, mixed> $hotel
 * @var array<int, array<string, mixed>> $roomTypes
 * @var array<int, string> $gallery
 */
?>
<section class="section" style="padding-top: var(--space-8);">
  <div class="container">
    <nav class="breadcrumbs mb-6" aria-label="Breadcrumb" data-animate>
      <a href="<?= route('public.hotels.index') ?>">Browse Hotels</a>
      <span class="sep">/</span>
      <span class="text-high"><?= e($hotel['name']) ?></span>
    </nav>

    <div class="hotel-card__image" style="height: 320px; border-radius: var(--radius-lg); margin-bottom: var(--space-8); <?= $hotel['hero_image'] ? "background-image: url('" . e(asset($hotel['hero_image'])) . "')" : '' ?>" data-animate>
      <?php if (!$hotel['hero_image']): ?><?= icon('home', 'hotel-card__placeholder-icon') ?><?php endif; ?>
    </div>

    <?php if ($gallery !== []): ?>
      <div class="gallery-grid mb-8" data-animate>
        <?php foreach (array_slice($gallery, 0, 8) as $path): ?>
          <div class="gallery-thumb"><img src="<?= e(asset($path)) ?>" alt="<?= e($hotel['name']) ?> photo" loading="lazy"></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="hero__grid" style="align-items: start; gap: var(--space-8);">
      <div>
        <h1 data-animate><?= e($hotel['name']) ?></h1>
        <p class="text-med mt-2" data-animate>
          <?= icon('map-pin', 'icon icon-sm') ?>
          <?= e(trim(($hotel['address'] ? $hotel['address'] . ', ' : '') . ($hotel['city'] ?: '') . ', ' . $hotel['country'], ', ')) ?>
        </p>

        <?php if ($roomTypes !== []): ?>
          <div class="card glass mt-8" data-animate>
            <h3 class="mb-4">Room Types</h3>
            <div class="table-wrap">
              <table class="table">
                <thead><tr><th>Type</th><th>Available</th><th>From</th></tr></thead>
                <tbody>
                  <?php foreach ($roomTypes as $rt): ?>
                    <tr>
                      <td><?= e($rt['room_type']) ?></td>
                      <td><?= (int) $rt['count'] ?></td>
                      <td><?= money((float) $rt['from_price']) ?> / night</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="card glass" style="padding: var(--space-6);" data-animate>
        <span class="badge badge--info mb-4">Enquire to book</span>
        <p class="text-med">Online booking for this hotel is opening soon — reach out directly for now.</p>
        <div class="mt-6" style="display: flex; flex-direction: column; gap: var(--space-3);">
          <?php if (!empty($hotel['mobile'])): ?>
            <a href="tel:<?= e($hotel['mobile']) ?>" class="btn btn-primary" style="width: 100%;"><?= icon('phone', 'icon icon-sm') ?><span>Call <?= e($hotel['mobile']) ?></span></a>
          <?php endif; ?>
          <?php if (!empty($hotel['email'])): ?>
            <a href="mailto:<?= e($hotel['email']) ?>" class="btn btn-ghost" style="width: 100%;"><?= icon('mail', 'icon icon-sm') ?><span>Email the hotel</span></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
