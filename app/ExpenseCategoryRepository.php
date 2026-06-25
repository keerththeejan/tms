<?php

declare(strict_types=1);

class ExpenseCategoryRepository
{
    /** @return list<array<string,mixed>> */
    public static function listActive(PDO $pdo, bool $includeInactive = false): array
    {
        $sql = 'SELECT c.*, a.account_name, a.account_code
                FROM expense_categories c
                LEFT JOIN accounts a ON a.id = c.account_id
                WHERE 1=1';
        if (!$includeInactive) {
            $sql .= ' AND c.is_active = 1';
        }
        $sql .= ' ORDER BY c.sort_order ASC, c.name ASC';

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed>|null */
    public static function getById(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT * FROM expense_categories WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public static function getByCode(PDO $pdo, string $code): ?array
    {
        $st = $pdo->prepare('SELECT * FROM expense_categories WHERE code = ? LIMIT 1');
        $st->execute([trim($code)]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string,mixed> $data */
    public static function create(PDO $pdo, array $data): array
    {
        $code = self::slugCode((string) ($data['code'] ?? $data['name'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Category name is required.');
        }
        if (self::getByCode($pdo, $code)) {
            $code .= '_' . substr(uniqid(), -4);
        }

        $st = $pdo->prepare(
            'INSERT INTO expense_categories (code, name, account_id, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([
            $code,
            $name,
            !empty($data['account_id']) ? (int) $data['account_id'] : null,
            !empty($data['is_active']) ? 1 : 1,
            (int) ($data['sort_order'] ?? 500),
        ]);

        return self::getById($pdo, (int) $pdo->lastInsertId()) ?: [];
    }

    /** @param array<string,mixed> $data */
    public static function update(PDO $pdo, int $id, array $data): array
    {
        $cat = self::getById($pdo, $id);
        if (!$cat) {
            throw new RuntimeException('Category not found.');
        }

        $fields = [];
        $params = [];
        foreach (['name', 'account_id', 'is_active', 'sort_order'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $val = $data[$field];
            if ($field === 'is_active') {
                $val = !empty($val) ? 1 : 0;
            } elseif ($field === 'account_id') {
                $val = $val ? (int) $val : null;
            } elseif ($field === 'sort_order') {
                $val = (int) $val;
            }
            $fields[] = "{$field} = ?";
            $params[] = $val;
        }

        if ($fields === []) {
            return $cat;
        }

        $params[] = $id;
        $pdo->prepare('UPDATE expense_categories SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

        return self::getById($pdo, $id) ?: [];
    }

    public static function delete(PDO $pdo, int $id): bool
    {
        $cat = self::getById($pdo, $id);
        if (!$cat) {
            return false;
        }
        if (!empty($cat['is_system'])) {
            throw new RuntimeException('System categories cannot be deleted. Deactivate instead.');
        }

        $st = $pdo->prepare('SELECT COUNT(*) FROM expenses WHERE category_id = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            throw new RuntimeException('Category is in use by expenses.');
        }

        $pdo->prepare('DELETE FROM expense_categories WHERE id = ?')->execute([$id]);

        return true;
    }

    private static function slugCode(string $input): string
    {
        $code = strtolower(trim($input));
        $code = preg_replace('/[^a-z0-9]+/', '_', $code) ?? 'misc';
        $code = trim($code, '_');

        return $code !== '' ? $code : 'misc';
    }
}
