<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base class for numbered migration files under database/migrations/.
 * Each file returns `new class extends Migration { ... }`.
 */
abstract class Migration
{
    abstract public function up(PDO $pdo): void;

    abstract public function down(PDO $pdo): void;

    /**
     * The audit columns every table carries per the Hotezo conventions:
     * created_at/updated_at, created_by/deleted_by (informational —
     * intentionally NOT foreign-keyed, see note below), soft-delete
     * flags, and coarse ownership/visibility scoping.
     *
     * created_by/deleted_by deliberately have no FK constraint: nearly
     * every table would need one, which forces a strict dependency
     * order against `users` and makes hard-deleting a user (Trash
     * module) a cascading nightmare. They're an audit trail, not a
     * relational integrity requirement.
     */
    protected function auditColumns(): string
    {
        return <<<SQL
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by CHAR(36) NULL,
            is_deleted TINYINT(1) NOT NULL DEFAULT 0,
            deleted_at DATETIME NULL,
            deleted_by CHAR(36) NULL,
            owner_role VARCHAR(30) NULL,
            visibility_scope VARCHAR(30) NULL DEFAULT 'hotel'
            SQL;
    }

    protected function createIndex(PDO $pdo, string $indexName, string $table, array $columns): void
    {
        $cols = implode(', ', $columns);
        $pdo->exec("CREATE INDEX {$indexName} ON {$table} ({$cols})");
    }

    protected function dropTable(PDO $pdo, string $table): void
    {
        $pdo->exec("DROP TABLE IF EXISTS {$table}");
    }
}
