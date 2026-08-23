<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Counter for commission_invoices numbering, scoped by billing entity
 * (a company_compliance_details row with hotel_id IS NULL — one of
 * Hotezo's own state-registered entities) rather than by hotel: one
 * Hotezo entity invoices many hotels, so the sequence must run
 * continuously across all of them for that entity's state + FY, not
 * restart per hotel the way invoice_number_sequence (guest invoices,
 * each hotel its own GST-registered issuer) and
 * service_invoice_number_sequence do. Same one-dedicated-sequence-
 * table-per-invoice-type convention as those two, just scoped to what
 * commission invoices actually need.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS commission_invoice_number_sequence (
                id CHAR(36) NOT NULL PRIMARY KEY,
                billing_entity_id CHAR(36) NOT NULL,
                financial_year VARCHAR(9) NOT NULL,
                prefix VARCHAR(20) NOT NULL DEFAULT 'HZC',
                last_number INT UNSIGNED NOT NULL DEFAULT 0,
                {$audit},
                UNIQUE KEY uq_commission_invoice_number_sequence (billing_entity_id, financial_year, prefix),
                CONSTRAINT fk_commission_invoice_number_sequence_entity FOREIGN KEY (billing_entity_id)
                    REFERENCES company_compliance_details (id) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_commission_invoice_number_sequence_entity_id', 'commission_invoice_number_sequence', ['billing_entity_id']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'commission_invoice_number_sequence');
    }
};
