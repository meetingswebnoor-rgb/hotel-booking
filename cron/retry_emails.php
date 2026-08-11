<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

/**
 * Runs frequently. Retries queued emails (booking confirmations,
 * invoices, digests) that failed to send.
 *
 * Scheduled via cron to run every 5 minutes:
 *   crontab expression: every 5 minutes, all hours, all days
 *   command: php /path/to/hotezo/cron/retry_emails.php
 *
 * TODO: implemented alongside the EmailQueue service.
 */

fwrite(STDOUT, '[retry_emails] not implemented yet' . PHP_EOL);
