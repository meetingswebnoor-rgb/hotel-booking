<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Backs account-level login rate-limiting: after
 * config('auth.max_login_attempts') failures, lock the account until
 * locked_until. Deliberately account-scoped (not IP-scoped) — simple
 * and effective, though it means an attacker who only knows a valid
 * email can lock that account out; add IP-based throttling too if that
 * becomes a real concern.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE users
                ADD COLUMN failed_login_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER remember_token,
                ADD COLUMN locked_until DATETIME NULL AFTER failed_login_attempts
            SQL);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE users
                DROP COLUMN locked_until,
                DROP COLUMN failed_login_attempts
            SQL);
    }
};
