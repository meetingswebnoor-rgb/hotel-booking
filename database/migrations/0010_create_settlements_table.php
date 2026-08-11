<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * One row per settlement run for a hotel over a period (Weekly/Monthly/
 * On-Demand, matching hotels.settlement_type). commission_invoices can
 * reference a settlement to bill Hotezo's commission for that period.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS settlements (
                id CHAR(36) NOT NULL PRIMARY KEY,
                hotel_id CHAR(36) NOT NULL,
                period_start DATE NOT NULL,
                period_end DATE NOT NULL,
                settlement_type ENUM('Weekly', 'Monthly', 'On-Demand') NOT NULL,
                total_bookings INT UNSIGNED NOT NULL DEFAULT 0,
                total_room_rent DECIMAL(12, 2) NOT NULL DEFAULT 0,
                total_ota_commission DECIMAL(12, 2) NOT NULL DEFAULT 0,
                total_hotezo_commission DECIMAL(12, 2) NOT NULL DEFAULT 0,
                total_taxes DECIMAL(12, 2) NOT NULL DEFAULT 0,
                net_payable DECIMAL(12, 2) NOT NULL DEFAULT 0,
                status ENUM('draft', 'pending', 'processing', 'paid', 'disputed') NOT NULL DEFAULT 'draft',
                payment_reference VARCHAR(100) NULL,
                paid_at DATETIME NULL,
                notes TEXT NULL,
                {$audit},
                CONSTRAINT fk_settlements_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_settlements_hotel_id', 'settlements', ['hotel_id']);
        $this->createIndex($pdo, 'idx_settlements_status', 'settlements', ['status']);
        $this->createIndex($pdo, 'idx_settlements_period', 'settlements', ['period_start', 'period_end']);
        $this->createIndex($pdo, 'idx_settlements_is_deleted', 'settlements', ['is_deleted']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'settlements');
    }
};
