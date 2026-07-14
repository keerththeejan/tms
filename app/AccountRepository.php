<?php

declare(strict_types=1);

/**
 * Accounts Repository
 * Manages Chart of Accounts (BUSY/Tally style)
 */
class AccountRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        AccountingSchemaRepository::ensureSchema($pdo);
    }

  private static function balanceSelectSql(): string
    {
        $valid = AccountingBalanceService::validVoucherPredicate('v');
        // Cash-In/Cash-Out: Balance = signed master opening + Σ(credit − debit) for POSTED lines
        return '(CASE WHEN a.opening_balance_type = \'DEBIT\' THEN -a.opening_balance ELSE a.opening_balance END)
                + COALESCE((
                    SELECT SUM(le.credit_amount) - SUM(le.debit_amount)
                    FROM ledger_entries le
                    INNER JOIN vouchers v ON v.id = le.voucher_id AND ' . $valid . '
                    WHERE le.account_id = a.id
                ), 0)';
    }

    /** @return list<array<string,mixed>> */
    public static function listAccounts(PDO $pdo, bool $includeInactive = false): array
    {
        $balanceSql = self::balanceSelectSql();
        $sql = "SELECT a.*, ag.group_name, ag.group_type, ag.nature AS group_nature,
                {$balanceSql} AS current_balance
                FROM accounts a
                INNER JOIN account_groups ag ON ag.id = a.account_group_id
                WHERE a.deleted_at IS NULL";

        if (!$includeInactive) {
            $sql .= ' AND a.is_active = 1';
        }

        $sql .= ' ORDER BY a.account_code ASC';

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Chart of accounts listing with optional filters.
     *
     * @param array{q?:string,group_type?:string,status?:string,sort?:string,order?:string} $filters
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public static function listForChart(PDO $pdo, array $filters = [], int $page = 1, int $limit = 50): array
    {
        $balanceSql = self::balanceSelectSql();
        $where = ['a.deleted_at IS NULL'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(a.account_code LIKE ? OR a.account_name LIKE ? OR ag.group_name LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $groupType = strtoupper(trim((string) ($filters['group_type'] ?? '')));
        if ($groupType !== '') {
            $where[] = 'ag.group_type = ?';
            $params[] = $groupType;
        }

        $status = strtolower(trim((string) ($filters['status'] ?? '')));
        if ($status === 'active') {
            $where[] = 'a.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'a.is_active = 0';
        }

        $whereSql = implode(' AND ', $where);

        $countSt = $pdo->prepare(
            "SELECT COUNT(*) FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             WHERE {$whereSql}"
        );
        $countSt->execute($params);
        $total = (int) $countSt->fetchColumn();

        $sortMap = [
            'account_code' => 'a.account_code',
            'account_name' => 'a.account_name',
            'group_name' => 'ag.group_name',
            'group_type' => 'ag.group_type',
            'current_balance' => 'current_balance',
            'is_active' => 'a.is_active',
        ];
        $sort = (string) ($filters['sort'] ?? 'account_code');
        $orderCol = $sortMap[$sort] ?? 'a.account_code';
        $orderDir = strtoupper((string) ($filters['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $page = max(1, $page);
        $limit = max(1, min(500, $limit));
        $offset = ($page - 1) * $limit;

        $sql = "SELECT a.*, ag.group_name, ag.group_type, ag.nature AS group_nature,
                {$balanceSql} AS current_balance
                FROM accounts a
                INNER JOIN account_groups ag ON ag.id = a.account_group_id
                WHERE {$whereSql}
                ORDER BY {$orderCol} {$orderDir}
                LIMIT {$limit} OFFSET {$offset}";

        $st = $pdo->prepare($sql);
        $st->execute($params);

        return [
            'rows' => $st->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total' => $total,
        ];
    }

    public static function generateNextCode(PDO $pdo, ?int $groupId = null): string
    {
        if ($groupId !== null && $groupId > 0) {
            $st = $pdo->prepare(
                "SELECT account_code FROM accounts
                 WHERE account_group_id = ? AND deleted_at IS NULL
                   AND account_code REGEXP '^[0-9]+$'
                 ORDER BY CAST(account_code AS UNSIGNED) DESC, account_code DESC
                 LIMIT 1"
            );
            $st->execute([$groupId]);
            $last = $st->fetchColumn();
            if ($last !== false && preg_match('/^(\d+)$/', (string) $last, $m)) {
                $next = (int) $m[1] + 1;

                return str_pad((string) $next, max(5, strlen($m[1])), '0', STR_PAD_LEFT);
            }

            $group = AccountGroupRepository::getById($pdo, $groupId);
            if ($group) {
                $prefix = self::numericPrefixForGroupType((string) ($group['group_type'] ?? 'EXPENSES'));

                return (string) ($prefix * 10000 + 1);
            }
        }

        $max = (int) ($pdo->query(
            "SELECT MAX(CAST(account_code AS UNSIGNED)) FROM accounts
             WHERE account_code REGEXP '^[0-9]+$' AND deleted_at IS NULL"
        )->fetchColumn() ?: 10000);

        return (string) ($max + 1);
    }

    private static function numericPrefixForGroupType(string $groupType): int
    {
        return match (strtoupper($groupType)) {
            'ASSETS' => 1,
            'LIABILITIES' => 2,
            'CAPITAL' => 3,
            'INCOME' => 4,
            'EXPENSES' => 5,
            default => 1,
        };
    }

    public static function codeExists(PDO $pdo, string $code, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM accounts WHERE account_code = ? AND deleted_at IS NULL';
        $params = [$code];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return (int) $st->fetchColumn() > 0;
    }

    public static function nameExistsInGroup(PDO $pdo, string $name, int $groupId, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM accounts
                WHERE account_group_id = ? AND LOWER(TRIM(account_name)) = LOWER(TRIM(?))
                  AND deleted_at IS NULL';
        $params = [$groupId, $name];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return (int) $st->fetchColumn() > 0;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    public static function validateAccountData(PDO $pdo, array $data, ?int $excludeId = null): array
    {
        $errors = [];
        $name = trim((string) ($data['account_name'] ?? ''));
        $code = trim((string) ($data['account_code'] ?? ''));
        $codeMode = strtolower(trim((string) ($data['code_mode'] ?? 'auto')));
        if (!in_array($codeMode, ['auto', 'manual'], true)) {
            $codeMode = 'auto';
        }
        $groupId = (int) ($data['account_group_id'] ?? 0);
        $openingBalance = (string) ($data['opening_balance'] ?? '0');
        $isCreate = $excludeId === null || $excludeId <= 0;

        if ($groupId <= 0) {
            $errors['account_group_id'] = 'Please select an account group.';
        } elseif (!AccountGroupRepository::getById($pdo, $groupId)) {
            $errors['account_group_id'] = 'Selected account group is invalid.';
        }

        if ($name === '') {
            $errors['account_name'] = 'Account name is required.';
        } elseif (mb_strlen($name) < 3) {
            $errors['account_name'] = 'Account name must be at least 3 characters.';
        } elseif ($groupId > 0 && self::nameExistsInGroup($pdo, $name, $groupId, $excludeId)) {
            $errors['account_name'] = 'Account name already exists in this group.';
        }

        if ($isCreate) {
            if ($codeMode === 'manual') {
                if ($code === '') {
                    $errors['account_code'] = 'Account code is required.';
                } elseif (!preg_match('/^\d+$/', $code)) {
                    $errors['account_code'] = 'Account code must be numeric.';
                } elseif (self::codeExists($pdo, $code, $excludeId)) {
                    $errors['account_code'] = 'This account code already exists.';
                }
            } elseif ($code !== '') {
                if (!preg_match('/^\d+$/', $code)) {
                    $errors['account_code'] = 'Account code must be numeric.';
                } elseif (self::codeExists($pdo, $code, $excludeId)) {
                    $errors['account_code'] = 'This account code already exists.';
                }
            }
        }

        if ($openingBalance !== '' && !is_numeric($openingBalance)) {
            $errors['opening_balance'] = 'Opening balance must be numeric.';
        }

        $balanceType = strtoupper((string) ($data['opening_balance_type'] ?? 'DEBIT'));
        if (!in_array($balanceType, ['DEBIT', 'CREDIT'], true)) {
            $errors['opening_balance_type'] = 'Invalid balance type.';
        }

        $isActive = $data['is_active'] ?? null;
        if ($isActive !== null && !in_array((int) $isActive, [0, 1], true)) {
            $errors['is_active'] = 'Invalid status.';
        }

        return $errors;
    }

    public static function hasTransactionUsage(PDO $pdo, int $id): bool
    {
        $queries = [
            'SELECT COUNT(*) FROM voucher_details WHERE account_id = ?',
            'SELECT COUNT(*) FROM ledger_entries WHERE account_id = ?',
            'SELECT COUNT(*) FROM vouchers WHERE bank_account_id = ? AND deleted_at IS NULL',
            'SELECT COUNT(*) FROM customer_ledger WHERE account_id = ?',
        ];

        foreach ($queries as $sql) {
            $st = $pdo->prepare($sql);
            $st->execute([$id]);
            if ((int) $st->fetchColumn() > 0) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array<string,mixed>> */
    public static function listByGroup(PDO $pdo, int $groupId): array
    {
        $st = $pdo->prepare(
            'SELECT a.*, ag.group_name, ag.group_type, ag.nature AS group_nature
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             WHERE a.account_group_id = ? AND a.is_active = 1 AND a.deleted_at IS NULL
             ORDER BY a.account_code ASC'
        );
        $st->execute([$groupId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function searchAccounts(PDO $pdo, string $query, int $limit = 50): array
    {
        $st = $pdo->prepare(
            'SELECT a.*, ag.group_name, ag.group_type, ag.nature AS group_nature
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             WHERE (a.account_code LIKE ? OR a.account_name LIKE ?)
             AND a.is_active = 1 AND a.deleted_at IS NULL
             ORDER BY a.account_name ASC
             LIMIT ?'
        );
        $st->execute(["%{$query}%", "%{$query}%", $limit]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed>|null */
    public static function getById(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare(
            'SELECT a.*, ag.group_name, ag.group_type, ag.nature AS group_nature
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             WHERE a.id = ? AND a.deleted_at IS NULL
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
            'SELECT a.*, ag.group_name, ag.group_type, ag.nature AS group_nature
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             WHERE a.account_code = ? AND a.deleted_at IS NULL
             LIMIT 1'
        );
        $st->execute([$code]);
        $result = $st->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /** @return array<string,mixed> */
    public static function create(PDO $pdo, array $data): array
    {
        $code = trim((string) ($data['account_code'] ?? ''));
        if ($code === '') {
            $code = self::generateNextCode($pdo, (int) ($data['account_group_id'] ?? 0));
        }

        $st = $pdo->prepare(
            'INSERT INTO accounts (account_code, account_name, account_group_id, opening_balance, opening_balance_type, is_active, is_system, branch_id, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $code,
            trim((string) ($data['account_name'] ?? '')),
            $data['account_group_id'],
            (float) ($data['opening_balance'] ?? 0),
            $data['opening_balance_type'] ?? 'DEBIT',
            (int) ($data['is_active'] ?? 1),
            (int) ($data['is_system'] ?? 0),
            $data['branch_id'] ?? null,
            $data['created_by'] ?? null,
        ]);

        return self::getById($pdo, (int) $pdo->lastInsertId()) ?: [];
    }

    /** @return array<string,mixed> */
    public static function update(PDO $pdo, int $id, array $data): array
    {
        $fields = [];
        $params = [];
        
        foreach (['account_name', 'account_group_id', 'opening_balance', 'opening_balance_type', 'is_active', 'branch_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $value = $data[$field];
                if ($field === 'account_name') {
                    $value = trim((string) $value);
                }
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return self::getById($pdo, $id) ?: [];
        }
        
        $params[] = $id;
        $sql = 'UPDATE accounts SET ' . implode(', ', $fields) . ' WHERE id = ?';
        
        $st = $pdo->prepare($sql);
        $st->execute($params);
        
        return self::getById($pdo, $id) ?: [];
    }

    public static function delete(PDO $pdo, int $id, ?int $userId = null): bool
    {
        $account = self::getById($pdo, $id);
        if (!$account) {
            throw new RuntimeException('Account not found.');
        }

        if ((int) ($account['is_system'] ?? 0) === 1) {
            throw new RuntimeException('System accounts cannot be deleted.');
        }

        if (self::hasTransactionUsage($pdo, $id)) {
            throw new RuntimeException('This account cannot be deleted because it has transactions.');
        }

        $st = $pdo->prepare(
            'UPDATE accounts SET deleted_at = CURRENT_TIMESTAMP, is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND deleted_at IS NULL'
        );

        return $st->execute([$id]);
    }

    /** @return float */
    public static function getBalance(PDO $pdo, int $accountId, ?string $asOfDate = null): float
    {
        $account = self::getById($pdo, $accountId);
        if (!$account) {
            return 0.0;
        }

        // CREDIT opening = Cash In (+); DEBIT opening = Cash Out (−)
        $openingBalance = AccountingBalanceService::signedAmount(
            (float) ($account['opening_balance'] ?? 0),
            (string) ($account['opening_balance_type'] ?? 'CREDIT')
        );

        $valid = AccountingBalanceService::validVoucherPredicate('v');
        $sql = "SELECT COALESCE(SUM(le.debit_amount), 0) AS total_debit,
                       COALESCE(SUM(le.credit_amount), 0) AS total_credit
                FROM ledger_entries le
                INNER JOIN vouchers v ON v.id = le.voucher_id AND {$valid}
                WHERE le.account_id = ?";

        $params = [$accountId];

        if ($asOfDate) {
            $sql .= ' AND le.entry_date <= ?';
            $params[] = $asOfDate;
        }

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $result = $st->fetch(PDO::FETCH_ASSOC);

        $totalDebit = (float) ($result['total_debit'] ?? 0);
        $totalCredit = (float) ($result['total_credit'] ?? 0);

        // Closing = Opening + Credit − Debit
        return AccountingBalanceService::calculateClosingBalance($openingBalance, $totalDebit, $totalCredit);
    }

    /** @return list<array<string,mixed>> */
    public static function getLedger(PDO $pdo, int $accountId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $account = self::getById($pdo, $accountId);
        if (!$account) {
            return [];
        }

        $openingBalance = AccountingBalanceService::signedAmount(
            (float) ($account['opening_balance'] ?? 0),
            (string) ($account['opening_balance_type'] ?? 'CREDIT')
        );

        $valid = AccountingBalanceService::validVoucherPredicate('v');

        // Opening for period = master + activity strictly before From Date
        $openingBalanceBefore = $openingBalance;
        if ($fromDate) {
            $st = $pdo->prepare(
                "SELECT COALESCE(SUM(le.debit_amount), 0) AS total_debit,
                        COALESCE(SUM(le.credit_amount), 0) AS total_credit
                 FROM ledger_entries le
                 INNER JOIN vouchers v ON v.id = le.voucher_id AND {$valid}
                 WHERE le.account_id = ? AND le.entry_date < ?"
            );
            $st->execute([$accountId, $fromDate]);
            $result = $st->fetch(PDO::FETCH_ASSOC);
            $totalDebit = (float) ($result['total_debit'] ?? 0);
            $totalCredit = (float) ($result['total_credit'] ?? 0);
            $openingBalanceBefore = AccountingBalanceService::calculateOpeningBalance(
                $totalDebit,
                $totalCredit,
                $openingBalance
            );
        }

        $sql = "SELECT le.*, v.voucher_date, v.voucher_type, v.narration AS voucher_narration
                FROM ledger_entries le
                INNER JOIN vouchers v ON v.id = le.voucher_id AND {$valid}
                WHERE le.account_id = ?";

        $params = [$accountId];

        if ($fromDate) {
            $sql .= ' AND le.entry_date >= ?';
            $params[] = $fromDate;
        }

        if ($toDate) {
            $sql .= ' AND le.entry_date <= ?';
            $params[] = $toDate;
        }

        $sql .= ' ORDER BY le.entry_date ASC, le.id ASC';

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $entries = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $runningBalance = $openingBalanceBefore;
        foreach ($entries as &$entry) {
            $debit = (float) ($entry['debit_amount'] ?? 0);
            $credit = (float) ($entry['credit_amount'] ?? 0);
            $runningBalance = AccountingBalanceService::applyMovement($runningBalance, $debit, $credit);
            $display = AccountingBalanceService::displayBalance($runningBalance);
            $entry['running_balance'] = $runningBalance;
            $entry['balance_type'] = $display['type'];
        }

        $openingDisplay = AccountingBalanceService::displayBalance($openingBalanceBefore);
        $closingDisplay = AccountingBalanceService::displayBalance($runningBalance);

        return [
            'opening_balance' => $openingDisplay['amount'],
            'opening_balance_type' => $openingDisplay['type'],
            'entries' => $entries,
            'closing_balance' => $closingDisplay['amount'],
            'closing_balance_type' => $closingDisplay['type'],
            'opening_balance_signed' => $openingBalanceBefore,
            'closing_balance_signed' => $runningBalance,
        ];
    }
}
