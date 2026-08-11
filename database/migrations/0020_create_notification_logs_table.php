<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS notification_logs (
                id CHAR(36) NOT NULL PRIMARY KEY,
                user_id CHAR(36) NULL,
                hotel_id CHAR(36) NULL,
                channel ENUM('email', 'sms', 'push') NOT NULL,
                event_key VARCHAR(100) NOT NULL,
                recipient VARCHAR(191) NOT NULL,
                subject VARCHAR(255) NULL,
                payload JSON NULL,
                status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
                error_message TEXT NULL,
                sent_at DATETIME NULL,
                {$audit},
                CONSTRAINT fk_notification_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT fk_notification_logs_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_notification_logs_user_id', 'notification_logs', ['user_id']);
        $this->createIndex($pdo, 'idx_notification_logs_hotel_id', 'notification_logs', ['hotel_id']);
        $this->createIndex($pdo, 'idx_notification_logs_status', 'notification_logs', ['status']);
        $this->createIndex($pdo, 'idx_notification_logs_event_key', 'notification_logs', ['event_key']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'notification_logs');
    }
};
