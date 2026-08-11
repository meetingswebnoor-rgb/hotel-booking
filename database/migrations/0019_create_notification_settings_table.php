<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * user_id NULL = hotel-wide default for that event; hotel_id NULL =
 * platform-wide default. event_key examples: 'booking.created',
 * 'settlement.paid'.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS notification_settings (
                id CHAR(36) NOT NULL PRIMARY KEY,
                user_id CHAR(36) NULL,
                hotel_id CHAR(36) NULL,
                channel ENUM('email', 'sms', 'push') NOT NULL DEFAULT 'email',
                event_key VARCHAR(100) NOT NULL,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                {$audit},
                CONSTRAINT fk_notification_settings_user FOREIGN KEY (user_id) REFERENCES users (id)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_notification_settings_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_notification_settings_user_id', 'notification_settings', ['user_id']);
        $this->createIndex($pdo, 'idx_notification_settings_hotel_id', 'notification_settings', ['hotel_id']);
        $this->createIndex($pdo, 'idx_notification_settings_event_key', 'notification_settings', ['event_key']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'notification_settings');
    }
};
