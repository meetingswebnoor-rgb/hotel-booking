<?php

use App\Core\View;

/**
 * OTA partner cards + a Reviews section underneath. Server-rendered
 * (a handful of OTAs, not thousands of bookings — same reasoning as
 * the Hotels list), one edit modal per existing OTA plus one add
 * modal, same pattern as Rooms/Rate Plans.
 *
 * @var array<int, array<string, mixed>> $otas
 * @var array<int, array<string, mixed>> $reviews
 * @var string|null $selectedOtaId
 * @var array<int, string> $statusOptions
 * @var bool $canCreate
 * @var bool $canManage
 * @var bool $canDelete
 */

$starLoop = static function (float $rating, string $sizeClass = 'icon-sm'): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $filled = $i <= round($rating);
        $out .= '<span class="star-rating__display' . ($filled ? ' star-rating__display--filled' : '') . '">' . icon('star', 'icon ' . $sizeClass) . '</span>';
    }

    return $out;
};

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', ['items' => [['label' => 'Home', 'href' => route('home')], ['label' => 'OTAs']]]) ?>
<?php View::endSection(); ?>

<div class="flex-between mb-6" data-animate>
  <div>
    <h2>OTAs</h2>
    <p class="text-med mt-2"><?= count($otas) ?> partner<?= count($otas) === 1 ? '' : 's' ?> connected</p>
  </div>
  <?php if ($canCreate): ?>
    <button type="button" class="btn btn-primary" data-modal-open="ota-add">
      <?= icon('plus', 'icon icon-sm') ?>
      <span>Add OTA</span>
    </button>
  <?php endif; ?>
</div>

<?php if ($otas === []): ?>
  <?= partial('empty-state', [
      'title' => 'No OTAs yet',
      'description' => $canCreate ? 'Add your first booking partner to start tracking commissions.' : 'Ask your Admin to add OTA partners.',
      'icon' => '🌐',
  ]) ?>
<?php else: ?>
  <div class="ota-grid mb-8" data-animate>
    <?php foreach ($otas as $o): ?>
      <?php $avgRating = (float) ($o['avg_rating'] ?? 0); ?>
      <div class="ota-card glass card-hover" style="--ota-color: <?= e($o['brand_color']) ?>">
        <div class="ota-card__header">
          <span class="ota-card__monogram" style="background: <?= e($o['brand_color']) ?>"><?= e(mb_substr($o['name'], 0, 1)) ?></span>
          <div class="ota-card__title">
            <h3><?= e($o['name']) ?></h3>
            <span class="badge badge--<?= $o['status'] === 'active' ? 'success' : 'neutral' ?>"><?= e(ucfirst($o['status'])) ?></span>
          </div>
        </div>

        <div class="ota-card__stats">
          <div class="ota-card__stat">
            <strong><?= e(number_format((float) $o['commission_pct'], 1)) ?>%</strong>
            <span>Commission</span>
          </div>
          <div class="ota-card__stat">
            <strong><?= (int) $o['review_count'] ?></strong>
            <span>Reviews</span>
          </div>
        </div>

        <?php if ((int) $o['review_count'] > 0): ?>
          <div class="star-rating star-rating--display mb-2" aria-hidden="true"><?= $starLoop($avgRating) ?></div>
        <?php endif; ?>

        <?php if (!empty($o['settlement_rules'])): ?>
          <p class="ota-card__settlement text-low"><?= e($o['settlement_rules']) ?></p>
        <?php endif; ?>

        <?php
        $customStatuses = !empty($o['custom_payment_statuses']) ? (json_decode((string) $o['custom_payment_statuses'], true) ?: []) : [];
        ?>
        <?php if ($customStatuses !== []): ?>
          <div class="ota-card__tags">
            <?php foreach ($customStatuses as $tag): ?>
              <span class="badge badge--info"><?= e($tag) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($canManage || $canDelete): ?>
          <div class="ota-card__actions">
            <?php if ($canManage): ?>
              <button type="button" class="link-btn" data-modal-open="ota-edit-<?= e($o['id']) ?>"><?= icon('edit-2', 'icon icon-sm') ?> Edit</button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
              <form method="POST" action="<?= route('otas.destroy', ['id' => $o['id']]) ?>" data-confirm-submit data-confirm-title="Remove <?= e($o['name']) ?>?" data-confirm-message="This OTA will be soft-deleted and hidden from the booking form.">
                <?= csrf_field() ?>
                <button type="submit" class="link-btn link-btn--danger"><?= icon('trash', 'icon icon-sm') ?> Remove</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php foreach ($otas as $o): ?>
    <?php if ($canManage): ?>
      <?= partial('admin/ota-modal', [
          'modalId' => 'ota-edit-' . $o['id'],
          'formAction' => route('otas.update', ['id' => $o['id']]),
          'ota' => $o,
          'statusOptions' => $statusOptions,
          'heading' => 'Edit ' . $o['name'],
      ]) ?>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>

