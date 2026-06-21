<?php
declare(strict_types=1);

/**
 * Fixed three-branch master: Colombo (1), Kilinochchi (2), Mullaitivu (3).
 * Merges duplicates, remaps FKs, and enforces canonical names/codes.
 */
final class BranchFixedMaster
{
    /** @var array<int,array{id:int,name:string,code:string,settings_slot:int,is_main:int}> */
    public const CANONICAL = [
        1 => ['id' => 1, 'name' => 'Colombo', 'code' => 'COL', 'settings_slot' => 0, 'is_main' => 0],
        2 => ['id' => 2, 'name' => 'Kilinochchi', 'code' => 'KIL', 'settings_slot' => 1, 'is_main' => 1],
        3 => ['id' => 3, 'name' => 'Mullaitivu', 'code' => 'MUL', 'settings_slot' => 2, 'is_main' => 0],
    ];

    private static bool $ensured = false;

    public static function ensure(\PDO $pdo): void
    {
        if (self::$ensured) {
            return;
        }

        BranchRepository::applySchemaColumns($pdo);

        try {
            try {
                BranchMergeService::execute($pdo);
        } catch (\Throwable $e) {
            try {
                self::mergeOrphansIntoCanonical($pdo);
            } catch (\Throwable $e2) {
                // best-effort orphan merge; do not block app boot
            }
        }

            self::ensureCanonicalRowsExist($pdo);
            self::normalizePrimaryKeys($pdo);
            self::ensureCanonicalRowsExist($pdo);
            self::finalizeCanonicalRows($pdo);
            self::purgeExtraBranches($pdo);
            self::$ensured = true;
        } catch (\Throwable $e) {
            self::$ensured = false;
            throw $e;
        }
    }

    /** Re-run canonical row checks when dropdowns need all three fixed branches. */
    public static function ensureRowsPresent(\PDO $pdo): void
    {
        if (!self::$ensured) {
            self::ensure($pdo);
            return;
        }
        self::ensureCanonicalRowsExist($pdo);
        self::normalizePrimaryKeys($pdo);
        self::ensureCanonicalRowsExist($pdo);
        self::finalizeCanonicalRows($pdo);
        $cnt = (int)$pdo->query('SELECT COUNT(*) FROM branches WHERE id IN (1,2,3) AND is_active = 1')->fetchColumn();
        if ($cnt < 3) {
            self::mergeOrphansIntoCanonical($pdo);
            self::ensureCanonicalRowsExist($pdo);
            self::normalizePrimaryKeys($pdo);
            self::ensureCanonicalRowsExist($pdo);
            self::finalizeCanonicalRows($pdo);
            self::purgeExtraBranches($pdo);
        }
    }

    /** @return int[] */
    public static function allowedIds(): array
    {
        return [1, 2, 3];
    }

    public static function isAllowedId(int $id): bool
    {
        return in_array($id, self::allowedIds(), true);
    }

    public static function fixedOrderSql(string $idColumn = 'id'): string
    {
        return 'CASE ' . $idColumn . ' WHEN 1 THEN 1 WHEN 2 THEN 2 WHEN 3 THEN 3 ELSE 99 END';
    }

    public static function nameToKey(string $name): ?string
    {
        $n = function_exists('mb_strtolower') ? mb_strtolower(trim($name), 'UTF-8') : strtolower(trim($name));
        if ($n === '') {
            return null;
        }
        if ($n === 'main' || $n === 'main branch' || str_contains($n, 'main branch') || str_contains($n, '(kili)')) {
            return 'kilinochchi';
        }
        if ($n === 'colombo' || str_starts_with($n, 'colombo') || $n === 'col' || str_contains($n, 'kolumbu')) {
            return 'colombo';
        }
        if (str_contains($n, 'kilinochchi') || str_contains($n, 'kilinochi') || str_contains($n, 'kili')) {
            return 'kilinochchi';
        }
        if (str_contains($n, 'mullaitivu') || str_contains($n, 'mullaithivu') || str_contains($n, 'mullativu') || str_contains($n, 'mullai')) {
            return 'mullaitivu';
        }
        if (preg_match('/branch\s*[a4]/i', $n)) {
            return 'colombo';
        }
        if (preg_match('/branch\s*[b5]/i', $n)) {
            return 'kilinochchi';
        }
        return null;
    }

