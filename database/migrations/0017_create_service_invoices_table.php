<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Hotezo's GST invoice to the hotel for the platform/subscription fee
 * — separate from commission_invoices (which bill per-booking commission).
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS service_invoices (
                id CHAR(36) NOT NULL PRIMARY KEY,
                hotel_id CHAR(36) NOT NULL,
                invoice_number VARCHAR(50) NOT NULL,
                invoice_date DATE NOT NULL,
                financial_year VARCHAR(9) NOT NULL,
                service_description VARCHAR(255) NOT NULL DEFAULT 'Hotezo Platform Subscription',
                billing_period_start DATE NULL,
                billing_period_end DATE NULL,
                taxable_value DECIMAL(12, 2) NOT NULL DEFAULT 0,
                cgst_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
                cgst_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                sgst_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
                sgst_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                igst_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
                igst_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                total_tax DECIMAL(12, 2) NOT NULL DEFAULT 0,
                grand_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
                status ENUM('draft', 'issued', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'draft',
                due_date DATE NULL,
                paid_at DATETIME NULL,
                pdf_path VARCHAR(255) NULL,
                notes TEXT NULL,
                {$audit},
                UNIQUE KEY uq_service_invoices_hotel_number (hotel_id, invoice_number),
                CONSTRAINT fk_service_invoices_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_service_invoices_hotel_id', 'service_invoices', ['hotel_id']);
        $this->createIndex($pdo, 'idx_service_invoices_status', 'service_invoices', ['status']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'service_invoices');
    }
};
