<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base class for seeder files under database/seeders/.
 * Each file returns `new class extends Seeder { ... }`.
 */
abstract class Seeder
{
    abstract public function run(PDO $pdo): void;

    protected function log(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