    public static function keyToTargetId(string $key): int
    {
        return match ($key) {
            'colombo' => 1,
            'kilinochchi' => 2,
            'mullaitivu' => 3,
            default => 1,
        };
    }

    private static function mergeOrphansIntoCanonical(\PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, name FROM branches ORDER BY id ASC')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $keepers = [];
        foreach (self::CANONICAL as $targetId => $meta) {
            $keepers[$targetId] = 0;
        }
        foreach ($rows as $r) {
            $key = self::nameToKey((string)($r['name'] ?? ''));
            if ($key === null) {
                $key = 'colombo';
            }
            $targetId = self::keyToTargetId($key);
            $rid = (int)$r['id'];
            if ($keepers[$targetId] <= 0) {
                $keepers[$targetId] = $rid;
            } elseif ($keepers[$targetId] !== $rid) {
                self::remapForeignKeys($pdo, $rid, $keepers[$targetId]);
                $pdo->prepare('DELETE FROM branches WHERE id = ?')->execute([$rid]);
            }
        }
    }

    private static function canonicalKeyForId(int $id): string
    {
        return match ($id) {
            1 => 'colombo',
            2 => 'kilinochchi',
            3 => 'mullaitivu',
            default => 'colombo',
        };
    }

    private static function findBranchIdForKey(\PDO $pdo, string $key): int
    {
        $rows = $pdo->query('SELECT id, name, code FROM branches ORDER BY id ASC')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $wantCode = strtoupper(self::CANONICAL[self::keyToTargetId($key)]['code']);
        foreach ($rows as $r) {
            if (self::nameToKey((string)($r['name'] ?? '')) === $key) {
                return (int)$r['id'];
            }
            if (strtoupper(trim((string)($r['code'] ?? ''))) === $wantCode) {
                return (int)$r['id'];
            }
        }
        return 0;
    }

