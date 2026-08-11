<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS hotels (
                id CHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(191) NOT NULL,
                slug VARCHAR(191) NOT NULL,
                address TEXT NULL,
                city VARCHAR(100) NULL,
                country VARCHAR(100) NOT NULL DEFAULT 'India',
                gst_number VARCHAR(15) NULL,
                pan VARCHAR(10) NULL,
                contact_person VARCHAR(150) NULL,
                mobile VARCHAR(20) NULL,
                email VARCHAR(191) NULL,
                bank_account VARCHAR(30) NULL,
                ifsc VARCHAR(11) NULL,
                account_holder VARCHAR(150) NULL,
                commission_pct DECIMAL(5, 2) NOT NULL DEFAULT 15.00,
                settlement_type ENUM('Weekly', 'Monthly', 'On-Demand') NOT NULL DEFAULT 'Monthly',
                gst_type ENUM('Registered', 'Unregistered') NOT NULL DEFAULT 'Unregistered',
                status ENUM('active', 'inactive', 'pending_approval') NOT NULL DEFAULT 'pending_approval',
                hero_image VARCHAR(255) NULL,
                gallery JSON NULL,
                {$audit},
                UNIQUE KEY uq_hotels_slug (slug),
                CONSTRAINT chk_hotels_commission_pct CHECK (commission_pct BETWEEN 5 AND 50)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_hotels_city', 'hotels', ['city']);
        $this->createIndex($pdo, 'idx_hotels_status', 'hotels', ['status']);
        $this->createIndex($pdo, 'idx_hotels_is_deleted', 'hotels', ['is_deleted']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'hotels');
    }
};
