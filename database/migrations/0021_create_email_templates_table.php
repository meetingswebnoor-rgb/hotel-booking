<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * hotel_id NULL = global template; set it to override a template for
 * one hotel. Note: MySQL/MariaDB treat NULLs as distinct in UNIQUE
 * keys, so the (template_key, hotel_id) key doesn't stop duplicate
 * global (hotel_id NULL) templates by itself — enforce that at the
 * application layer.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS email_templates (
                id CHAR(36) NOT NULL PRIMARY KEY,
                template_key VARCHAR(100) NOT NULL,
                hotel_id CHAR(36) NULL,
                subject VARCHAR(255) NOT NULL,
                body_html MEDIUMTEXT NOT NULL,
                body_text MEDIUMTEXT NULL,
                available_variables JSON NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                {$audit},
                UNIQUE KEY uq_email_templates_key_hotel (template_key, hotel_id),
                CONSTRAINT fk_email_templates_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_email_templates_hotel_id', 'email_templates', ['hotel_id']);
        $this->createIndex($pdo, 'idx_email_templates_is_active', 'email_templates', ['is_active']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'email_templates');
    }
};
