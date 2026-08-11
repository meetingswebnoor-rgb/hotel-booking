<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS invoice_email_logs (
                id CHAR(36) NOT NULL PRIMARY KEY,
                invoice_id CHAR(36) NOT NULL,
                recipient_email VARCHAR(191) NOT NULL,
                status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
                error_message TEXT NULL,
                sent_at DATETIME NULL,
                {$audit},
                CONSTRAINT fk_invoice_email_logs_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_invoice_email_logs_invoice_id', 'invoice_email_logs', ['invoice_id']);
        $this->createIndex($pdo, 'idx_invoice_email_logs_status', 'invoice_email_logs', ['status']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'invoice_email_logs');
    }
};
