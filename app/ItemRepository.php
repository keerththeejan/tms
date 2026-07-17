<?php

declare(strict_types=1);

final class ItemRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS items (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                item_name VARCHAR(200) NOT NULL,
                unit_rate DECIMAL(12,2) NOT NULL,
                status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_items_name (item_name),
                KEY idx_items_status (status),
                KEY idx_items_rate (unit_rate)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * @param array{q?:string,status?:string,sort?:string,order?:string} $filters
     * @return array{rows:list<array<string,mixed>>,total:int,page:int,pages:int}
     */
    public static function paginate(PDO $pdo, array $filters, int $page, int $limit): array
    {
        $where = ['1=1'];
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($q !== '') {
            $where[] = '(item_name LIKE ? OR CAST(unit_rate AS CHAR) LIKE ? OR status LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);
        $count = $pdo->prepare("SELECT COUNT(*) FROM items WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sortMap = [
            'item_name' => 'item_name',
            'unit_rate' => 'unit_rate',
            'status' => 'status',
            'created_at' => 'created_at',
        ];
        $sort = (string) ($filters['sort'] ?? 'created_at');
        $sortColumn = $sortMap[$sort] ?? 'created_at';
        $order = strtoupper((string) ($filters['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $limit = max(5, min(100, $limit));
        $pages = max(1, (int) ceil($total / $limit));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare(
            "SELECT id, item_name, unit_rate, status, created_at, updated_at
             FROM items
             WHERE {$whereSql}
             ORDER BY {$sortColumn} {$order}, id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    /** @return list<array{id:int,item_name:string,unit_rate:string}> */
    public static function activeOptions(PDO $pdo): array
    {
        $stmt = $pdo->query(
            "SELECT id, item_name, unit_rate
             FROM items
             WHERE status = 'Active'
             ORDER BY item_name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed>|null */
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function nameExists(PDO $pdo, string $name, int $excludeId = 0): bool
    {
        $sql = 'SELECT 1 FROM items WHERE item_name = ?';
        $params = [$name];
        if ($excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(PDO $pdo, string $name, float $rate, string $status): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO items (item_name, unit_rate, status, created_at, updated_at)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([$name, $rate, $status]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, string $name, float $rate, string $status): void
    {
        $stmt = $pdo->prepare(
            'UPDATE items
             SET item_name = ?, unit_rate = ?, status = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?'
        );
        $stmt->execute([$name, $rate, $status, $id]);
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM items WHERE id = ?');
        $stmt->execute([$id]);
    }
}
