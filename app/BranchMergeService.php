<?php
declare(strict_types=1);

/**
 * One-off / maintenance: merge duplicate branch rows into three operational branches
 * (Colombo, Kilinochchi, Mullaitivu) and drop "Main Branch" after reassigning FKs.
 */
final class BranchMergeService
{
    private const CANONICAL = [
        'colombo' => ['name' => 'Colombo', 'code' => 'COL'],
        'kilinochchi' => ['name' => 'Kilinochchi', 'code' => 'KIL'],
        'mullaitivu' => ['name' => 'Mullaitivu', 'code' => 'MUL'],
    ];

    /** @return array{ok:bool,messages:string[],keepers:array<string,int>}|array{ok:false,error:string} */
    public static function preview(\PDO $pdo): array
    {
        try {
            BranchRepository::ensureSchema($pdo);
            $plan = self::buildPlan($pdo);
            return ['ok' => true, 'messages' => $plan['messages'], 'keepers' => $plan['keepers']];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** @return array{ok:bool,messages:string[]}|array{ok:false,error:string} */
    public static function execute(\PDO $pdo): array
    {
        try {
            BranchRepository::ensureSchema($pdo);
            $plan = self::buildPlan($pdo);
            $pdo->beginTransaction();
            foreach ($plan['merges'] as $m) {
                self::mergeBranchIds($pdo, (int)$m['from'], (int)$m['to']);
            }
            foreach ($plan['delete_ids'] as $id) {
                $pdo->prepare('DELETE FROM branches WHERE id = ?')->execute([(int)$id]);
            }
            foreach ($plan['final_updates'] as $u) {
                $pdo->prepare('UPDATE branches SET name = ?, code = ?, is_main = ?, is_active = 1 WHERE id = ?')->execute([
                    $u['name'],
                    $u['code'],
                    (int)$u['is_main'],
                    (int)$u['id'],
                ]);
            }
            if (self::tableExists($pdo, 'users')) {
                $pdo->exec('UPDATE users u INNER JOIN branches b ON b.id = u.branch_id SET u.is_main_branch = b.is_main');
            }
            $pdo->commit();
            return ['ok' => true, 'messages' => array_merge($plan['messages'], ['Done. Committed to database.'])];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{
     *   messages:string[],
     *   keepers:array<string,int>,
     *   merges:list<array{from:int,to:int}>,
     *   delete_ids:int[],
     *   final_updates:list<array{id:int,name:string,code:string,is_main:int}>
     * }
     */
    private static function buildPlan(\PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM branches ORDER BY id ASC');
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        if ($rows === []) {
            throw new \RuntimeException('No branches in database.');
        }

        $groups = [
            'colombo' => [],
            'kilinochchi' => [],
            'mullaitivu' => [],
            '__main__' => [],
        ];
        $unknown = [];
        foreach ($rows as $r) {
            $key = self::nameToGroupKey((string)($r['name'] ?? ''));
            if ($key === null) {
                $unknown[] = (int)$r['id'] . ' = ' . ($r['name'] ?? '');
                continue;
            }
            $groups[$key][] = $r;
        }
        if ($unknown !== []) {
            throw new \RuntimeException('Unrecognized branch names (rename or remove first): ' . implode('; ', $unknown));
        }
        foreach (['colombo', 'kilinochchi', 'mullaitivu'] as $need) {
            if ($groups[$need] === []) {
                throw new \RuntimeException('Missing branch group: ' . $need . ' (need at least one row named Colombo / Kilinochchi / Mullaitivu).');
            }
        }

        $messages = [];
        $keepers = [];
        foreach (['colombo', 'kilinochchi', 'mullaitivu'] as $g) {
            $keeper = self::pickKeeperRow($groups[$g]);
            $keepers[$g] = (int)$keeper['id'];
            $messages[] = 'Keeper for ' . $g . ': id=' . $keepers[$g] . ' (' . ($keeper['name'] ?? '') . ')';
        }

        $kilId = $keepers['kilinochchi'];
        $merges = [];
        $deleteIds = [];

        foreach (['colombo', 'kilinochchi', 'mullaitivu'] as $g) {
            $kid = $keepers[$g];
            foreach ($groups[$g] as $r) {
                $rid = (int)$r['id'];
                if ($rid !== $kid) {
                    $merges[] = ['from' => $rid, 'to' => $kid];
                    $deleteIds[] = $rid;
                    $messages[] = 'Merge duplicate: id ' . $rid . ' → ' . $kid . ' (' . $g . ')';
                }
            }
        }

        foreach ($groups['__main__'] as $r) {
            $rid = (int)$r['id'];
            $merges[] = ['from' => $rid, 'to' => $kilId];
            $deleteIds[] = $rid;
            $messages[] = 'Reassign Main Branch id ' . $rid . ' → Kilinochchi id ' . $kilId;
        }

        $deleteIds = array_values(array_unique($deleteIds));
        sort($deleteIds);

        $finalUpdates = [];
        foreach (['colombo', 'kilinochchi', 'mullaitivu'] as $g) {
            $meta = self::CANONICAL[$g];
            $finalUpdates[] = [
                'id' => $keepers[$g],
                'name' => $meta['name'],
                'code' => $meta['code'],
                'is_main' => $g === 'kilinochchi' ? 1 : 0,
            ];
        }

        $messages[] = 'After run: exactly 3 branches (COL / KIL / MUL), Kilinochchi = main hub.';

        return [
            'messages' => $messages,
            'keepers' => $keepers,
            'merges' => $merges,
            'delete_ids' => $deleteIds,
            'final_updates' => $finalUpdates,
        ];
    }

    private static function nameToGroupKey(string $name): ?string
    {
        $n = mb_strtolower(trim($name), 'UTF-8');
        if ($n === '') {
            return null;
        }
        if ($n === 'main' || $n === 'main branch' || str_contains($n, 'main branch')) {
            return '__main__';
        }
        if ($n === 'colombo' || str_starts_with($n, 'colombo')) {
            return 'colombo';
        }
        if (str_contains($n, 'kilinochchi')) {
            return 'kilinochchi';
        }
        if (str_contains($n, 'mullaitivu') || str_contains($n, 'mullaithivu')) {
            return 'mullaitivu';
        }
        return null;
    }

    /** @param list<array<string,mixed>> $rows */
    private static function pickKeeperRow(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $sa = self::keeperScore($a);
            $sb = self::keeperScore($b);
            if ($sa !== $sb) {
                return $sb <=> $sa;
            }
            return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        });

        return $rows[0];
    }

    private static function keeperScore(array $r): int
    {
        $slot = $r['settings_slot'] ?? null;
        $slotScore = ($slot !== null && $slot !== '' && (int)$slot >= 0) ? 100 : 0;
        $mainScore = !empty($r['is_main']) ? 50 : 0;
        $defScore = !empty($r['is_default']) ? 40 : 0;
        $addr = trim((string)($r['address_english'] ?? ''));

        return $slotScore + $mainScore + $defScore + ($addr !== '' ? 10 : 0);
    }

    private static function tableExists(\PDO $pdo, string $table): bool
    {
        $t = preg_replace('/[^a-z0-9_]/i', '', $table);
        if ($t === '') {
            return false;
        }
        $st = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t));

        return $st && $st->fetch() !== false;
    }

