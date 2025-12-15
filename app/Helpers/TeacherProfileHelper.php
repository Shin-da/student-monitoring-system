<?php
declare(strict_types=1);

namespace Helpers;

use PDO;

class TeacherProfileHelper
{
    private static ?array $columnCache = null;

    /**
     * Ensure a teacher/adviser profile exists for the provided user.
     *
     * @param array{
     *     user_id:int,
     *     name?:string,
     *     employee_id?:?string,
     *     department?:?string,
     *     specialization?:?string,
     *     hire_date?:?string,
     *     is_adviser?:int
     * } $meta
     */
    public static function save(PDO $pdo, array $meta): void
    {
        $userId = (int)($meta['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid teacher user_id supplied.');
        }

        $columns = self::getColumnMap($pdo);
        if (empty($columns)) {
            return;
        }

        $data = [
            'user_id' => $userId,
            'is_adviser' => (int)($meta['is_adviser'] ?? 0),
        ];

        self::maybeAssign($data, $columns, 'employee_id', $meta['employee_id'] ?? null);
        self::maybeAssign($data, $columns, 'department', $meta['department'] ?? null);
        self::maybeAssign($data, $columns, 'specialization', $meta['specialization'] ?? null);
        self::maybeAssign($data, $columns, 'hire_date', $meta['hire_date'] ?? null);
        if (isset($columns['teacher_name'])) {
            $data['teacher_name'] = $meta['name'] ?? $meta['teacher_name'] ?? null;
        }

        $existingId = self::getExistingId($pdo, $userId);
        if ($existingId !== null) {
            self::update($pdo, $existingId, $data);
            self::removeDuplicateRows($pdo, $userId, $existingId);
            return;
        }

        if (self::needsManualId($columns)) {
            $data['id'] = self::nextId($pdo);
        }

        self::insert($pdo, $data);
        $newId = (int)($data['id'] ?? $pdo->lastInsertId());
        self::removeDuplicateRows($pdo, $userId, $newId);
    }

    private static function maybeAssign(array &$data, array $columns, string $column, $value): void
    {
        if (isset($columns[$column])) {
            $data[$column] = $value;
        }
    }

    private static function getExistingId(PDO $pdo, int $userId): ?int
    {
        $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (int)$value;
    }

    private static function update(PDO $pdo, int $id, array $data): void
    {
        $setParts = [];
        $params = [];

        foreach ($data as $column => $value) {
            if ($column === 'user_id' || $column === 'id') {
                continue;
            }
            $placeholder = ':set_' . $column;
            $setParts[] = sprintf('%s = %s', $column, $placeholder);
            $params[$placeholder] = $value;
        }

        if (empty($setParts)) {
            return;
        }

        $params[':id'] = $id;
        $sql = 'UPDATE teachers SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    private static function insert(PDO $pdo, array $data): void
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $params = array_combine($placeholders, array_values($data));

        $sql = sprintf(
            'INSERT INTO teachers (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    private static function needsManualId(array $columns): bool
    {
        if (!isset($columns['id'])) {
            return false;
        }

        return stripos($columns['id']['Extra'] ?? '', 'auto_increment') === false;
    }

    private static function nextId(PDO $pdo): int
    {
        $stmt = $pdo->query('SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM teachers');
        $next = (int)$stmt->fetchColumn();
        return max(1, $next);
    }

    private static function removeDuplicateRows(PDO $pdo, int $userId, int $keepId): void
    {
        $stmt = $pdo->prepare('DELETE FROM teachers WHERE user_id = :user_id AND id != :keep_id');
        $stmt->execute([
            'user_id' => $userId,
            'keep_id' => $keepId,
        ]);
    }

    private static function getColumnMap(PDO $pdo): array
    {
        if (self::$columnCache !== null) {
            return self::$columnCache;
        }

        try {
            $stmt = $pdo->query('SHOW COLUMNS FROM teachers');
        } catch (\Throwable $e) {
            return [];
        }
        $columns = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[strtolower($column['Field'])] = $column;
        }

        self::$columnCache = $columns;
        return $columns;
    }
}


