<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin base model. Feature modules extend this and set $table;
 * all writes flow through here so soft-delete/audit columns stay consistent.
 */
abstract class Model
{
    protected static string $table;

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(array $conditions = []): array
    {
        return Database::all(static::$table, [...$conditions, 'is_deleted' => 0]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int|string $id): ?array
    {
        return Database::first(static::$table, ['id' => $id, 'is_deleted' => 0]);
    }

    public static function create(array $data): string
    {
        $data['id'] ??= uuid();
        $data['created_at'] ??= date('Y-m-d H:i:s');
        $data['updated_at'] ??= date('Y-m-d H:i:s');
        $data['is_deleted'] ??= 0;

        Database::insert(static::$table, $data);

        return (string) $data['id'];
    }

    public static function updateRecord(int|string $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Database::update(static::$table, $id, $data);
    }

    public static function softDelete(int|string $id, null|int|string $deletedBy = null): int
    {
        return Database::softDelete(static::$table, $id, $deletedBy === null ? null : (string) $deletedBy);
    }
}
