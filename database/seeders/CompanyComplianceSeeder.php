<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Seeder;

/**
 * Hotezo's own state-registered billing entities (hotel_id NULL) — the
 * "Billing Entity" choices the commission-invoice generator picks
 * from. Two states are seeded deliberately: one matches a seeded
 * hotel's own state (intra-state, CGST+SGST) and one doesn't
 * (inter-state, IGST), so both GST paths have real data to generate
 * against. Fictional placeholder GSTIN/PAN/bank details, same
 * disclosed-placeholder convention as HotelSeeder's sample hotels.
 */
return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $entities = [
            [
                'legal_entity_name' => 'Hotezo Technologies Private Limited',
                'gstin' => '27HOTZO1234F1Z9',
                'pan' => 'HOTZO1234F',
                'cin' => 'U62099MH2020PTC123456',
                'registered_address' => '4th Floor, Tech Park One, Andheri East',
                'state' => 'Maharashtra',
                'state_code' => '27',
                'bank_name' => 'HDFC Bank',
                'bank_account_number' => '50200012345678',
                'bank_ifsc' => 'HDFC0000123',
                'bank_account_holder' => 'Hotezo Technologies Private Limited',
                'signatory_name' => 'Finance Controller',
                'signatory_designation' => 'Authorized Signatory',
                'is_default' => 1,
            ],
            [
                'legal_entity_name' => 'Hotezo Technologies Private Limited — Karnataka Branch',
                'gstin' => '29HOTZO1234F2Z7',
                'pan' => 'HOTZO1234F',
                'cin' => 'U62099MH2020PTC123456',
                'registered_address' => '2nd Floor, Innovation Hub, Koramangala',
                'state' => 'Karnataka',
                'state_code' => '29',
                'bank_name' => 'HDFC Bank',
                'bank_account_number' => '50200098765432',
                'bank_ifsc' => 'HDFC0004567',
                'bank_account_holder' => 'Hotezo Technologies Private Limited',
                'signatory_name' => 'Finance Controller',
                'signatory_designation' => 'Authorized Signatory',
                'is_default' => 0,
            ],
        ];

        foreach ($entities as $entity) {
            $existing = Database::first('company_compliance_details', [
                'legal_entity_name' => $entity['legal_entity_name'],
                'hotel_id' => null,
            ]);

            if ($existing !== null) {
                continue;
            }

            Database::insert('company_compliance_details', [
                'id' => uuid(),
                'hotel_id' => null,
                'legal_entity_name' => $entity['legal_entity_name'],
                'gstin' => $entity['gstin'],
                'pan' => $entity['pan'],
                'cin' => $entity['cin'],
                'registered_address' => $entity['registered_address'],
                'state' => $entity['state'],
                'state_code' => $entity['state_code'],
                'bank_name' => $entity['bank_name'],
                'bank_account_number' => $entity['bank_account_number'],
                'bank_ifsc' => $entity['bank_ifsc'],
                'bank_account_holder' => $entity['bank_account_holder'],
                'signatory_name' => $entity['signatory_name'],
                'signatory_designation' => $entity['signatory_designation'],
                'is_default' => $entity['is_default'],
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
                'owner_role' => 'super_admin',
                'visibility_scope' => 'global',
            ]);
        }

        $this->log('Seeded 2 Hotezo billing entities.');
    }
};
