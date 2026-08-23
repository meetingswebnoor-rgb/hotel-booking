<?php

use App\Core\View;

/**
 * @var array<int, array<string, mixed>> $hotels
 * @var array<int, array<string, mixed>> $billingEntities
 * @var array<int, float> $gstRates
 * @var array<string, string> $invoiceTypes
 * @var array<string, string> $transactionCategories
 */

$errors = form_errors();

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', [
    'items' => [
        ['label' => 'Home', 'href' => route('home')],
        ['label' => 'Service Invoices', 'href' => route('service-invoices.index')],
        ['label' => 'Generate'],
    ],
]) ?>
<?php View::endSection(); ?>

<?php if ($hotels === [] || $billingEntities === []): ?>
  <?= partial('empty-state', [
      'title' => $hotels === [] ? 'No hotels assigned' : 'No billing entity set up',
      'description' => $hotels === []
          ? 'Ask your Admin to assign you to a hotel before generating invoices.'
          : 'Add at least one Hotezo billing entity to company_compliance_details before generating invoices.',
      'icon' => '🧾',
  ]) ?>
<?php else: ?>

<form method="POST" action="<?= route('service-invoices.store') ?>" class="booking-form-grid" data-service-invoice-form>
  <?= csrf_field() ?>

  <div class="booking-form__main">
    <div class="card glass mb-6" data-animate>
      <h3 class="mb-4">Charge Details</h3>
      <div class="booking-form__cols-2">
        <div class="field">
          <label for="hotel_id">Hotel</label>
          <select class="select" id="hotel_id" name="hotel_id" data-field-input required>
            <option value="">Select a hotel…</option>
            <?php foreach ($hotels as $h): ?>
              <option value="<?= e($h['id']) ?>" data-state-code="<?= e((string) ($h['state_code'] ?? '')) ?>" <?= old('hotel_id') === $h['id'] ? 'selected' : '' ?>>
                <?= e($h['name']) ?><?= !empty($h['city']) ? ' — ' . e($h['city']) : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['hotel_id'])): ?><span class="field-error"><?= e($errors['hotel_id'][0]) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label for="billing_entity_id">Billing Entity</label>
          <select class="select" id="billing_entity_id" name="billing_entity_id" data-field-input required>
            <option value="">Select a billing entity…</option>
            <?php foreach ($billingEntities as $be): ?>
              <option value="<?= e($be['id']) ?>" data-state-code="<?= e((string) ($be['state_code'] ?? '')) ?>" <?= old('billing_entity_id') === $be['id'] ? 'selected' : '' ?>>
                <?= e($be['legal_entity_name']) ?><?= !empty($be['state']) ? ' (' . e($be['state']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['billing_entity_id'])): ?><span class="field-error"><?= e($errors['billing_entity_id'][0]) ?></span><?php endif; ?>
        </div>
      </div>

      <div class="field mt-4">
        <label for="service_description">Service Description</label>
        <input class="input" type="text" id="service_description" name="service_description" value="<?= e(old('service_description', 'Hotezo Platform Subscription')) ?>" required>
        <?php if (isset($errors['service_description'])): ?><span class="field-error"><?= e($errors['service_description'][0]) ?></span><?php endif; ?>
      </div>

      <div class="booking-form__cols-3 mt-4">
        <div class="field">
          <label for="taxable_value">Amount (₹)</label>
          <input class="input" type="number" min="0" step="0.01" id="taxable_value" name="taxable_value" data-field-input value="<?= e(old('taxable_value', '')) ?>" required>
          <?php if (isset($errors['taxable_value'])): ?><span class="field-error"><?= e($errors['taxable_value'][0]) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label for="gst_rate">GST Rate</label>
          <select class="select" id="gst_rate" name="gst_rate" data-field-input>
            <?php foreach ($gstRates as $rate): ?>
              <option value="<?= e((string) (int) $rate) ?>" <?= old('gst_rate', '18') === (string) (int) $rate ? 'selected' : '' ?>><?= (int) $rate ?>%</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="invoice_type">Type</label>
          <select class="select" id="invoice_type" name="invoice_type">
            <?php foreach ($invoiceTypes as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= old('invoice_type', 'invoice') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field mt-4">
        <label for="transaction_category">Transaction Category</label>
        <select class="select" id="transaction_category" name="transaction_category" style="max-width: 280px;">
          <?php foreach ($transactionCategories as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= old('transaction_category', 'REG') === $value ? 'selected' : '' ?>><?= e($value) ?> — <?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card glass mb-6" data-breakup-section hidden data-animate>
      <div class="flex-between mb-4">
        <h3>GST Breakup <span class="text-low" style="font-size: 0.8125rem; font-weight: 400;">— editable before generating</span></h3>
        <span class="badge badge--info" data-gst-type-badge></span>
      </div>

      <div class="booking-form__cols-3" data-intra-state-fields>
        <div class="field">
          <label for="cgst_rate">CGST %</label>
          <input class="input" type="number" min="0" step="0.01" id="cgst_rate" name="cgst_rate" data-field="cgst_rate">
        </div>
        <div class="field">
          <label for="cgst_amount">CGST Amount (₹)</label>
          <input class="input" type="number" min="0" step="0.01" id="cgst_amount" name="cgst_amount" data-field="cgst_amount">
        </div>
        <div class="field">
          <label for="sgst_rate">SGST %</label>
          <input class="input" type="number" min="0" step="0.01" id="sgst_rate" name="sgst_rate" data-field="sgst_rate">
        </div>
        <div class="field">
          <label for="sgst_amount">SGST Amount (₹)</label>
          <input class="input" type="number" min="0" step="0.01" id="sgst_amount" name="sgst_amount" data-field="sgst_amount">
        </div>
      </div>
      <div class="booking-form__cols-2" data-inter-state-fields hidden>
        <div class="field">
          <label for="igst_rate">IGST %</label>
          <input class="input" type="number" min="0" step="0.01" id="igst_rate" name="igst_rate" data-field="igst_rate">
        </div>
        <div class="field">
          <label for="igst_amount">IGST Amount (₹)</label>
          <input class="input" type="number" min="0" step="0.01" id="igst_amount" name="igst_amount" data-field="igst_amount">
        </div>
      </div>

      <p class="text-low mt-4" style="font-size: 0.8125rem;" data-place-of-supply></p>

      <div class="field mt-4">
        <label for="notes">Notes <span class="text-low">(optional, printed on the invoice)</span></label>
        <textarea class="input" id="notes" name="notes" rows="2"><?= e(old('notes')) ?></textarea>
      </div>
    </div>

    <div class="flex gap-3" data-animate>
      <button type="submit" class="btn btn-primary btn-lg" data-generate-btn disabled>Generate Invoice</button>
      <a href="<?= route('service-invoices.index') ?>" class="btn btn-ghost btn-lg">Cancel</a>
    </div>
  </div>

  <aside class="calc-summary glass" data-calc-summary>
    <h3 class="mb-4">Invoice Summary</h3>
    <dl class="calc-summary__list">
      <div class="calc-summary__row"><dt>Amount</dt><dd data-summary="taxable_value">₹0</dd></div>
      <div class="calc-summary__row"><dt>Total GST</dt><dd data-summary="total_tax">₹0</dd></div>
      <div class="calc-summary__row calc-summary__row--highlight"><dt>Total Payable</dt><dd data-summary="grand_total">₹0</dd></div>
    </dl>
    <p class="text-low mt-4" style="font-size: 0.75rem;">
      Nothing is saved until you click "Generate Invoice".
    </p>
  </aside>
</form>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/service-invoice-form.js') ?>"></script>
<?php View::endSection(); ?>
<?php endif; ?>
