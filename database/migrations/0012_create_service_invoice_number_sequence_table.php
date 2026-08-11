<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Counter for Hotezo's own service/commission invoice numbering.
 * hotel_id is nullable: NULL means a single global Hotezo sequence;
 * set it only if Hotezo ever needs a separate numbering series per hotel.
 *
 * Note: MySQL/MariaDB treat every NULL as distinct for UNIQUE KEY
 * purposes, so the (hotel_id, financial_year, prefix) key does not by
 * itself stop two hotel_id=NULL rows for the same year/prefix — the
 * service that allocates numbers must SELECT-then-INSERT rather than
 * rely on a DB-level uniqueness violation for the global-sequence case.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS service_invoice_number_sequence (
                id CHAR(36) NOT NULL PRIMARY KEY,
                hotel_id CHAR(36) NULL,
                financial_year VARCHAR(9) NOT NULL,
                prefix VARCHAR(20) NOT NULL DEFAULT 'SRV',
                last_number INT UNSIGNED NOT NULL DEFAULT 0,
                {$audit},
                UNIQUE KEY uq_service_invoice_number_sequence (hotel_id, financial_year, prefix),
                CONSTRAINT fk_service_invoice_number_sequence_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_service_invoice_number_sequence_hotel_id', 'service_invoice_number_sequence', ['hotel_id']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'service_invoice_number_sequence');
    }
};
