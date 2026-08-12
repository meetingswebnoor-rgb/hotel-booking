<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Marks accounts seeded by DemoUsersSeeder for the public landing
 * page's quick-login. Lets the app treat them differently later
 * (nightly data reset, blocking self-service password changes /
 * deleting core records) without guessing based on email address.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE users
                ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER locked_until
            SQL);

        $this->createIndex($pdo, 'idx_users_is_demo', 'users', ['is_demo']);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE users
                DROP INDEX idx_users_is_demo,
                DROP COLUMN is_demo
            SQL);
    }
};
