<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * hotel_id NULL = platform-wide defaults; hotel_id set = per-hotel
 * override of numbering/terms/footer for guest_invoices.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS invoice_settings (
                id CHAR(36) NOT NULL PRIMARY KEY,
                hotel_id CHAR(36) NULL,
                invoice_prefix VARCHAR(20) NOT NULL DEFAULT 'INV',
                financial_year_start_month TINYINT UNSIGNED NOT NULL DEFAULT 4,
                default_gst_rate DECIMAL(5, 2) NOT NULL DEFAULT 12.00,
                terms_and_conditions TEXT NULL,
                footer_note TEXT NULL,
                show_bank_details TINYINT(1) NOT NULL DEFAULT 1,
                auto_email_on_generate TINYINT(1) NOT NULL DEFAULT 1,
                {$audit},
                CONSTRAINT fk_invoice_settings_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_invoice_settings_hotel_id', 'invoice_settings', ['hotel_id']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'invoice_settings');
    }
};
