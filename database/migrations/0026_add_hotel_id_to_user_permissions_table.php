<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Permission overrides can now be scoped per-hotel (e.g. "allow
 * settlements.approve for this user, but only for hotel X"), not just
 * globally. hotel_id NULL = a global override for that user.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE user_permissions
                DROP INDEX uq_user_permissions_user_key,
                ADD COLUMN hotel_id CHAR(36) NULL AFTER user_id,
                ADD UNIQUE KEY uq_user_permissions_user_key_hotel (user_id, permission_key, hotel_id),
                ADD CONSTRAINT fk_user_permissions_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            SQL);

        $this->createIndex($pdo, 'idx_user_permissions_hotel_id', 'user_permissions', ['hotel_id']);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            ALTER TABLE user_permissions
                DROP FOREIGN KEY fk_user_permissions_hotel,
                DROP INDEX uq_user_permissions_user_key_hotel,
                DROP INDEX idx_user_permissions_hotel_id,
                DROP COLUMN hotel_id,
                ADD UNIQUE KEY uq_user_permissions_user_key (user_id, permission_key)
            SQL);
    }
};
