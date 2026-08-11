<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS rooms (
                id CHAR(36) NOT NULL PRIMARY KEY,
                hotel_id CHAR(36) NOT NULL,
                room_number VARCHAR(20) NOT NULL,
                room_type ENUM('Single', 'Double', 'Suite', 'Deluxe') NOT NULL,
                pax SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                max_adults SMALLINT UNSIGNED NOT NULL DEFAULT 2,
                max_children SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                base_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
                status ENUM('Available', 'Occupied', 'Maintenance') NOT NULL DEFAULT 'Available',
                images JSON NULL,
                {$audit},
                UNIQUE KEY uq_rooms_hotel_room_number (hotel_id, room_number),
                CONSTRAINT fk_rooms_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_rooms_hotel_id', 'rooms', ['hotel_id']);
        $this->createIndex($pdo, 'idx_rooms_room_type', 'rooms', ['room_type']);
        $this->createIndex($pdo, 'idx_rooms_status', 'rooms', ['status']);
        $this->createIndex($pdo, 'idx_rooms_is_deleted', 'rooms', ['is_deleted']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'rooms');
    }
};
