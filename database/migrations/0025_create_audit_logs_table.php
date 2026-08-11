<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Append-only system-wide change log — distinct from the per-row
 * created_by/deleted_by audit columns, which only capture the last
 * writer. entity_type/entity_id name the affected row generically
 * (e.g. 'bookings'/id) with no FK, since it points at any table.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS audit_logs (
                id CHAR(36) NOT NULL PRIMARY KEY,
                user_id CHAR(36) NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id CHAR(36) NULL,
                old_values JSON NULL,
                new_values JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                {$audit},
                CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_audit_logs_user_id', 'audit_logs', ['user_id']);
        $this->createIndex($pdo, 'idx_audit_logs_entity', 'audit_logs', ['entity_type', 'entity_id']);
        $this->createIndex($pdo, 'idx_audit_logs_action', 'audit_logs', ['action']);
        $this->createIndex($pdo, 'idx_audit_logs_created_at', 'audit_logs', ['created_at']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'audit_logs');
    }
};
