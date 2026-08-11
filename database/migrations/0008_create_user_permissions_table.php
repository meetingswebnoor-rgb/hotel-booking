<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Per-user permission overrides on top of the role's baseline access
 * (permission_key examples: 'bookings.create', 'invoices.void').
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS user_permissions (
                id CHAR(36) NOT NULL PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                permission_key VARCHAR(100) NOT NULL,
                is_allowed TINYINT(1) NOT NULL DEFAULT 1,
                {$audit},
                UNIQUE KEY uq_user_permissions_user_key (user_id, permission_key),
                CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id) REFERENCES users (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_user_permissions_user_id', 'user_permissions', ['user_id']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'user_permissions');
    }
};
