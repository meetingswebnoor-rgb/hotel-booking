<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * The original commission_invoices table only carried a generic
 * taxable_value + cgst/sgst/igst shape — enough for a GST invoice, but
 * not the specific breakup (room nights, room rent, OTA commission,
 * TDS, TCS) the commission-invoice generator needs to actually show.
 * taxable_value itself is reused as-is for the Hotezo-commission total
 * (the value Hotezo's own service is taxed on) rather than adding a
 * duplicate column.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE commission_invoices
                ADD COLUMN billing_entity_id CHAR(36) NULL AFTER settlement_id,
                ADD COLUMN bill_number VARCHAR(50) NULL AFTER invoice_number,
                ADD COLUMN hotel_state_code VARCHAR(2) NULL AFTER financial_year,
                ADD COLUMN total_room_nights INT UNSIGNED NOT NULL DEFAULT 0 AFTER total_bookings,
                ADD COLUMN total_room_rent DECIMAL(12, 2) NOT NULL DEFAULT 0 AFTER total_room_nights,
                ADD COLUMN total_ota_commission DECIMAL(12, 2) NOT NULL DEFAULT 0 AFTER total_room_rent,
                ADD COLUMN tds_rate DECIMAL(5, 2) NOT NULL DEFAULT 0 AFTER total_tax,
                ADD COLUMN tds_amount DECIMAL(12, 2) NOT NULL DEFAULT 0 AFTER tds_rate,
                ADD COLUMN tcs_rate DECIMAL(5, 2) NOT NULL DEFAULT 0 AFTER tds_amount,
                ADD COLUMN tcs_amount DECIMAL(12, 2) NOT NULL DEFAULT 0 AFTER tcs_rate,
                ADD COLUMN net_receivable DECIMAL(12, 2) NOT NULL DEFAULT 0 AFTER grand_total,
                ADD CONSTRAINT fk_commission_invoices_billing_entity FOREIGN KEY (billing_entity_id)
                    REFERENCES company_compliance_details (id) ON DELETE RESTRICT ON UPDATE CASCADE
            SQL);

        $this->createIndex($pdo, 'idx_commission_invoices_billing_entity_id', 'commission_invoices', ['billing_entity_id']);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE commission_invoices
                DROP FOREIGN KEY fk_commission_invoices_billing_entity,
                DROP COLUMN billing_entity_id,
                DROP COLUMN bill_number,
                DROP COLUMN hotel_state_code,
                DROP COLUMN total_room_nights,
                DROP COLUMN total_room_rent,
                DROP COLUMN total_ota_commission,
                DROP COLUMN tds_rate,
                DROP COLUMN tds_amount,
                DROP COLUMN tcs_rate,
                DROP COLUMN tcs_amount,
                DROP COLUMN net_receivable
            SQL);
    }
};