    private static function ensureCanonicalRowsExist(\PDO $pdo): void
    {
        foreach (self::CANONICAL as $targetId => $meta) {
            $st = $pdo->prepare('SELECT id FROM branches WHERE id = ? LIMIT 1');
            $st->execute([$targetId]);
            if ($st->fetch()) {
                continue;
            }
            $legacyId = self::findBranchIdForKey($pdo, self::canonicalKeyForId($targetId));
            if ($legacyId > 0) {
                self::moveBranchRowToId($pdo, $legacyId, $targetId);
                continue;
            }
            $byCode = $pdo->prepare('SELECT id FROM branches WHERE UPPER(code) = ? ORDER BY id ASC LIMIT 1');
            $byCode->execute([strtoupper($meta['code'])]);
            $row = $byCode->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                self::moveBranchRowToId($pdo, (int)$row['id'], $targetId);
                continue;
            }
            $byName = $pdo->prepare('SELECT id FROM branches WHERE LOWER(name) LIKE ? ORDER BY id ASC LIMIT 1');
            $like = '%' . strtolower(substr($meta['name'], 0, 5)) . '%';
            $byName->execute([$like]);
            $row = $byName->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                self::moveBranchRowToId($pdo, (int)$row['id'], $targetId);
                continue;
            }
            $pdo->prepare(
                'INSERT INTO branches (id, name, code, is_main, is_active, is_default, settings_slot, location)
                 VALUES (?,?,?,?,1,?,?,NULL)'
            )->execute([
                $targetId,
                $meta['name'],
                $meta['code'],
                $meta['is_main'],
                $targetId === 1 ? 1 : 0,
                $meta['settings_slot'],
            ]);
        }
    }

    private static function normalizePrimaryKeys(\PDO $pdo): void
    {
        $map = [];
        foreach (self::CANONICAL as $targetId => $meta) {
            $st = $pdo->prepare(
                'SELECT id FROM branches WHERE id = ? OR UPPER(code) = ? OR LOWER(name) LIKE ? ORDER BY CASE WHEN id = ? THEN 0 ELSE 1 END, id ASC LIMIT 1'
            );
            $st->execute([$targetId, strtoupper($meta['code']), '%' . strtolower(substr($meta['name'], 0, 4)) . '%', $targetId]);
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $map[$targetId] = (int)$row['id'];
            }
        }
        foreach ($map as $targetId => $currentId) {
            if ($currentId !== $targetId) {
                self::moveBranchRowToId($pdo, $currentId, $targetId);
            }
        }
    }

    private static function moveBranchRowToId(\PDO $pdo, int $fromId, int $toId): void
    {
        if ($fromId === $toId || $fromId <= 0 || $toId <= 0) {
            return;
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        try {
            $tempFrom = 50000 + $fromId;
            $tempTo = 50000 + $toId;

            $occupant = $pdo->prepare('SELECT id FROM branches WHERE id = ? LIMIT 1');
            $occupant->execute([$toId]);
            if ($occupant->fetch()) {
                self::remapForeignKeys($pdo, $toId, $tempTo);
                $pdo->prepare('UPDATE branches SET id = ? WHERE id = ?')->execute([$tempTo, $toId]);
            }

            self::remapForeignKeys($pdo, $fromId, $tempFrom);
            $pdo->prepare('UPDATE branches SET id = ? WHERE id = ?')->execute([$tempFrom, $fromId]);

            self::remapForeignKeys($pdo, $tempFrom, $toId);
            $pdo->prepare('UPDATE branches SET id = ? WHERE id = ?')->execute([$toId, $tempFrom]);

            $chkTemp = $pdo->prepare('SELECT id FROM branches WHERE id = ? LIMIT 1');
            $chkTemp->execute([$tempTo]);
            if ($chkTemp->fetch()) {
                self::remapForeignKeys($pdo, $tempTo, $fromId);
                $pdo->prepare('UPDATE branches SET id = ? WHERE id = ?')->execute([$fromId, $tempTo]);
            } else {
                $pdo->prepare('DELETE FROM branches WHERE id = ?')->execute([$tempTo]);
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private static function finalizeCanonicalRows(\PDO $pdo): void
    {
        $pdo->exec('UPDATE branches SET is_main = 0 WHERE id IN (1,2,3)');
        foreach (self::CANONICAL as $targetId => $meta) {
            $pdo->prepare(
                'UPDATE branches SET name = ?, code = ?, settings_slot = ?, is_main = ?, is_active = 1 WHERE id = ?'
            )->execute([
                $meta['name'],
                $meta['code'],
                $meta['settings_slot'],
                $meta['is_main'],
                $targetId,
            ]);
        }
        $def = $pdo->query('SELECT COUNT(*) FROM branches WHERE is_default = 1 AND id IN (1,2,3)')->fetchColumn();
        if ((int)$def === 0) {
            $pdo->exec('UPDATE branches SET is_default = 0 WHERE id IN (1,2,3)');
            $pdo->exec('UPDATE branches SET is_default = 1 WHERE id = 1');
        }
        if (self::tableExists($pdo, 'users')) {
            $pdo->exec('UPDATE users u INNER JOIN branches b ON b.id = u.branch_id SET u.is_main_branch = b.is_main');
        }
    }

    private static function purgeExtraBranches(\PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, name FROM branches WHERE id NOT IN (1,2,3) ORDER BY id ASC')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $r) {
            $fromId = (int)$r['id'];
            $key = self::nameToKey((string)($r['name'] ?? '')) ?? 'colombo';
            $toId = self::keyToTargetId($key);
            self::remapForeignKeys($pdo, $fromId, $toId);
            $pdo->prepare('DELETE FROM branches WHERE id = ?')->execute([$fromId]);
        }
    }

    public static function remapForeignKeys(\PDO $pdo, int $fromId, int $toId): void
    {
        if ($fromId === $toId || $fromId <= 0 || $toId <= 0) {
            return;
        }
        if (self::tableExists($pdo, 'delivery_notes') && self::columnExists($pdo, 'delivery_notes', 'branch_id')) {
            self::mergeDeliveryNotes($pdo, $fromId, $toId);
        }
        if (self::tableExists($pdo, 'delivery_route_assignments') && self::columnExists($pdo, 'delivery_route_assignments', 'branch_id')) {
            try {
                $pdo->prepare(
                    'DELETE dra FROM delivery_route_assignments dra
                     INNER JOIN delivery_route_assignments d2
                       ON d2.customer_id = dra.customer_id AND d2.delivery_date = dra.delivery_date AND d2.branch_id = ?
                     WHERE dra.branch_id = ?'
                )->execute([$toId, $fromId]);
                $pdo->prepare('UPDATE delivery_route_assignments SET branch_id = ? WHERE branch_id = ?')->execute([$toId, $fromId]);
            } catch (\PDOException $e) {
                // ignore missing column / schema mismatch
            }
        }
        if (self::tableExists($pdo, 'routes')) {
            self::safeUpdateBranchColumn($pdo, 'routes', 'from_branch_id', $toId, $fromId);
            self::safeUpdateBranchColumn($pdo, 'routes', 'to_branch_id', $toId, $fromId);
        }
        foreach ([
            ['users', 'branch_id'],
            ['suppliers', 'branch_id'],
            ['parcels', 'from_branch_id'],
            ['parcels', 'to_branch_id'],
            ['expenses', 'branch_id'],
            ['employees', 'branch_id'],
            ['employee_advances', 'branch_id'],
        ] as [$tbl, $col]) {
            self::safeUpdateBranchColumn($pdo, $tbl, $col, $toId, $fromId);
        }
    }

    private static function safeUpdateBranchColumn(\PDO $pdo, string $table, string $column, int $toId, int $fromId): void
    {
        if (!self::columnExists($pdo, $table, $column)) {
            return;
        }
        try {
            $pdo->prepare("UPDATE `{$table}` SET `{$column}` = ? WHERE `{$column}` = ?")->execute([$toId, $fromId]);
        } catch (\PDOException $e) {
            // ignore missing column / permission issues during best-effort migration
        }
    }

    private static function mergeDeliveryNotes(\PDO $pdo, int $fromId, int $toId): void
    {
        $stmt = $pdo->prepare('SELECT * FROM delivery_notes WHERE branch_id = ?');
        $stmt->execute([$fromId]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $dn) {
            $dnId = (int)$dn['id'];
            $cid = (int)$dn['customer_id'];
            $dd = (string)$dn['delivery_date'];
            $ex = $pdo->prepare('SELECT id, total_amount, discount FROM delivery_notes WHERE customer_id = ? AND delivery_date = ? AND branch_id = ? LIMIT 1');
            $ex->execute([$cid, $dd, $toId]);
            $keep = $ex->fetch(\PDO::FETCH_ASSOC);
            if (!$keep) {
                $pdo->prepare('UPDATE delivery_notes SET branch_id = ? WHERE id = ?')->execute([$toId, $dnId]);
                continue;
            }
            $keepId = (int)$keep['id'];
            $pdo->prepare(
                'DELETE dnp FROM delivery_note_parcels dnp
                 INNER JOIN delivery_note_parcels k ON k.delivery_note_id = ? AND k.parcel_id = dnp.parcel_id
                 WHERE dnp.delivery_note_id = ?'
            )->execute([$keepId, $dnId]);
            $pdo->prepare('UPDATE delivery_note_parcels SET delivery_note_id = ? WHERE delivery_note_id = ?')->execute([$keepId, $dnId]);
            $total = (float)($keep['total_amount'] ?? 0) + (float)($dn['total_amount'] ?? 0);
            $disc = (float)($keep['discount'] ?? 0) + (float)($dn['discount'] ?? 0);
            $pdo->prepare('UPDATE delivery_notes SET total_amount = ?, discount = ? WHERE id = ?')->execute([$total, $disc, $keepId]);
            $pdo->prepare('DELETE FROM delivery_notes WHERE id = ?')->execute([$dnId]);
        }
    }

    private static function tableExists(\PDO $pdo, string $table): bool
    {
        $t = preg_replace('/[^a-z0-9_]/i', '', $table);
        if ($t === '') {
            return false;
        }
        $st = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($t));
        return $st && $st->fetch() !== false;
    }

    private static function columnExists(\PDO $pdo, string $table, string $column): bool
    {
        if (!self::tableExists($pdo, $table)) {
            return false;
        }
        $t = preg_replace('/[^a-z0-9_]/i', '', $table);
        $c = preg_replace('/[^a-z0-9_]/i', '', $column);
        if ($t === '' || $c === '') {
            return false;
        }
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $st->execute([$t, $c]);
        return (int)$st->fetchColumn() > 0;
    }
}
