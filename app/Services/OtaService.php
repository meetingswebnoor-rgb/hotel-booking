<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Validator;
use App\Models\Ota;
use App\Models\OtaReview;

/**
 * OTA partner records and their reviews. custom_payment_statuses is
 * stored as a JSON array of extra option labels (e.g. "Escrow Hold")
 * an OTA needs beyond the base Pending/Paid/Hold/Disputed set — the
 * booking form merges them in for whichever OTA is selected (see
 * BookingController::save() and public/assets/js/booking-form.js).
 */
final class OtaService
{
    public const STATUSES = ['active', 'inactive'];
    private const HEX_COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    /**
     * @param array<string, mixed> $input
     * @return array<string, array<int, string>> empty when valid
     */
    public static function validate(array $input, ?string $excludeId): array
    {
        $rules = [
            'name' => 'required|max:100',
            'commission_pct' => 'required|numeric',
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ];
        $errors = Validator::make($input, $rules)->errors();

        $commission = (float) ($input['commission_pct'] ?? -1);
        if ($commission < 0 || $commission > 100) {
            $errors['commission_pct'][] = 'Commission must be between 0 and 100.';
        }

        $color = trim((string) ($input['brand_color'] ?? ''));
        if ($color !== '' && preg_match(self::HEX_COLOR_PATTERN, $color) !== 1) {
            $errors['brand_color'][] = 'Brand color must be a hex value like #6366F1.';
        }

        $name = trim((string) ($input['name'] ?? ''));
        if ($name !== '') {
            $dupe = Database::first('otas', ['name' => $name, 'is_deleted' => 0]);
            if ($dupe !== null && ($excludeId === null || $dupe['id'] !== $excludeId)) {
                $errors['name'][] = 'An OTA with this name already exists.';
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input already-validated
     * @param array<int, string> $customPaymentStatuses free-text labels beyond the base set, deduped/trimmed
     * @param array<string, mixed>|null $existing
     */
    public static function save(array $input, array $customPaymentStatuses, ?array $existing, ?string $ownerRole): string
    {
        $data = [
            'name' => sanitize(trim((string) $input['name'])),
            'commission_pct' => round((float) $input['commission_pct'], 2),
            'brand_color' => trim((string) ($input['brand_color'] ?? '')) ?: '#6366F1',
            'settlement_rules' => self::nullableSanitized($input['settlement_rules'] ?? null),
            'status' => (string) $input['status'],
            'custom_payment_statuses' => json_encode(array_values($customPaymentStatuses)),
        ];

        if ($existing === null) {
            $data['owner_role'] = $ownerRole;
            $data['visibility_scope'] = 'global';

            return Ota::create($data);
        }

        Ota::updateRecord($existing['id'], $data);

        return (string) $existing['id'];
    }

    public static function delete(string $otaId, null|int|string $deletedBy): void
    {
        Ota::softDelete($otaId, $deletedBy);
    }

    /**
     * Free-text labels typed into the OTA modal's tag input, arriving
     * as a JSON-encoded array in a hidden field — trimmed, emptied
     * entries dropped, capped at a sane length/count so one bad paste
     * can't blow up the column.
     *
     * @return array<int, string>
     */
    public static function parseCustomPaymentStatuses(mixed $raw): array
    {
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded)) {
            return [];
        }

        $labels = [];
        foreach ($decoded as $label) {
            $clean = trim((string) $label);
            if ($clean !== '' && mb_strlen($clean) <= 40) {
                $labels[] = $clean;
            }
        }

        return array_slice(array_values(array_unique($labels)), 0, 20);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, array<int, string>> empty when valid
     */
    public static function validateReview(array $input): array
    {
        $rules = [
            'author_name' => 'required|max:150',
            'review_text' => 'required|max:2000',
            'rating' => 'required|numeric',
        ];
        $errors = Validator::make($input, $rules)->errors();

        $rating = (int) ($input['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            $errors['rating'][] = 'Rating must be between 1 and 5.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input already-validated
     */
    public static function saveReview(string $otaId, array $input, ?string $ownerRole): string
    {
        return OtaReview::create([
            'ota_id' => $otaId,
            'rating' => (int) $input['rating'],
            'author_name' => sanitize(trim((string) $input['author_name'])),
            'review_text' => sanitize(trim((string) $input['review_text'])),
            'owner_role' => $ownerRole,
            'visibility_scope' => 'global',
        ]);
    }

    public static function deleteReview(string $reviewId, null|int|string $deletedBy): void
    {
        OtaReview::softDelete($reviewId, $deletedBy);
    }

    private static function nullableSanitized(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : sanitize($trimmed);
    }
}
