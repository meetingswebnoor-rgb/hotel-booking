<?php

/**
 * Shared field set for the hotel Details form — used by both the
 * create page and the hub's Details tab. Money/compliance fields
 * mirror the hotels table exactly (see database/migrations/0004).
 *
 * @var array<string, mixed>|null $hotel
 * @var array<int, string> $settlementTypes
 * @var array<int, string> $gstTypes
 * @var array<string, string> $statusOptions
 */

$errors = form_errors();
$gallery = $hotel !== null ? (json_decode((string) ($hotel['gallery'] ?? '[]'), true) ?: []) : [];

$val = static function (string $key, string $default = '') use ($hotel) {
    if ($hotel !== null && array_key_exists($key, $hotel)) {
        return (string) $hotel[$key];
    }

    return old($key, $default);
};
?>
<div class="card glass mb-6" data-animate>
  <h3 class="mb-4">Basic Details</h3>
  <div class="booking-form__cols-2">
    <div class="field">
      <label for="name">Hotel Name</label>
      <input class="input" type="text" id="name" name="name" value="<?= e($val('name')) ?>" data-hotel-name required>
      <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name'][0]) ?></span><?php endif; ?>
    </div>
    <div class="field">
      <label for="slug">Slug <span class="text-low">(used in the public URL)</span></label>
      <input class="input" type="text" id="slug" name="slug" value="<?= e($val('slug')) ?>" data-hotel-slug maxlength="191" required>
      <?php if (isset($errors['slug'])): ?><span class="field-error"><?= e($errors['slug'][0]) ?></span><?php endif; ?>
    </div>
    <div class="field">
      <label for="status">Status</label>
      <select class="select" id="status" name="status">
        <?php foreach ($statusOptions as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $val('status', 'pending_approval') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="commission_pct">Hotezo Commission — <span data-commission-display><?= e($val('commission_pct', '15')) ?></span>%</label>
      <input class="range" type="range" id="commission_pct" name="commission_pct" min="5" max="50" step="0.5" value="<?= e($val('commission_pct', '15')) ?>" data-commission-slider>
      <?php if (isset($errors['commission_pct'])): ?><span class="field-error"><?= e($errors['commission_pct'][0]) ?></span><?php endif; ?>
    </div>
  </div>
</div>

<div class="card glass mb-6" data-animate>
  <h3 class="mb-4">Location</h3>
  <div class="field mb-4">
    <label for="address">Address</label>
    <textarea class="input" id="address" name="address" rows="2"><?= e($val('address')) ?></textarea>
  </div>
  <div class="booking-form__cols-2">
    <div class="field">
      <label for="city">City</label>
      <input class="input" type="text" id="city" name="city" value="<?= e($val('city')) ?>">
    </div>
    <div class="field">
      <label for="country">Country</label>
      <input class="input" type="text" id="country" name="country" value="<?= e($val('country', 'India')) ?>">
    </div>
  </div>
</div>

<div class="card glass mb-6" data-animate>
  <h3 class="mb-4">Compliance &amp; Contact</h3>
  <div class="booking-form__cols-3">
    <div class="field">
      <label for="gst_number">GST Number</label>
      <input class="input" type="text" id="gst_number" name="gst_number" value="<?= e($val('gst_number')) ?>" maxlength="15">
    </div>
    <div class="field">
      <label for="pan">PAN</label>
      <input class="input" type="text" id="pan" name="pan" value="<?= e($val('pan')) ?>" maxlength="10">
    </div>
    <div class="field">
      <label for="gst_type">GST Type</label>
      <select class="select" id="gst_type" name="gst_type">
        <?php foreach ($gstTypes as $type): ?>
          <option value="<?= e($type) ?>" <?= $val('gst_type', 'Unregistered') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="contact_person">Contact Person</label>
      <input class="input" type="text" id="contact_person" name="contact_person" value="<?= e($val('contact_person')) ?>">
    </div>
    <div class="field">
      <label for="mobile">Mobile</label>
      <input class="input" type="tel" id="mobile" name="mobile" value="<?= e($val('mobile')) ?>">
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input class="input" type="email" id="email" name="email" value="<?= e($val('email')) ?>">
    </div>
  </div>
</div>

<div class="card glass mb-6" data-animate>
  <h3 class="mb-4">Banking &amp; Settlement</h3>
  <div class="booking-form__cols-3">
    <div class="field">
      <label for="bank_account">Bank Account No.</label>
      <input class="input" type="text" id="bank_account" name="bank_account" value="<?= e($val('bank_account')) ?>" maxlength="30">
    </div>
    <div class="field">
      <label for="ifsc">IFSC</label>
      <input class="input" type="text" id="ifsc" name="ifsc" value="<?= e($val('ifsc')) ?>" maxlength="11">
    </div>
    <div class="field">
      <label for="account_holder">Account Holder</label>
      <input class="input" type="text" id="account_holder" name="account_holder" value="<?= e($val('account_holder')) ?>">
    </div>
  </div>
  <div class="field mt-4" style="max-width: 280px;">
    <label for="settlement_type">Settlement Type</label>
    <select class="select" id="settlement_type" name="settlement_type">
      <?php foreach ($settlementTypes as $type): ?>
        <option value="<?= e($type) ?>" <?= $val('settlement_type', 'Monthly') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="card glass mb-6" data-animate>
  <h3 class="mb-4">Photos</h3>
  <div class="field mb-4">
    <label for="hero_image">Hero Image</label>
    <?php if ($hotel !== null && $hotel['hero_image']): ?>
      <img class="hotel-photo-preview" src="<?= e(asset($hotel['hero_image'])) ?>" alt="Current hero image">
    <?php endif; ?>
    <input class="input" type="file" id="hero_image" name="hero_image" accept="image/jpeg,image/png,image/webp" data-image-preview-input>
    <span class="text-low" style="font-size: 0.8125rem;">JPEG, PNG, or WebP — up to 5MB.</span>
  </div>

  <?php if ($gallery !== []): ?>
    <div class="field mb-4">
      <label>Current Gallery</label>
      <div class="gallery-grid">
        <?php foreach ($gallery as $path): ?>
          <label class="gallery-thumb">
            <img src="<?= e(asset($path)) ?>" alt="Gallery photo">
            <span class="gallery-thumb__remove">
              <input type="checkbox" name="remove_gallery[]" value="<?= e($path) ?>">
              <?= icon('x', 'icon icon-sm') ?> Remove
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="field">
    <label for="gallery">Add Gallery Photos</label>
    <input class="input" type="file" id="gallery" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple>
  </div>
</div>
