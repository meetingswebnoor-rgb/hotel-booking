<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Per-hotel, per-financial-year counter for guest_invoices numbering
 * (each hotel is its own GST-registered entity, so its invoice
 * sequence must be gapless and independent of other hotels).
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS invoice_number_sequence (
                id CHAR(36) NOT NULL PRIMARY KEY,
                hotel_id CHAR(36) NOT NULL,
                financial_year VARCHAR(9) NOT NULL,
                prefix VARCHAR(20) NOT NULL DEFAULT 'INV',
                last_number INT UNSIGNED NOT NULL DEFAULT 0,
                {$audit},
                UNIQUE KEY uq_invoice_number_sequence (hotel_id, financial_year, prefix),
                CONSTRAINT fk_invoice_number_sequence_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_invoice_number_sequence_hotel_id', 'invoice_number_sequence', ['hotel_id']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'invoice_number_sequence');
    }
};
