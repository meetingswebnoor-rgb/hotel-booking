<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Unified, queryable ledger across all three invoice types — one row
 * per issued invoice regardless of whether it's a guest_invoices,
 * commission_invoices, or service_invoices record. source_id is
 * intentionally polymorphic (points into whichever table invoice_type
 * names) so it can't carry a real FK constraint; invoice_email_logs
 * links back to this table instead of the three type-specific ones.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS invoices (
                id CHAR(36) NOT NULL PRIMARY KEY,
                invoice_type ENUM('guest', 'commission', 'service') NOT NULL,
                source_id CHAR(36) NOT NULL,
                hotel_id CHAR(36) NULL,
                invoice_number VARCHAR(50) NOT NULL,
                invoice_date DATE NOT NULL,
                financial_year VARCHAR(9) NOT NULL,
                grand_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                {$audit},
                UNIQUE KEY uq_invoices_type_source (invoice_type, source_id),
                CONSTRAINT fk_invoices_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_invoices_hotel_id', 'invoices', ['hotel_id']);
        $this->createIndex($pdo, 'idx_invoices_invoice_type', 'invoices', ['invoice_type']);
        $this->createIndex($pdo, 'idx_invoices_invoice_date', 'invoices', ['invoice_date']);
        $this->createIndex($pdo, 'idx_invoices_status', 'invoices', ['status']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'invoices');
    }
};
