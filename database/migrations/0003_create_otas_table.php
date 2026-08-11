<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS otas (
                id CHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                commission_pct DECIMAL(5, 2) NOT NULL DEFAULT 15.00,
                settlement_rules TEXT NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                custom_payment_statuses JSON NULL,
                {$audit},
                UNIQUE KEY uq_otas_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_otas_status', 'otas', ['status']);
        $this->createIndex($pdo, 'idx_otas_is_deleted', 'otas', ['is_deleted']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'otas');
    }
};
