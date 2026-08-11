<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

/**
 * Runs once a day. Hard-deletes soft-deleted rows past the retention
 * window and prunes old log entries.
 *
 * Scheduled via cron, e.g.:
 *   0 3 * * * php /path/to/hotezo/cron/purge_logs.php
 *
 * TODO: implemented alongside the Trash module.
 */

fwrite(STDOUT, '[purge_logs] not implemented yet' . PHP_EOL);
