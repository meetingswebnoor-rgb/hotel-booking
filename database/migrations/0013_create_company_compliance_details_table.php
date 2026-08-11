<?php

declare(strict_types=1);

use App\Core\Migration;

/**
 * Legal/tax/banking profile stamped on invoices. hotel_id NULL = the
 * default (Hotezo's own compliance profile, used on commission_invoices
 * and service_invoices); hotel_id set = an override for that hotel's
 * guest_invoices, if it ever needs different registered details than
 * what's on the hotels table itself.
 */
return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $audit = $this->auditColumns();

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS company_compliance_details (
                id CHAR(36) NOT NULL PRIMARY KEY,
                hotel_id CHAR(36) NULL,
                legal_entity_name VARCHAR(191) NOT NULL,
                gstin VARCHAR(15) NULL,
                pan VARCHAR(10) NULL,
                cin VARCHAR(21) NULL,
                registered_address TEXT NULL,
                state VARCHAR(100) NULL,
                state_code VARCHAR(2) NULL,
                bank_name VARCHAR(150) NULL,
                bank_account_number VARCHAR(30) NULL,
                bank_ifsc VARCHAR(11) NULL,
                bank_account_holder VARCHAR(150) NULL,
                swift_code VARCHAR(11) NULL,
                logo_path VARCHAR(255) NULL,
                signatory_name VARCHAR(150) NULL,
                signatory_designation VARCHAR(100) NULL,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                {$audit},
                CONSTRAINT fk_company_compliance_details_hotel FOREIGN KEY (hotel_id) REFERENCES hotels (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->createIndex($pdo, 'idx_company_compliance_details_hotel_id', 'company_compliance_details', ['hotel_id']);
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'company_compliance_details');
    }
};
