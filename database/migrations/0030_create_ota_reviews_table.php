<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS ota_reviews (
                id CHAR(36) NOT NULL PRIMARY KEY,
                ota_id CHAR(36) NOT NULL,
                rating TINYINT UNSIGNED NOT NULL,
                author_name VARCHAR(150) NOT NULL,
                review_text TEXT NOT NULL,
                {$audit},
                CONSTRAINT fk_ota_reviews_ota FOREIGN KEY (ota_id) REFERENCES otas (id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_ota_reviews_ota_id', 'ota_reviews', ['ota_id']);
        $this->createIndex($pdo, 'idx_ota_reviews_is_deleted', 'ota_reviews', ['is_deleted']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'ota_reviews');
    }
};
