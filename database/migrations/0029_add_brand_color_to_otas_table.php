<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * A stable, admin-editable brand color per OTA for the OTA Management
 * cards — distinct from format.js's otaBadgeColor(), which hashes a
 * name into one of a shared palette for places (bookings list, hero
 * mockup) that only ever see an OTA's name, never its record. The OTA
 * Management page has the actual row, so it uses this real column
 * instead of a derived hash.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE otas ADD COLUMN brand_color VARCHAR(7) NOT NULL DEFAULT '#6366F1' AFTER commission_pct");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('ALTER TABLE otas DROP COLUMN brand_color');
    }
};
