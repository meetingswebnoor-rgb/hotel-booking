<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * assigned_hotels is a denormalized JSON cache of hotel IDs for quick
 * reads; database/migrations/0007_create_user_hotels_table.php is the
 * source of truth for the actual user<->hotel relationships.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS users (
                id CHAR(36) NOT NULL PRIMARY KEY,
                full_name VARCHAR(150) NOT NULL,
                username VARCHAR(50) NOT NULL,
                email VARCHAR(191) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                designation VARCHAR(100) NULL,
                role_id CHAR(36) NOT NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                assigned_hotels JSON NULL,
                hotel_assignment_type ENUM('single', 'multiple', 'all') NOT NULL DEFAULT 'single',
                last_login DATETIME NULL,
                remember_token VARCHAR(100) NULL,
                {$audit},
                UNIQUE KEY uq_users_username (username),
                UNIQUE KEY uq_users_email (email),
                CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_users_role_id', 'users', ['role_id']);
        $this->createIndex($pdo, 'idx_users_status', 'users', ['status']);
        $this->createIndex($pdo, 'idx_users_is_deleted', 'users', ['is_deleted']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'users');
    }
};
