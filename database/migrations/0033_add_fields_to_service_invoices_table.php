<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * service_invoices already had the generic GST-invoice shape
 * (taxable_value, cgst/sgst/igst, total_tax, grand_total) — this adds
 * what the one-off-charge generator specifically needs: which billing
 * entity issued it, the single flat GST rate picked (0/5/12/18, unlike
 * commission_invoices which is always 18%), the document type
 * (invoice/credit note/debit note), the transaction category, and the
 * derived place of supply.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE service_invoices
                ADD COLUMN billing_entity_id CHAR(36) NULL AFTER hotel_id,
                ADD COLUMN hotel_state_code VARCHAR(2) NULL AFTER financial_year,
                ADD COLUMN place_of_supply VARCHAR(100) NULL AFTER hotel_state_code,
                ADD COLUMN gst_rate DECIMAL(5, 2) NOT NULL DEFAULT 0 AFTER taxable_value,
                ADD COLUMN invoice_type ENUM('invoice', 'credit_note', 'debit_note') NOT NULL DEFAULT 'invoice' AFTER service_description,
                ADD COLUMN transaction_category ENUM('REG', 'RG') NOT NULL DEFAULT 'REG' AFTER invoice_type,
                ADD CONSTRAINT fk_service_invoices_billing_entity FOREIGN KEY (billing_entity_id)
                    REFERENCES company_compliance_details (id) ON DELETE RESTRICT ON UPDATE CASCADE
            SQL);

        $this->createIndex($pdo, 'idx_service_invoices_billing_entity_id', 'service_invoices', ['billing_entity_id']);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE service_invoices
                DROP FOREIGN KEY fk_service_invoices_billing_entity,
                DROP COLUMN billing_entity_id,
                DROP COLUMN hotel_state_code,
                DROP COLUMN place_of_supply,
                DROP COLUMN gst_rate,
                DROP COLUMN invoice_type,
                DROP COLUMN transaction_category
            SQL);
    }
};
