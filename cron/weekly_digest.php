<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

/**
 * Runs once a week. Emails Super Admin + Hotel Admins a rollup of
 * bookings, commissions, and settlement status across the week.
 *
 * Scheduled via cron, e.g.:
 *   0 7 * * 1 php /path/to/hotezo/cron/weekly_digest.php
 *
 * TODO: implemented alongside the Analytics/Reports module.
 */

fwrite(STDOUT, '[weekly_digest] not implemented yet' . PHP_EOL);
