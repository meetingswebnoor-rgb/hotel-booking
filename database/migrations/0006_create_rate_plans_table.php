<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS rate_plans (
                id CHAR(36) NOT NULL PRIMARY KEY,
                hotel_id CHAR(36) NOT NULL,
                plan_name VARCHAR(150) NOT NULL,
                room_type ENUM('Single', 'Double', 'Suite', 'Deluxe') NOT NULL,
                occupancy_type VARCHAR(50) NULL,
                season ENUM('Peak', 'Off-Peak', 'Regular') NOT NULL DEFAULT 'Regular',
                base_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
                {$audit},
                CONSTRAINT fk_rate_plans_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_rate_plans_hotel_id', 'rate_plans', ['hotel_id']);
        $this->createIndex($pdo, 'idx_rate_plans_room_type', 'rate_plans', ['room_type']);
        $this->createIndex($pdo, 'idx_rate_plans_season', 'rate_plans', ['season']);
        $this->createIndex($pdo, 'idx_rate_plans_is_deleted', 'rate_plans', ['is_deleted']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'rate_plans');
    }
};