    private static function mergeBranchIds(\PDO $pdo, int $fromId, int $toId): void
    {
        if ($fromId === $toId) {
            return;
        }

        if (self::tableExists($pdo, 'delivery_notes')) {
            self::mergeDeliveryNotes($pdo, $fromId, $toId);
        }
        if (self::tableExists($pdo, 'delivery_route_assignments')) {
            self::mergeDeliveryRouteAssignments($pdo, $fromId, $toId);
        }
        if (self::tableExists($pdo, 'routes')) {
            self::mergeRoutes($pdo, $fromId, $toId);
        }

        $updates = [
            ['users', 'branch_id'],
            ['suppliers', 'branch_id'],
            ['parcels', 'from_branch_id'],
            ['parcels', 'to_branch_id'],
            ['expenses', 'branch_id'],
            ['employees', 'branch_id'],
        ];
        foreach ($updates as [$tbl, $col]) {
            if (!self::tableExists($pdo, $tbl)) {
                continue;
            }
            try {
                $pdo->prepare("UPDATE `{$tbl}` SET `{$col}` = ? WHERE `{$col}` = ?")->execute([$toId, $fromId]);
            } catch (\PDOException $e) {
                throw new \RuntimeException("Failed updating {$tbl}.{$col}: " . $e->getMessage(), 0, $e);
            }
        }

        if (self::tableExists($pdo, 'employee_advances')) {
            try {
                $pdo->prepare('UPDATE employee_advances SET branch_id = ? WHERE branch_id = ?')->execute([$toId, $fromId]);
            } catch (\PDOException $e) {
                throw new \RuntimeException('Failed updating employee_advances.branch_id: ' . $e->getMessage(), 0, $e);
            }
        }
    }

