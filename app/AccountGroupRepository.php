<?php

declare(strict_types=1);

/**
 * Account Groups Repository
 * Manages account groups for Chart of Accounts (BUSY/Tally style)
 */
class AccountGroupRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        AccountingSchemaRepository::ensureSchema($pdo);
    }

    /** Ensure primary account groups exist; returns number of groups created. */
    public static function ensureDefaultGroups(PDO $pdo): int
    {
        self::ensureSchema($pdo);

        return AccountingSchemaRepository::seedAccountGroupsIfEmpty($pdo);
    }

    /** @return list<array<string,mixed>> */
    public static function listGroups(PDO $pdo, bool $includeInactive = false): array
    {
        $sql = 'SELECT ag.*, parent.group_name AS parent_name, parent.group_code AS parent_code
                FROM account_groups ag
                LEFT JOIN account_groups parent ON parent.id = ag.parent_id';
        
        if (!$includeInactive) {
            $sql .= ' WHERE ag.deleted_at IS NULL';
        }
        
        $sql .= ' ORDER BY ag.sort_order ASC, ag.group_name ASC';
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function listByType(PDO $pdo, string $groupType): array
    {
        $st = $pdo->prepare(
            'SELECT ag.*, parent.group_name AS parent_name
             FROM account_groups ag
             LEFT JOIN account_groups parent ON parent.id = ag.parent_id
             WHERE ag.group_type = ? AND ag.deleted_at IS NULL
             ORDER BY ag.sort_order ASC, ag.group_name ASC'
        );
        $st->execute([$groupType]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed>|null */
    public static function getById(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare(
            'SELECT ag.*, parent.group_name AS parent_name
             FROM account_groups ag
             LEFT JOIN account_groups parent ON parent.id = ag.parent_id
             WHERE ag.id = ? AND ag.deleted_at IS NULL
             LIMIT 1'
        );
        $st->execute([$id]);
        $result = $st->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /** @return array<string,mixed>|null */
    public static function getByCode(PDO $pdo, string $code): ?array
    {
        $st = $pdo->prepare(
            'SELECT * FROM account_groups WHERE group_code = ? AND deleted_at IS NULL LIMIT 1'
        );
        $st->execute([$code]);
        $result = $st->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /** @return list<array<string,mixed>> */
    public static function getTree(PDO $pdo): array
    {
        $groups = self::listGroups($pdo);
        $tree = [];
        $indexed = [];

        // Index all groups
        foreach ($groups as $group) {
            $indexed[(int) $group['id']] = $group;
            $indexed[(int) $group['id']]['children'] = [];
        }

        // Build tree structure
        foreach ($indexed as $id => $group) {
            $parentId = (int) ($group['parent_id'] ?? 0);
            if ($parentId > 0 && isset($indexed[$parentId])) {
                $indexed[$parentId]['children'][] = &$indexed[$id];
            } else {
                $tree[] = &$indexed[$id];
            }
        }

        return $tree;
    }

    /** @return array<string,mixed> */
    public static function create(PDO $pdo, array $data): array
    {
        $st = $pdo->prepare(
            'INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $data['group_code'],
            $data['group_name'],
            $data['parent_id'] ?? null,
            $data['group_type'],
            $data['nature'],
            (int) ($data['is_primary'] ?? 0),
            (int) ($data['is_system'] ?? 0),
            (int) ($data['sort_order'] ?? 0),
        ]);
        
        return self::getById($pdo, (int) $pdo->lastInsertId()) ?: [];
    }

    /** @return array<string,mixed> */
    public static function update(PDO $pdo, int $id, array $data): array
    {
        $fields = [];
        $params = [];
        
        foreach (['group_name', 'parent_id', 'group_type', 'nature', 'is_primary', 'is_system', 'sort_order'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return self::getById($pdo, $id) ?: [];
        }
        
        $params[] = $id;
        $sql = 'UPDATE account_groups SET ' . implode(', ', $fields) . ' WHERE id = ?';
        
        $st = $pdo->prepare($sql);
        $st->execute($params);
        
        return self::getById($pdo, $id) ?: [];
    }

    public static function delete(PDO $pdo, int $id, ?int $userId = null): bool
    {
        // Check if group has accounts
        $st = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE account_group_id = ? AND deleted_at IS NULL');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            throw new RuntimeException('Cannot delete account group that contains accounts.');
        }

        // Check if group has children
        $st = $pdo->prepare('SELECT COUNT(*) FROM account_groups WHERE parent_id = ? AND deleted_at IS NULL');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            throw new RuntimeException('Cannot delete account group that has sub-groups.');
        }

        $st = $pdo->prepare('UPDATE account_groups SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?');
        return $st->execute([$id]);
    }
}
