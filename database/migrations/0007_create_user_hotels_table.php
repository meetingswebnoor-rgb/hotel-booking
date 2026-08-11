<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Source of truth for user<->hotel assignment (users.assigned_hotels
 * is a denormalized JSON cache for quick reads).
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS user_hotels (
                id CHAR(36) NOT NULL PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                hotel_id CHAR(36) NOT NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                {$audit},
                UNIQUE KEY uq_user_hotels_user_hotel (user_id, hotel_id),
                CONSTRAINT fk_user_hotels_user FOREIGN KEY (user_id) REFERENCES users (id)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_user_hotels_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_user_hotels_user_id', 'user_hotels', ['user_id']);
        $this->createIndex($pdo, 'idx_user_hotels_hotel_id', 'user_hotels', ['hotel_id']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'user_hotels');
    }
};
