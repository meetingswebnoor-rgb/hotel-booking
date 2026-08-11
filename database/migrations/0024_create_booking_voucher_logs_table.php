<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Who performed the action is already covered by the created_by audit
 * column, so it isn't duplicated here.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS booking_voucher_logs (
                id CHAR(36) NOT NULL PRIMARY KEY,
                booking_id CHAR(36) NOT NULL,
                action ENUM('generated', 'emailed', 'downloaded', 'resent') NOT NULL,
                file_path VARCHAR(255) NULL,
                recipient_email VARCHAR(191) NULL,
                {$audit},
                CONSTRAINT fk_booking_voucher_logs_booking FOREIGN KEY (booking_id) REFERENCES bookings (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_booking_voucher_logs_booking_id', 'booking_voucher_logs', ['booking_id']);
        $this->createIndex($pdo, 'idx_booking_voucher_logs_action', 'booking_voucher_logs', ['action']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'booking_voucher_logs');
    }
};
