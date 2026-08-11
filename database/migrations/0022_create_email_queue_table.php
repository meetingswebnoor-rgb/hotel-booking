<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * related_type/related_id are a lightweight polymorphic pointer (e.g.
 * 'booking'/id, 'invoice'/id) — no FK, since the target table varies.
 * Drained by cron/retry_emails.php.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS email_queue (
                id CHAR(36) NOT NULL PRIMARY KEY,
                template_id CHAR(36) NULL,
                to_email VARCHAR(191) NOT NULL,
                to_name VARCHAR(150) NULL,
                subject VARCHAR(255) NOT NULL,
                body_html MEDIUMTEXT NOT NULL,
                related_type VARCHAR(50) NULL,
                related_id CHAR(36) NULL,
                status ENUM('pending', 'sending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
                attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 3,
                last_error TEXT NULL,
                scheduled_at DATETIME NULL,
                sent_at DATETIME NULL,
                {$audit},
                CONSTRAINT fk_email_queue_template FOREIGN KEY (template_id) REFERENCES email_templates (id)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_email_queue_status', 'email_queue', ['status']);
        $this->createIndex($pdo, 'idx_email_queue_scheduled_at', 'email_queue', ['scheduled_at']);
        $this->createIndex($pdo, 'idx_email_queue_related', 'email_queue', ['related_type', 'related_id']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'email_queue');
    }
};
