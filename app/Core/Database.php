<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * PDO singleton + small query helpers. Every query goes through a
 * prepared statement — no raw string interpolation of values, ever.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $cfg = config('database');
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['driver'],
                $cfg['host'],
                $cfg['port'],
                $cfg['database'],
                $cfg['charset']
            );

            try {
                self::$connection = new PDO($dsn, $cfg['username'], $cfg['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage(), previous: $e);
            }
        }

        return self::$connection;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(
        string $table,
        array $conditions = [],
        string $columns = '*',
        ?string $orderBy = null,
        ?int $limit = null
    ): array {
        [$where, $params] = self::buildWhere($conditions);
        $sql = "SELECT {$columns} FROM {$table} {$where}";

        if ($orderBy !== null) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        return self::query($sql, $params)->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function first(string $table, array $conditions = [], string $columns = '*'): ?array
    {
        $rows = self::all($table, $conditions, $columns, null, 1);

        return $rows[0] ?? null;
    }

    public static function insert(string $table, array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ":{$c}", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        self::query($sql, $data);

        $lastId = self::connection()->lastInsertId();

        return $lastId !== '' && $lastId !== '0' ? $lastId : (string) ($data['id'] ?? '');
    }

    public static function update(string $table, int|string $id, array $data, string $key = 'id'): int
    {
        $set = implode(', ', array_map(static fn (string $c): string => "{$c} = :{$c}", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$key} = :__key";

        $params = $data;
        $params['__key'] = $id;

        return self::query($sql, $params)->rowCount();
    }

    public static function softDelete(string $table, int|string $id, ?string $deletedBy = null, string $key = 'id'): int
    {
        return self::update($table, $id, [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deletedBy,
        ], $key);
    }

    public static function delete(string $table, int|string $id, string $key = 'id'): int
    {
        return self::query("DELETE FROM {$table} WHERE {$key} = :id", ['id' => $id])->rowCount();
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function buildWhere(array $conditions): array
    {
        if ($conditions === []) {
            return ['', []];
        }

        $clauses = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $clauses[] = "{$column} IS NULL";
                continue;
            }

            $placeholder = 'w_' . str_replace('.', '_', $column);
            $clauses[] = "{$column} = :{$placeholder}";
            $params[$placeholder] = $value;
        }

        return ['WHERE ' . implode(' AND ', $clauses), $params];
    }
}
