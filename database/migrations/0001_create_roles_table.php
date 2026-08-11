<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * level: 0 = highest authority (Super Admin) ... 5 = lowest (view-only).
 * See database/seeders/RoleSeeder.php for the 10 seeded roles.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS roles (
                id CHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                level TINYINT UNSIGNED NOT NULL,
                description VARCHAR(255) NULL,
                {$audit},
                UNIQUE KEY uq_roles_name (name),
                CONSTRAINT chk_roles_level CHECK (level BETWEEN 0 AND 5)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_roles_level', 'roles', ['level']);
        $this->createIndex($pdo, 'idx_roles_is_deleted', 'roles', ['is_deleted']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'roles');
    }
};