<?php if ($canCreate): ?>
  <?= partial('admin/ota-modal', [
      'modalId' => 'ota-add',
      'formAction' => route('otas.store'),
      'ota' => null,
      'statusOptions' => $statusOptions,
      'heading' => 'Add OTA',
  ]) ?>
<?php endif; ?>

<div class="flex-between mb-6" data-animate>
  <div>
    <h2>Reviews</h2>
    <p class="text-med mt-2"><?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?><?= $selectedOtaId !== null ? ' for this OTA' : ' across all OTAs' ?></p>
  </div>
  <?php if ($canManage): ?>
    <button type="button" class="btn btn-primary" data-modal-open="ota-review-add">
      <?= icon('plus', 'icon icon-sm') ?>
      <span>Add Review</span>
    </button>
  <?php endif; ?>
</div>

<form method="GET" action="<?= route('otas.index') ?>" class="card glass filter-bar mb-6" data-animate>
  <div class="filter-bar__row">
    <div class="field">
      <label for="filter-ota">OTA</label>
      <select class="select" id="filter-ota" name="ota_id" data-auto-submit>
        <option value="">All OTAs</option>
        <?php foreach ($otas as $o): ?>
          <option value="<?= e($o['id']) ?>" <?= $selectedOtaId === $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</form>

<?php if ($reviews === []): ?>
  <?= partial('empty-state', [
      'title' => 'No reviews yet',
      'description' => $canManage ? 'Add a review to start building an OTA track record.' : 'Nothing has been logged for this OTA yet.',
      'icon' => '⭐',
  ]) ?>
<?php else: ?>
  <div class="review-grid" data-animate>
    <?php foreach ($reviews as $r): ?>
      <div class="card glass review-card">
        <div class="flex-between mb-2">
          <span class="badge badge--info" style="background: <?= e($r['ota_color']) ?>22; color: <?= e($r['ota_color']) ?>;"><?= e($r['ota_name']) ?></span>
          <div class="star-rating star-rating--display" aria-hidden="true"><?= $starLoop((float) $r['rating']) ?></div>
        </div>
        <p class="review-card__text">&ldquo;<?= e($r['review_text']) ?>&rdquo;</p>
        <div class="flex-between mt-3">
          <span class="text-low" style="font-size: 0.8125rem;"><?= e($r['author_name']) ?> &middot; <?= e(date('M j, Y', strtotime((string) $r['created_at']))) ?></span>
          <?php if ($canManage): ?>
            <form method="POST" action="<?= route('otas.reviews.destroy', ['id' => $r['id']]) ?>" data-confirm-submit data-confirm-title="Remove this review?" data-confirm-message="This review by <?= e($r['author_name']) ?> will be soft-deleted.">
              <?= csrf_field() ?>
              <button type="submit" class="link-btn link-btn--danger"><?= icon('trash', 'icon icon-sm') ?></button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($canManage): ?>
  <?= partial('admin/ota-review-modal', ['otas' => $otas, 'selectedOtaId' => $selectedOtaId]) ?>
<?php endif; ?>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/otas.js') ?>"></script>
<?php View::endSection(); ?>
