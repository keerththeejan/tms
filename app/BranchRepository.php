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

    /** Default branch for invoices / receipts / header (first is_default active row). */
    public static function getDefaultForPrint(\PDO $pdo): ?array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query('SELECT * FROM branches WHERE is_default = 1 AND is_active = 1 LIMIT 1');
        $row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
        if (!$row) {
            $stmt = $pdo->query('SELECT * FROM branches WHERE is_active = 1 ORDER BY is_main DESC, id ASC LIMIT 1');
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
        $stmt = $pdo->query('SELECT id FROM branches WHERE is_main = 1 AND is_active = 1 ORDER BY id ASC LIMIT 1');
        $row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
        if ($row && (int)$row['id'] > 0) {
            return (int)$row['id'];
        }
        $stmt = $pdo->query('SELECT id FROM branches WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        $row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
        return $row ? (int)$row['id'] : 0;
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
            'SELECT id, name, address_tamil, address_english, phones, is_default, is_main, settings_slot FROM branches WHERE is_active = 1 ORDER BY is_default DESC, settings_slot IS NULL, settings_slot ASC, is_main DESC, name ASC'
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
        $stmt = $pdo->query(
            'SELECT id, name, address_tamil, address_english, phones FROM branches WHERE is_active = 1 ORDER BY id ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $usedIds = [];
        $keywordsByColumn = [
            ['colombo'],
            ['kilinochchi', 'kilinochi'],
            ['mullaitivu', 'mullaithivu', 'mullativu', 'mulllaitivu'],
        ];
        $result = [null, null, null];
        foreach ($keywordsByColumn as $colIdx => $keywords) {
            foreach ($rows as $r) {
                $id = (int)($r['id'] ?? 0);
                if ($id <= 0 || isset($usedIds[$id])) {
                    continue;
                }
                $rawName = trim((string)($r['name'] ?? ''));
                $nameNorm = function_exists('mb_strtolower')
                    ? mb_strtolower($rawName, 'UTF-8')
                    : strtolower($rawName);
                foreach ($keywords as $kw) {
                    if ($kw === '') {
                        continue;
                    }
                    if (str_contains($nameNorm, $kw)) {
                        $shape = self::rowToCompanyBranchShape($r);
                        $shape['id'] = $id;
                        $result[$colIdx] = $shape;
                        $usedIds[$id] = true;
                        break 2;
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
        $stmt = $pdo->query(
            'SELECT * FROM branches WHERE settings_slot IS NOT NULL AND settings_slot BETWEEN 0 AND 2 ORDER BY settings_slot ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $bySlot = [];
        foreach ($rows as $r) {
            $slot = (int)($r['settings_slot'] ?? -1);
            if ($slot >= 0 && $slot <= 2) {
                $bySlot[$slot] = $r;
            }
        }
        $slots = [];
        $anyDb = false;
        for ($i = 0; $i < 3; $i++) {
            if (isset($bySlot[$i])) {
                $anyDb = true;
                $b = $bySlot[$i];
                $slots[] = [
                    'id' => (int)$b['id'],
                    'name' => (string)($b['name'] ?? ''),
                    'address_ta' => (string)($b['address_tamil'] ?? ''),
                    'address_en' => (string)($b['address_english'] ?? ''),
                    'phones' => (string)($b['phones'] ?? ''),
                ];
            } else {
                $slots[] = ['id' => 0, 'name' => '', 'address_ta' => '', 'address_en' => '', 'phones' => ''];
            }
        }
        if ($anyDb) {
            return $slots;
        }
        $legacy = $company['branches'] ?? [];
        if (!is_array($legacy)) {
            return $slots;
        }
        for ($i = 0; $i < 3; $i++) {
            $b = $legacy[$i] ?? null;
            if (!is_array($b)) {
                continue;
            }
            $slots[$i] = [
                'id' => 0,
                'name' => (string)($b['name'] ?? ''),
                'address_ta' => (string)($b['address_ta'] ?? ''),
                'address_en' => (string)($b['address_en'] ?? ''),
                'phones' => (string)($b['phones'] ?? ''),
            ];
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
        $names = $post['branch_name'] ?? [];
        $tas = $post['branch_address_ta'] ?? [];
        $ens = $post['branch_address_en'] ?? [];
        $phones = $post['branch_phones'] ?? [];
        $ids = $post['branch_db_id'] ?? [];
        $defaultIdx = isset($post['default_branch_idx']) ? (int)$post['default_branch_idx'] : 0;
        if ($defaultIdx < 0) {
            $defaultIdx = 0;
        }
        if ($defaultIdx > 2) {
            $defaultIdx = 2;
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec('UPDATE branches SET settings_slot = NULL WHERE settings_slot BETWEEN 0 AND 2');

            for ($i = 0; $i < 3; $i++) {
                $name = trim((string)($names[$i] ?? ''));
                $ta = trim((string)($tas[$i] ?? ''));
                $en = trim((string)($ens[$i] ?? ''));
                $ph = trim((string)($phones[$i] ?? ''));
                $existingId = isset($ids[$i]) ? (int)$ids[$i] : 0;

                $empty = ($name === '' && $ta === '' && $en === '' && $ph === '');
                if ($empty) {
                    continue;
                }
                if ($name === '') {
                    $name = 'Branch';
                }

                if ($existingId > 0) {
                    $st = $pdo->prepare(
                        'UPDATE branches SET name=?, address_tamil=?, address_english=?, phones=?, settings_slot=? WHERE id=?'
                    );
                    $st->execute([
                        $name,
                        $ta !== '' ? $ta : null,
                        $en !== '' ? $en : null,
                        $ph !== '' ? $ph : null,
                        $i,
                        $existingId,
                    ]);
                } else {
                    $code = 'BR-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
                    $st = $pdo->prepare(
                        'INSERT INTO branches (name, code, address_tamil, address_english, phones, is_main, is_active, is_default, settings_slot, location) VALUES (?,?,?,?,?,?,?,?,?,NULL)'
                    );
                    $st->execute([
                        $name,
                        $code,
                        $ta !== '' ? $ta : null,
                        $en !== '' ? $en : null,
                        $ph !== '' ? $ph : null,
                        0,
                        1,
                        0,
                        $i,
                    ]);
                }
            }

            $pdo->exec('UPDATE branches SET is_default = 0');
            $stPick = $pdo->prepare(
                'SELECT id FROM branches WHERE settings_slot = ? AND settings_slot BETWEEN 0 AND 2 LIMIT 1'
            );
            $stPick->execute([$defaultIdx]);
            $pickRow = $stPick->fetch(\PDO::FETCH_ASSOC);
            $chosenId = $pickRow ? (int)$pickRow['id'] : 0;
            if ($chosenId > 0) {
                $st = $pdo->prepare('UPDATE branches SET is_default = 1 WHERE id = ?');
                $st->execute([$chosenId]);
            }

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
            'SELECT name, address_tamil, address_english, phones, settings_slot FROM branches WHERE settings_slot BETWEEN 0 AND 2 ORDER BY settings_slot ASC'
        );
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $bySlot = [];
        foreach ($rows as $r) {
            $s = (int)($r['settings_slot'] ?? -1);
            if ($s >= 0 && $s <= 2) {
                $bySlot[$s] = $r;
            }
        }
        $mirror = [];
        for ($i = 0; $i < 3; $i++) {
            if (isset($bySlot[$i])) {
                $b = $bySlot[$i];
                $mirror[] = [
                    'name' => (string)($b['name'] ?? ''),
                    'address_ta' => (string)($b['address_tamil'] ?? ''),
                    'address_en' => (string)($b['address_english'] ?? ''),
                    'phones' => (string)($b['phones'] ?? ''),
                ];
            } else {
                $mirror[] = ['name' => '', 'address_ta' => '', 'address_en' => '', 'phones' => ''];
            }
        }
        return $mirror;
    }

    /**
     * @param int[] $preserveIds Always include these branch ids (e.g. current parcel from/to when inactive).
     * @return list<array<string,mixed>>
     */
    public static function forDropdowns(\PDO $pdo, array $preserveIds = []): array
    {
        self::ensureSchema($pdo);
        $preserveIds = array_values(array_unique(array_filter(array_map('intval', $preserveIds), static function ($v) {
            return $v > 0;
        })));
        if ($preserveIds === []) {
            $stmt = $pdo->query(
                'SELECT id, name, code, is_main, is_active, location, address_tamil, address_english, phones, is_default FROM branches WHERE is_active = 1 ORDER BY is_main DESC, name ASC'
            );
            return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        }
        $placeholders = implode(',', array_fill(0, count($preserveIds), '?'));
        $sql = 'SELECT id, name, code, is_main, is_active, location, address_tamil, address_english, phones, is_default FROM branches WHERE is_active = 1 OR id IN (' . $placeholders . ') ORDER BY is_main DESC, name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($preserveIds);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string,mixed> $parcel
     */
    public static function forParcelForm(\PDO $pdo, array $parcel): array
    {
        return self::forDropdowns($pdo, [
            (int)($parcel['from_branch_id'] ?? 0),
            (int)($parcel['to_branch_id'] ?? 0),
        ]);
    }

    /** All branches for filters, reports, and admin lists (includes inactive). */
    public static function forFilters(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query(
            'SELECT id, name, code, is_main, is_active, location, address_tamil, address_english, phones, is_default FROM branches ORDER BY name ASC'
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /** Dashboard widgets: id, name, code (+ flags). */
    public static function forDashboard(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query('SELECT id, name, code, is_main, is_active, is_default FROM branches ORDER BY name ASC');
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /** Branches admin index / edit form source. */
    public static function allOrderedForAdmin(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $stmt = $pdo->query('SELECT * FROM branches ORDER BY is_main DESC, is_default DESC, name ASC');
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
