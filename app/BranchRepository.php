<?php
declare(strict_types=1);

/**
 * Single source of truth for branch master data (branches table).
 * Settings “Primary / Branch 2 / Branch 3” use settings_slot 0–2.
 */
final class BranchRepository
{
    private static bool $schemaChecked = false;

    /** Ensure optional columns exist (safe no-op if already present). */
    public static function ensureSchema(\PDO $pdo): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;
        self::applySchemaColumns($pdo);
        try {
            BranchFixedMaster::ensure($pdo);
        } catch (\Throwable $e) {
            // migration best-effort; do not block app boot
        }
    }

    /** Column patches only (no fixed-branch migration). */
    public static function applySchemaColumns(\PDO $pdo): void
    {
        try {
            $pdo->exec('ALTER TABLE branches ADD COLUMN location VARCHAR(255) NULL DEFAULT NULL AFTER code');
        } catch (\PDOException $e) {
            // duplicate column
        }
        try {
            $pdo->exec('ALTER TABLE branches ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_main');
        } catch (\PDOException $e) {
            // duplicate column
        }
        try {
            $pdo->exec('ALTER TABLE branches ADD COLUMN address_tamil VARCHAR(500) NULL DEFAULT NULL AFTER name');
        } catch (\PDOException $e) {
            // duplicate column
        }
        try {
            $pdo->exec('ALTER TABLE branches ADD COLUMN address_english VARCHAR(500) NULL DEFAULT NULL AFTER address_tamil');
        } catch (\PDOException $e) {
            // duplicate column
        }
        try {
            $pdo->exec('ALTER TABLE branches ADD COLUMN phones VARCHAR(255) NULL DEFAULT NULL AFTER address_english');
        } catch (\PDOException $e) {
            // duplicate column
        }
        try {
            $pdo->exec('ALTER TABLE branches ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
        } catch (\PDOException $e) {
            // duplicate column
        }
        try {
            $pdo->exec('ALTER TABLE branches ADD COLUMN settings_slot TINYINT NULL DEFAULT NULL COMMENT "0-2 Settings letterhead slots" AFTER phones');
        } catch (\PDOException $e) {
            // duplicate column
        }
        try {
            $c = $pdo->query('SELECT COUNT(*) AS c FROM branches WHERE is_default = 1')->fetch(\PDO::FETCH_ASSOC);
            if ($c && (int)($c['c'] ?? 0) === 0) {
                $pdo->exec('UPDATE branches SET is_default = 1 WHERE is_main = 1 LIMIT 1');
                $c2 = $pdo->query('SELECT COUNT(*) AS c FROM branches WHERE is_default = 1')->fetch(\PDO::FETCH_ASSOC);
                if ($c2 && (int)($c2['c'] ?? 0) === 0) {
                    $row = $pdo->query('SELECT id FROM branches ORDER BY id ASC LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
                    if ($row && (int)$row['id'] > 0) {
                        $st = $pdo->prepare('UPDATE branches SET is_default = 1 WHERE id = ?');
                        $st->execute([(int)$row['id']]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /** Fixed three-branch IDs in display order: Colombo, Kilinochchi, Mullaitivu. */
    public static function fixedBranchIds(): array
    {
        return BranchFixedMaster::allowedIds();
    }

    private static function fixedOnlyWhere(string $alias = ''): string
    {
        $col = ($alias !== '' ? $alias . '.' : '') . 'id';
        return $col . ' IN (1,2,3)';
    }

    private static function fixedOrderBy(string $alias = 'id'): string
    {
        $col = $alias;
        if (str_contains($alias, '.')) {
            // already qualified
        } elseif ($alias === 'id') {
            $col = 'id';
        }
        return BranchFixedMaster::fixedOrderSql($col);
    }

    /** Default branch for invoices / receipts / header (first is_default active row). */
    public static function getDefaultForPrint(\PDO $pdo): ?array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query(
            'SELECT * FROM branches WHERE is_default = 1 AND is_active = 1 AND id IN (1,2,3) LIMIT 1'
        );
        $row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
        if (!$row) {
            $stmt = $pdo->query(
                'SELECT * FROM branches WHERE is_active = 1 AND id IN (1,2,3) ORDER BY ' . self::fixedOrderBy('id') . ' LIMIT 1'
            );
            $row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
        }
        return $row ?: null;
    }

    public static function getDefaultBranchIdForForms(\PDO $pdo): int
    {
        $r = self::getDefaultForPrint($pdo);
        return $r ? (int)$r['id'] : 0;
    }

    /**
     * Normalized shape for print / Helpers::companyBranches().
     *
     * @return array{name:string,address_ta:string,address_en:string,phones:string}
     */
    public static function rowToCompanyBranchShape(array $b): array
    {
        return [
            'name' => (string)($b['name'] ?? ''),
            'address_ta' => (string)($b['address_tamil'] ?? $b['address_ta'] ?? ''),
            'address_en' => (string)($b['address_english'] ?? $b['address_en'] ?? ''),
            'phones' => (string)($b['phones'] ?? ''),
        ];
    }

    /** Operational hub (pricing / return-load hub): single row with is_main = 1. */
    public static function getMainBranchId(\PDO $pdo): int
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query('SELECT id FROM branches WHERE is_main = 1 AND is_active = 1 AND id IN (1,2,3) ORDER BY id ASC LIMIT 1');
        $row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
        if ($row && (int)$row['id'] > 0) {
            return (int)$row['id'];
        }
        return 2;
    }

    /**
     * All active branches for letterhead / multi-column prints (DB order: default, settings slot, main, name).
     *
     * @return list<array{name:string,address_ta:string,address_en:string,phones:string}>
     */
    public static function branchesForCompanyPrint(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query(
            'SELECT id, name, address_tamil, address_english, phones, is_default, is_main, settings_slot
             FROM branches WHERE is_active = 1 AND id IN (1,2,3)
             ORDER BY ' . self::fixedOrderBy('id')
        );
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $out = [];
        foreach ($rows as $b) {
            $shape = self::rowToCompanyBranchShape($b);
            $shape['id'] = (int)($b['id'] ?? 0);
            $out[] = $shape;
        }
        return $out;
    }

    /**
     * Three invoice header columns in fixed order: Colombo, Kilinochchi, Mullaitivu.
     * Uses active branches only; matches branch name (case-insensitive) against known keywords.
     *
     * @return array{0: ?array{id:int,name:string,address_ta:string,address_en:string,phones:string}, 1: ?array, 2: ?array}
     */
    public static function invoiceHeaderBranchesThree(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $result = [null, null, null];
        $stmt = $pdo->query(
            'SELECT id, name, address_tamil, address_english, phones, settings_slot
             FROM branches WHERE id IN (1,2,3) ORDER BY ' . self::fixedOrderBy('id')
        );
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        foreach ($rows as $idx => $r) {
            if ($idx > 2) {
                break;
            }
            $shape = self::rowToCompanyBranchShape($r);
            $shape['id'] = (int)($r['id'] ?? 0);
            $shape['name'] = (string)(BranchFixedMaster::CANONICAL[$shape['id']]['name'] ?? $shape['name']);
            $result[$idx] = $shape;
        }

        $legacyBranches = [];
        try {
            if (class_exists('Helpers')) {
                $company = Helpers::company();
                $legacy = $company['branches'] ?? [];
                if (is_array($legacy)) {
                    $legacyBranches = $legacy;
                }
            }
        } catch (\Throwable $e) {
            $legacyBranches = [];
        }
        if ($legacyBranches !== []) {
            $keywordsByColumn = [
                ['colombo', 'kolumbu', 'கொழும்பு', 'col'],
                ['kilinochchi', 'kilinochi', 'kilino', 'கிளிநொச்சி', 'kili'],
                ['mullaitivu', 'mullaithivu', 'mullativu', 'mulllaitivu', 'முல்லைத்தீவு', 'mullai', 'mlt'],
            ];
            $matchLegacyByKeywords = static function (array $branches, array $keywords): ?array {
                foreach ($branches as $b) {
                    if (!is_array($b)) {
                        continue;
                    }
                    $name = trim((string)($b['name'] ?? ''));
                    $nameNorm = function_exists('mb_strtolower')
                        ? mb_strtolower($name, 'UTF-8')
                        : strtolower($name);
                    foreach ($keywords as $kw) {
                        if ($kw === '') {
                            continue;
                        }
                        if (str_contains($nameNorm, $kw)) {
                            return [
                                'id' => 0,
                                'name' => $name,
                                'address_ta' => (string)($b['address_ta'] ?? ''),
                                'address_en' => (string)($b['address_en'] ?? ''),
                                'phones' => (string)($b['phones'] ?? ''),
                            ];
                        }
                    }
                }
                return null;
            };
            foreach ($keywordsByColumn as $colIdx => $keywords) {
                $legacyShape = $matchLegacyByKeywords($legacyBranches, $keywords);
                if (!is_array($legacyShape)) {
                    $legacyRaw = $legacyBranches[$colIdx] ?? null;
                    if (is_array($legacyRaw)) {
                        $legacyShape = [
                            'id' => 0,
                            'name' => (string)($legacyRaw['name'] ?? ''),
                            'address_ta' => (string)($legacyRaw['address_ta'] ?? ''),
                            'address_en' => (string)($legacyRaw['address_en'] ?? ''),
                            'phones' => (string)($legacyRaw['phones'] ?? ''),
                        ];
                    }
                }
                if (!is_array($legacyShape)) {
                    continue;
                }
                if (!is_array($result[$colIdx])) {
                    $result[$colIdx] = $legacyShape;
                    continue;
                }
                // Merge only missing fields so DB values remain authoritative.
                foreach (['name', 'address_ta', 'address_en', 'phones'] as $k) {
                    if (trim((string)($result[$colIdx][$k] ?? '')) === '' && trim((string)($legacyShape[$k] ?? '')) !== '') {
                        $result[$colIdx][$k] = $legacyShape[$k];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Active branches for parcel/courier letterhead: $prioritizeBranchId (usually from_branch_id) is listed first.
     *
     * @return list<array{id:int,name:string,address_ta:string,address_en:string,phones:string}>
     */
    public static function branchesForParcelLetterhead(\PDO $pdo, int $prioritizeBranchId = 0): array
    {
        $rows = self::branchesForCompanyPrint($pdo);
        if ($prioritizeBranchId <= 0 || $rows === []) {
            return $rows;
        }
        $prio = null;
        $rest = [];
        foreach ($rows as $r) {
            if ((int)($r['id'] ?? 0) === $prioritizeBranchId) {
                $prio = $r;
            } else {
                $rest[] = $r;
            }
        }
        if ($prio === null) {
            $fb = self::findById($pdo, $prioritizeBranchId);
            if ($fb && (int)($fb['is_active'] ?? 1) === 1) {
                $shape = self::rowToCompanyBranchShape($fb);
                $shape['id'] = (int)$fb['id'];
                array_unshift($rest, $shape);
                return $rest;
            }
            return $rows;
        }
        return array_merge([$prio], $rest);
    }

    /**
     * Three Settings slots (0 = Primary, 1–2 = additional), from DB or legacy company JSON.
     *
     * @return list<array{id:int,name:string,address_ta:string,address_en:string,phones:string}>
     */
    public static function getSettingsFormBranches(\PDO $pdo, array $company): array
    {
        self::ensureSchema($pdo);
        $slots = [];
        for ($i = 0; $i < 3; $i++) {
            $branchId = $i + 1;
            $b = self::findById($pdo, $branchId);
            $canonical = BranchFixedMaster::CANONICAL[$branchId] ?? null;
            if ($b) {
                $slots[] = [
                    'id' => $branchId,
                    'name' => (string)($canonical['name'] ?? $b['name'] ?? ''),
                    'address_ta' => (string)($b['address_tamil'] ?? ''),
                    'address_en' => (string)($b['address_english'] ?? ''),
                    'phones' => (string)($b['phones'] ?? ''),
                ];
            } else {
                $legacy = $company['branches'][$i] ?? null;
                $slots[] = [
                    'id' => $branchId,
                    'name' => (string)($canonical['name'] ?? (is_array($legacy) ? ($legacy['name'] ?? '') : '')),
                    'address_ta' => is_array($legacy) ? (string)($legacy['address_ta'] ?? '') : '',
                    'address_en' => is_array($legacy) ? (string)($legacy['address_en'] ?? '') : '',
                    'phones' => is_array($legacy) ? (string)($legacy['phones'] ?? '') : '',
                ];
            }
        }
        return $slots;
    }

    /** Which Settings card (0–2) is the default for print/header. */
    public static function getDefaultBranchSlotIndex(\PDO $pdo): int
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query(
            'SELECT settings_slot FROM branches WHERE is_default = 1 AND is_active = 1 AND settings_slot BETWEEN 0 AND 2 LIMIT 1'
        );
        $row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
        if ($row && isset($row['settings_slot'])) {
            return (int)$row['settings_slot'];
        }
        return 0;
    }

    /**
     * Sync Settings company POST into branches (settings_slot 0–2) + single is_default.
     *
     * @return array{mirror: list<array{name:string,address_ta:string,address_en:string,phones:string}>}
     */
    public static function syncSettingsFromCompanyPost(\PDO $pdo, array $post): array
    {
        self::ensureSchema($pdo);
        $tas = $post['branch_address_ta'] ?? [];
        $ens = $post['branch_address_en'] ?? [];
        $phones = $post['branch_phones'] ?? [];
        $defaultIdx = isset($post['default_branch_idx']) ? (int)$post['default_branch_idx'] : 0;
        if ($defaultIdx < 0) {
            $defaultIdx = 0;
        }
        if ($defaultIdx > 2) {
            $defaultIdx = 2;
        }

        $pdo->beginTransaction();
        try {
            for ($i = 0; $i < 3; $i++) {
                $branchId = $i + 1;
                $canonical = BranchFixedMaster::CANONICAL[$branchId];
                $ta = trim((string)($tas[$i] ?? ''));
                $en = trim((string)($ens[$i] ?? ''));
                $ph = trim((string)($phones[$i] ?? ''));
                if ($en === '' || $ph === '') {
                    throw new \InvalidArgumentException(
                        'Branch ' . $canonical['name'] . ' requires English address and phone numbers.'
                    );
                }
                $st = $pdo->prepare(
                    'UPDATE branches SET name=?, code=?, address_tamil=?, address_english=?, phones=?, settings_slot=?, is_active=1 WHERE id=?'
                );
                $st->execute([
                    $canonical['name'],
                    $canonical['code'],
                    $ta !== '' ? $ta : null,
                    $en,
                    $ph,
                    $i,
                    $branchId,
                ]);
            }

            $pdo->exec('UPDATE branches SET is_default = 0 WHERE id IN (1,2,3)');
            $defaultBranchId = $defaultIdx + 1;
            $pdo->prepare('UPDATE branches SET is_default = 1 WHERE id = ?')->execute([$defaultBranchId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $mirror = self::buildMirrorForSettingsSlots($pdo);
        return ['mirror' => $mirror];
    }

    /**
     * Three entries for company.json `branches` mirror (always length 3 for legacy UI).
     *
     * @return list<array{name:string,address_ta:string,address_en:string,phones:string}>
     */
    public static function buildMirrorForSettingsSlots(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query(
            'SELECT name, address_tamil, address_english, phones, settings_slot FROM branches WHERE id IN (1,2,3) ORDER BY ' . self::fixedOrderBy('id')
        );
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $mirror = [];
        for ($i = 0; $i < 3; $i++) {
            $b = $rows[$i] ?? null;
            if ($b) {
                $mirror[] = [
                    'name' => (string)(BranchFixedMaster::CANONICAL[$i + 1]['name'] ?? $b['name'] ?? ''),
                    'address_ta' => (string)($b['address_tamil'] ?? ''),
                    'address_en' => (string)($b['address_english'] ?? ''),
                    'phones' => (string)($b['phones'] ?? ''),
                ];
            } else {
                $mirror[] = ['name' => (string)(BranchFixedMaster::CANONICAL[$i + 1]['name'] ?? ''), 'address_ta' => '', 'address_en' => '', 'phones' => ''];
            }
        }
        return $mirror;
    }

    /**
     * Fixed three active branches — single source for all dropdowns and AJAX lists.
     *
     * @return list<array<string,mixed>>
     */
    public static function forFixedThree(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        BranchFixedMaster::ensureRowsPresent($pdo);
        $stmt = $pdo->query(
            'SELECT id, name, code, is_main, is_active, location, address_tamil, address_english, phones, is_default
             FROM branches WHERE is_active = 1 AND id IN (1,2,3)
             ORDER BY ' . self::fixedOrderBy('id')
        );
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        foreach ($rows as &$r) {
            $id = (int)$r['id'];
            if (isset(BranchFixedMaster::CANONICAL[$id])) {
                $r['name'] = BranchFixedMaster::CANONICAL[$id]['name'];
                $r['code'] = BranchFixedMaster::CANONICAL[$id]['code'];
            }
        }
        unset($r);
        return array_values($rows);
    }

    /** Map legacy branch ids (e.g. old "Main Branch (KILI)") to fixed ids 1–3. */
    public static function resolveToFixedBranchId(\PDO $pdo, int $branchId): int
    {
        if (BranchFixedMaster::isAllowedId($branchId)) {
            self::ensureSchema($pdo);
            $st = $pdo->prepare('SELECT id FROM branches WHERE id = ? AND is_active = 1 LIMIT 1');
            $st->execute([$branchId]);
            if ($st->fetch()) {
                return $branchId;
            }
        }
        if ($branchId <= 0) {
            return 0;
        }
        self::ensureSchema($pdo);
        $st = $pdo->prepare('SELECT name, code FROM branches WHERE id = ? LIMIT 1');
        $st->execute([$branchId]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return 0;
        }
        $key = BranchFixedMaster::nameToKey((string)($row['name'] ?? ''));
        if ($key === null) {
            $code = strtoupper(trim((string)($row['code'] ?? '')));
            $key = match ($code) {
                'COL' => 'colombo',
                'KIL' => 'kilinochchi',
                'MUL' => 'mullaitivu',
                default => null,
            };
        }
        return $key !== null ? BranchFixedMaster::keyToTargetId($key) : 0;
    }

    /**
     * @param array<string,mixed> $parcel
     */
    public static function normalizeParcelBranchIds(\PDO $pdo, array &$parcel): void
    {
        $from = self::resolveToFixedBranchId($pdo, (int)($parcel['from_branch_id'] ?? 0));
        $to = self::resolveToFixedBranchId($pdo, (int)($parcel['to_branch_id'] ?? 0));
        if ($from > 0) {
            $parcel['from_branch_id'] = $from;
        }
        if ($to > 0) {
            $parcel['to_branch_id'] = $to;
        }
    }

    /**
     * @param int[] $preserveIds Ignored — fixed three-branch system always returns ids 1–3.
     * @return list<array<string,mixed>>
     */
    public static function forDropdowns(\PDO $pdo, array $preserveIds = []): array
    {
        return self::forFixedThree($pdo);
    }

    /**
     * @param array<string,mixed> $parcel
     */
    public static function forParcelForm(\PDO $pdo, array $parcel): array
    {
        return self::forFixedThree($pdo);
    }

    /** All branches for filters, reports, and admin lists (includes inactive). */
    public static function forFilters(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        BranchFixedMaster::ensureRowsPresent($pdo);
        $stmt = $pdo->query(
            'SELECT id, name, code, is_main, is_active, location, address_tamil, address_english, phones, is_default
             FROM branches WHERE id IN (1,2,3) ORDER BY ' . self::fixedOrderBy('id')
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /** Dashboard widgets: id, name, code (+ flags). */
    public static function forDashboard(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        BranchFixedMaster::ensureRowsPresent($pdo);
        $stmt = $pdo->query(
            'SELECT id, name, code, is_main, is_active, is_default FROM branches WHERE id IN (1,2,3) ORDER BY ' . self::fixedOrderBy('id')
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /** Branches admin index / edit form source. */
    public static function allOrderedForAdmin(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query(
            'SELECT * FROM branches WHERE id IN (1,2,3) ORDER BY ' . self::fixedOrderBy('id')
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function findById(\PDO $pdo, int $id): ?array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->prepare('SELECT * FROM branches WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * JSON list for AJAX (logged-in clients). Optional preserve ids (same as dropdowns).
     *
     * @param int[] $preserveIds
     * @return list<array<string,mixed>>
     */
    public static function toJsonList(\PDO $pdo, array $preserveIds = []): array
    {
        $rows = self::forDropdowns($pdo, $preserveIds);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int)$r['id'],
                'name' => (string)$r['name'],
                'code' => (string)($r['code'] ?? ''),
                'is_main' => (int)($r['is_main'] ?? 0),
                'is_active' => (int)($r['is_active'] ?? 1),
                'is_default' => (int)($r['is_default'] ?? 0),
                'location' => isset($r['location']) ? (string)$r['location'] : null,
                'address_tamil' => (string)($r['address_tamil'] ?? ''),
                'address_english' => (string)($r['address_english'] ?? ''),
                'phones' => (string)($r['phones'] ?? ''),
            ];
        }
        return $out;
    }
}
