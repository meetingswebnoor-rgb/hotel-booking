<?php

use App\Core\View;

/**
 * @var array<int, string> $settlementTypes
 * @var array<int, string> $gstTypes
 * @var array<string, string> $statusOptions
 */

$hotel = null;

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', [
    'items' => [
        ['label' => 'Home', 'href' => route('home')],
        ['label' => 'Hotels', 'href' => route('hotels.index')],
        ['label' => 'New Hotel'],
    ],
]) ?>
<?php View::endSection(); ?>

<form method="POST" action="<?= route('hotels.store') ?>" enctype="multipart/form-data" class="form-narrow" data-hotel-form>
  <?= csrf_field() ?>

  <?= partial('admin/hotel-form-fields', [
      'hotel' => $hotel,
      'settlementTypes' => $settlementTypes,
      'gstTypes' => $gstTypes,
      'statusOptions' => $statusOptions,
  ]) ?>

  <div class="flex gap-3" data-animate>
    <button type="submit" class="btn btn-primary btn-lg">Create Hotel</button>
    <a href="<?= route('hotels.index') ?>" class="btn btn-ghost btn-lg">Cancel</a>
  </div>
</form>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/hotel-hub.js') ?>"></script>
<?php View::endSection(); ?>
