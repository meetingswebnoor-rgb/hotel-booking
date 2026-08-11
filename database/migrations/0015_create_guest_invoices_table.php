<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * The hotel's GST invoice to the guest for a stay. hsn_sac_code
 * defaults to 996311 (accommodation services).
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS guest_invoices (
                id CHAR(36) NOT NULL PRIMARY KEY,
                hotel_id CHAR(36) NOT NULL,
                booking_id CHAR(36) NOT NULL,
                invoice_number VARCHAR(50) NOT NULL,
                invoice_date DATE NOT NULL,
                financial_year VARCHAR(9) NOT NULL,
                guest_name VARCHAR(150) NOT NULL,
                guest_gstin VARCHAR(15) NULL,
                guest_address TEXT NULL,
                place_of_supply VARCHAR(100) NULL,
                hsn_sac_code VARCHAR(10) NOT NULL DEFAULT '996311',
                taxable_value DECIMAL(12, 2) NOT NULL DEFAULT 0,
                cgst_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
                cgst_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                sgst_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
                sgst_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                igst_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
                igst_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                total_tax DECIMAL(12, 2) NOT NULL DEFAULT 0,
                grand_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
                amount_in_words VARCHAR(255) NULL,
                status ENUM('draft', 'issued', 'paid', 'cancelled') NOT NULL DEFAULT 'draft',
                pdf_path VARCHAR(255) NULL,
                notes TEXT NULL,
                {$audit},
                UNIQUE KEY uq_guest_invoices_hotel_number (hotel_id, invoice_number),
                CONSTRAINT fk_guest_invoices_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT fk_guest_invoices_booking FOREIGN KEY (booking_id) REFERENCES bookings (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_guest_invoices_hotel_id', 'guest_invoices', ['hotel_id']);
        $this->createIndex($pdo, 'idx_guest_invoices_booking_id', 'guest_invoices', ['booking_id']);
        $this->createIndex($pdo, 'idx_guest_invoices_status', 'guest_invoices', ['status']);
        $this->createIndex($pdo, 'idx_guest_invoices_invoice_date', 'guest_invoices', ['invoice_date']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'guest_invoices');
    }
};
