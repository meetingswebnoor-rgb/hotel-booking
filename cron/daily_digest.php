<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

/**
 * Runs once a day. Emails each Hotel Admin a summary of the previous
 * day's bookings, revenue, and pending settlements.
 *
 * Scheduled via cron, e.g.:
 *   0 7 * * * php /path/to/hotezo/cron/daily_digest.php
 *
 * TODO: implemented alongside the Bookings/Settlements module.
 */

fwrite(STDOUT, '[daily_digest] not implemented yet' . PHP_EOL);