    private static function mergeDeliveryNotes(\PDO $pdo, int $fromId, int $toId): void
    {
        $stmt = $pdo->prepare('SELECT * FROM delivery_notes WHERE branch_id = ?');
        $stmt->execute([$fromId]);
        $dns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($dns as $dn) {
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
            $delDup = $pdo->prepare(
                'DELETE dnp FROM delivery_note_parcels dnp
                 INNER JOIN delivery_note_parcels k ON k.delivery_note_id = ? AND k.parcel_id = dnp.parcel_id
                 WHERE dnp.delivery_note_id = ?'
            );
            $delDup->execute([$keepId, $dnId]);
            $pdo->prepare('UPDATE delivery_note_parcels SET delivery_note_id = ? WHERE delivery_note_id = ?')->execute([$keepId, $dnId]);
            $total = (float)($keep['total_amount'] ?? 0) + (float)($dn['total_amount'] ?? 0);
            $disc = (float)($keep['discount'] ?? 0) + (float)($dn['discount'] ?? 0);
            $pdo->prepare('UPDATE delivery_notes SET total_amount = ?, discount = ? WHERE id = ?')->execute([$total, $disc, $keepId]);
            $pdo->prepare('DELETE FROM delivery_notes WHERE id = ?')->execute([$dnId]);
        }
    }

    private static function mergeDeliveryRouteAssignments(\PDO $pdo, int $fromId, int $toId): void
    {
        $sql = 'DELETE dra FROM delivery_route_assignments dra
            INNER JOIN delivery_route_assignments d2
              ON d2.customer_id = dra.customer_id AND d2.delivery_date = dra.delivery_date AND d2.branch_id = ?
            WHERE dra.branch_id = ?';
        $pdo->prepare($sql)->execute([$toId, $fromId]);
        $pdo->prepare('UPDATE delivery_route_assignments SET branch_id = ? WHERE branch_id = ?')->execute([$toId, $fromId]);
    }

    private static function mergeRoutes(\PDO $pdo, int $fromId, int $toId): void
    {
        $pdo->prepare('UPDATE routes SET from_branch_id = ? WHERE from_branch_id = ?')->execute([$toId, $fromId]);
        $pdo->prepare('UPDATE routes SET to_branch_id = ? WHERE to_branch_id = ?')->execute([$toId, $fromId]);
        for ($i = 0; $i < 40; $i++) {
            $row = $pdo->query(
                'SELECT r1.id AS id1, r2.id AS id2 FROM routes r1
                 INNER JOIN routes r2 ON r1.from_branch_id = r2.from_branch_id AND r1.to_branch_id = r2.to_branch_id AND r1.id < r2.id
                 LIMIT 1'
            )->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                break;
            }
            $keep = (int)$row['id1'];
            $drop = (int)$row['id2'];
            try {
                $pdo->prepare('UPDATE parcels SET route_id = ? WHERE route_id = ?')->execute([$keep, $drop]);
            } catch (\PDOException $e) {
            }
            try {
                $pdo->prepare('UPDATE parcels SET return_route_id = ? WHERE return_route_id = ?')->execute([$keep, $drop]);
            } catch (\PDOException $e) {
            }
            $pdo->prepare('DELETE FROM routes WHERE id = ?')->execute([$drop]);
        }
    }
}

