<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Runs numbered migration files from database/migrations/, tracking
 * what's already been applied in a `migrations` table (batch-numbered,
 * like Laravel's, so an entire run can be rolled back together).
 */
final class Migrator
{
    private PDO $pdo;

    public function __construct(private readonly string $migrationsPath)
    {
        $this->pdo = Database::connection();
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(191) NOT NULL,
                batch INT UNSIGNED NOT NULL,
                run_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_migrations_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    /**
     * @return array<int, string> absolute file paths, sorted by filename
     */
    private function files(): array
    {
        $files = glob(rtrim($this->migrationsPath, '/') . '/*.php') ?: [];
        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * @return array<int, string> migration names already recorded as run
     */
    private function ran(): array
    {
        return $this->pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    }

    private function load(string $file): Migration
    {
        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new RuntimeException(basename($file) . ' must return an App\\Core\\Migration instance.');
        }

        return $migration;
    }

    public function migrate(): void
    {
        $ran = $this->ran();
        $nextBatch = ((int) $this->pdo->query('SELECT MAX(batch) FROM migrations')->fetchColumn()) + 1;
        $applied = 0;

        foreach ($this->files() as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $ran, true)) {
                continue;
            }

            $this->log("Migrating: {$name}");
            $this->load($file)->up($this->pdo);

            $stmt = $this->pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (:m, :b)');
            $stmt->execute(['m' => $name, 'b' => $nextBatch]);

            $this->log("Migrated:  {$name}");
            $applied++;
        }

        $this->log($applied === 0 ? 'Nothing to migrate.' : "Migrated {$applied} file(s).");
    }

    public function rollback(): void
    {
        $lastBatch = (int) $this->pdo->query('SELECT MAX(batch) FROM migrations')->fetchColumn();

        if ($lastBatch === 0) {
            $this->log('Nothing to roll back.');

            return;
        }

        $stmt = $this->pdo->prepare('SELECT migration FROM migrations WHERE batch = :b ORDER BY id DESC');
        $stmt->execute(['b' => $lastBatch]);
        $names = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($names as $name) {
            $file = rtrim($this->migrationsPath, '/') . "/{$name}.php";

            if (is_file($file)) {
                $this->log("Rolling back: {$name}");
                $this->load($file)->down($this->pdo);
            } else {
                fwrite(STDERR, "Missing migration file for {$name}, skipping down().\n");
            }

            $del = $this->pdo->prepare('DELETE FROM migrations WHERE migration = :m');
            $del->execute(['m' => $name]);
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $this->log('Rolled back ' . count($names) . ' file(s).');
    }

    /**
     * Drops every table currently in the database and re-runs
     * migrate() from a clean slate. Deliberately introspects the real
     * schema (SHOW TABLES) rather than calling each migration's
     * down() in reverse — that would break the moment a migration is
     * added that's never actually been applied yet (down() assumes
     * up() already ran). Safe here because this database is dedicated
     * entirely to this migration set.
     */
    public function fresh(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->ensureMigrationsTable();
        $this->migrate();
    }

    public function status(): void
    {
        $ran = $this->ran();

        foreach ($this->files() as $file) {
            $name = basename($file, '.php');
            $mark = in_array($name, $ran, true) ? '[x]' : '[ ]';
            $this->log("{$mark} {$name}");
        }
    }

    private function log(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}
