<?php

use App\Core\View;

/**
 * @var array<int, array<string, mixed>> $hotels
 * @var array<int, array<string, mixed>> $billingEntities
 */

View::section('breadcrumbs');
?>
<?= partial('breadcrumbs', [
    'items' => [
        ['label' => 'Home', 'href' => route('home')],
        ['label' => 'Commission Invoices', 'href' => route('commission-invoices.index')],
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

<form method="POST" action="<?= route('commission-invoices.store') ?>" class="booking-form-grid" data-invoice-form>
  <?= csrf_field() ?>

  <div class="booking-form__main">
    <div class="card glass mb-6" data-animate>
      <h3 class="mb-4">Hotel, Month &amp; Billing Entity</h3>
      <div class="booking-form__cols-3">
        <div class="field">
          <label for="hotel_id">Hotel</label>
          <select class="select" id="hotel_id" name="hotel_id" data-invoice-input required>
            <option value="">Select a hotel…</option>
            <?php foreach ($hotels as $h): ?>
              <option value="<?= e($h['id']) ?>"><?= e($h['name']) ?><?= !empty($h['city']) ? ' — ' . e($h['city']) : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="month">Month</label>
          <input class="input" type="month" id="month" name="month" data-invoice-input max="<?= date('Y-m') ?>" required>
        </div>
        <div class="field">
          <label for="billing_entity_id">Billing Entity</label>
          <select class="select" id="billing_entity_id" name="billing_entity_id" data-invoice-input required>
            <option value="">Select a billing entity…</option>
            <?php foreach ($billingEntities as $be): ?>
              <option value="<?= e($be['id']) ?>"><?= e($be['legal_entity_name']) ?><?= !empty($be['state']) ? ' (' . e($be['state']) . ')' : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <p class="text-low mt-4" style="font-size: 0.8125rem;" data-invoice-status>Select a hotel, month, and billing entity to pull confirmed bookings.</p>
    </div>

    <div class="card glass mb-6" data-breakup-section hidden data-animate>
      <div class="flex-between mb-4">
        <h3>Breakup <span class="text-low" style="font-size: 0.8125rem; font-weight: 400;">— editable before generating</span></h3>
        <span class="badge badge--info" data-gst-type-badge></span>
      </div>

      <div class="booking-form__cols-3">
        <div class="field">
          <label for="total_bookings">Bookings</label>
          <input class="input" type="number" id="total_bookings" name="total_bookings" data-field="total_bookings" readonly>
        </div>
        <div class="field">
          <label for="total_room_nights">Room Nights</label>
          <input class="input" type="number" min="0" id="total_room_nights" name="total_room_nights" data-field="total_room_nights">
        </div>
        <div class="field">
          <label for="total_room_rent">Room Rent (₹)</label>
          <input class="input" type="number" min="0" step="0.01" id="total_room_rent" name="total_room_rent" data-field="total_room_rent">
        </div>
        <div class="field">
          <label for="total_ota_commission">OTA Commission (₹)</label>
          <input class="input" type="number" min="0" step="0.01" id="total_ota_commission" name="total_ota_commission" data-field="total_ota_commission">
        </div>
        <div class="field">
          <label for="taxable_value">Hotezo Commission — Taxable Value (₹)</label>
          <input class="input" type="number" min="0" step="0.01" id="taxable_value" name="taxable_value" data-field="taxable_value">
        </div>
      </div>

      <h4 class="mt-6 mb-3">GST (18%)</h4>
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

      <h4 class="mt-6 mb-3">Withholding (deducted by the hotel, not added to the invoice)</h4>
      <div class="booking-form__cols-2">
        <div class="field">
          <label for="tds_amount">TDS <span class="text-low" data-tds-rate-label></span> (₹)</label>
          <input class="input" type="number" min="0" step="0.01" id="tds_amount" name="tds_amount" data-field="tds_amount">
        </div>
        <div class="field">
          <label for="tcs_amount">TCS <span class="text-low" data-tcs-rate-label></span> (₹)</label>
          <input class="input" type="number" min="0" step="0.01" id="tcs_amount" name="tcs_amount" data-field="tcs_amount">
        </div>
      </div>

      <div class="field mt-4">
        <label for="notes">Notes <span class="text-low">(optional, printed on the invoice)</span></label>
        <textarea class="input" id="notes" name="notes" rows="2"></textarea>
      </div>
    </div>

    <div class="flex gap-3" data-animate>
      <button type="submit" class="btn btn-primary btn-lg" data-generate-btn disabled>Generate Invoice</button>
      <a href="<?= route('commission-invoices.index') ?>" class="btn btn-ghost btn-lg" data-invoice-cancel-link>Cancel</a>
    </div>
  </div>

  <aside class="calc-summary glass" data-calc-summary>
    <h3 class="mb-4">Invoice Summary</h3>
    <dl class="calc-summary__list">
      <div class="calc-summary__row"><dt>Taxable Value</dt><dd data-summary="taxable_value">₹0</dd></div>
      <div class="calc-summary__row"><dt>Total GST</dt><dd data-summary="total_tax">₹0</dd></div>
      <div class="calc-summary__row calc-summary__row--divider"><dt>Grand Total</dt><dd data-summary="grand_total">₹0</dd></div>
      <div class="calc-summary__row"><dt>Less TDS</dt><dd data-summary="tds_amount">₹0</dd></div>
      <div class="calc-summary__row"><dt>Less TCS</dt><dd data-summary="tcs_amount">₹0</dd></div>
      <div class="calc-summary__row calc-summary__row--highlight"><dt>Net Receivable</dt><dd data-summary="net_receivable">₹0</dd></div>
    </dl>
    <p class="text-low mt-4" style="font-size: 0.75rem;">
      Numbers pull from confirmed bookings automatically and can be edited above before you generate — nothing is saved until you click "Generate Invoice".
    </p>
  </aside>
</form>

<?php View::section('scripts'); ?>
<script type="module" src="<?= asset('js/commission-invoice-form.js') ?>"></script>
<?php View::endSection(); ?>
<?php endif; ?>
