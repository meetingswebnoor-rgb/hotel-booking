<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * `rooms` here is a JSON array of room lines (room_id, room_type,
 * rate_plan_id, nightly_rate, nights, subtotal) — a booking can span
 * multiple physical rooms. ota_id is nullable for direct/walk-in
 * bookings; guest_email is nullable and used for the confirmation/
 * invoice email flow.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS bookings (
                id CHAR(36) NOT NULL PRIMARY KEY,
                booking_id VARCHAR(30) NOT NULL,
                hotel_id CHAR(36) NOT NULL,
                ota_id CHAR(36) NULL,
                guest_name VARCHAR(150) NOT NULL,
                guest_mobile VARCHAR(20) NOT NULL,
                guest_email VARCHAR(191) NULL,
                booking_date DATE NOT NULL,
                checkin_date DATE NOT NULL,
                checkout_date DATE NOT NULL,
                nights SMALLINT UNSIGNED NOT NULL,
                rooms JSON NOT NULL,
                adults SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                children SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                total_room_rent DECIMAL(12, 2) NOT NULL DEFAULT 0,
                hotel_gst DECIMAL(12, 2) NOT NULL DEFAULT 0,
                tds DECIMAL(12, 2) NOT NULL DEFAULT 0,
                tcs DECIMAL(12, 2) NOT NULL DEFAULT 0,
                ota_commission DECIMAL(12, 2) NOT NULL DEFAULT 0,
                hotezo_commission DECIMAL(12, 2) NOT NULL DEFAULT 0,
                gst_on_commission DECIMAL(12, 2) NOT NULL DEFAULT 0,
                total_commission_taxes DECIMAL(12, 2) NOT NULL DEFAULT 0,
                hotel_earning DECIMAL(12, 2) NOT NULL DEFAULT 0,
                hotel_collection DECIMAL(12, 2) NOT NULL DEFAULT 0,
                hotezo_collection DECIMAL(12, 2) NOT NULL DEFAULT 0,
                status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show', 'rejected')
                    NOT NULL DEFAULT 'pending',
                ota_payment_status ENUM('pending', 'paid', 'hold', 'disputed') NOT NULL DEFAULT 'pending',
                payment_remarks TEXT NULL,
                internal_notes TEXT NULL,
                source ENUM('online', 'ota', 'walkin') NOT NULL DEFAULT 'online',
                checked_in_at DATETIME NULL,
                checked_out_at DATETIME NULL,
                cancelled_at DATETIME NULL,
                cancellation_reason VARCHAR(255) NULL,
                {$audit},
                UNIQUE KEY uq_bookings_booking_id (booking_id),
                CONSTRAINT fk_bookings_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT fk_bookings_ota FOREIGN KEY (ota_id) REFERENCES otas (id)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_bookings_hotel_id', 'bookings', ['hotel_id']);
        $this->createIndex($pdo, 'idx_bookings_ota_id', 'bookings', ['ota_id']);
        $this->createIndex($pdo, 'idx_bookings_status', 'bookings', ['status']);
        $this->createIndex($pdo, 'idx_bookings_ota_payment_status', 'bookings', ['ota_payment_status']);
        $this->createIndex($pdo, 'idx_bookings_checkin_date', 'bookings', ['checkin_date']);
        $this->createIndex($pdo, 'idx_bookings_checkout_date', 'bookings', ['checkout_date']);
        $this->createIndex($pdo, 'idx_bookings_booking_date', 'bookings', ['booking_date']);
        $this->createIndex($pdo, 'idx_bookings_guest_mobile', 'bookings', ['guest_mobile']);
        $this->createIndex($pdo, 'idx_bookings_is_deleted', 'bookings', ['is_deleted']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'bookings');
    }
};
