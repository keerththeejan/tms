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
        // Per-account Normal Balance (group nature / accounts.normal_balance):
        // DEBIT-normal:  signed_opening + Σ(debit − credit)
        // CREDIT-normal: signed_opening + Σ(credit − debit)
        return '(CASE
                    WHEN COALESCE(a.normal_balance, ag.nature, \'DEBIT\') = \'CREDIT\' THEN
                        (CASE WHEN a.opening_balance_type = \'CREDIT\' THEN a.opening_balance ELSE -a.opening_balance END)
                        + COALESCE((
                            SELECT SUM(le.credit_amount) - SUM(le.debit_amount)
                            FROM ledger_entries le
                            INNER JOIN vouchers v ON v.id = le.voucher_id AND ' . $valid . '
                            WHERE le.account_id = a.id
                        ), 0)
                    ELSE
                        (CASE WHEN a.opening_balance_type = \'DEBIT\' THEN a.opening_balance ELSE -a.opening_balance END)
                        + COALESCE((
                            SELECT SUM(le.debit_amount) - SUM(le.credit_amount)
                            FROM ledger_entries le
                            INNER JOIN vouchers v ON v.id = le.voucher_id AND ' . $valid . '
                            WHERE le.account_id = a.id
                        ), 0)
                 END)';
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

        $normal = strtoupper(trim((string) ($data['normal_balance'] ?? $data['opening_balance_type'] ?? 'DEBIT')));
        if ($normal !== 'CREDIT') {
            $normal = 'DEBIT';
        }
        $ledgerType = strtoupper(trim((string) ($data['ledger_type'] ?? 'GENERAL')));
        if ($ledgerType === '') {
            $ledgerType = 'GENERAL';
        }
        $accountType = strtoupper(trim((string) ($data['account_type'] ?? 'GENERAL')));
        if ($accountType === '') {
            $accountType = 'GENERAL';
        }
        $parentId = isset($data['parent_account_id']) && (int) $data['parent_account_id'] > 0
            ? (int) $data['parent_account_id']
            : null;

        $st = $pdo->prepare(
            'INSERT INTO accounts (account_code, account_name, account_group_id, parent_account_id, opening_balance, opening_balance_type,
             normal_balance, ledger_type, account_type, is_active, is_system, branch_id, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $code,
            trim((string) ($data['account_name'] ?? '')),
            $data['account_group_id'],
            $parentId,
            (float) ($data['opening_balance'] ?? 0),
            $data['opening_balance_type'] ?? $normal,
            $normal,
            $ledgerType,
            $accountType,
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
        
        foreach ([
            'account_name',
            'account_group_id',
            'parent_account_id',
            'opening_balance',
            'opening_balance_type',
            'normal_balance',
            'ledger_type',
            'account_type',
            'is_active',
            'branch_id',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $value = $data[$field];
                if ($field === 'account_name') {
                    $value = trim((string) $value);
                }
                if ($field === 'normal_balance') {
                    $value = strtoupper(trim((string) $value)) === 'CREDIT' ? 'CREDIT' : 'DEBIT';
                }
                if ($field === 'ledger_type' || $field === 'account_type') {
                    $value = strtoupper(trim((string) $value));
                    if ($value === '') {
                        $value = 'GENERAL';
                    }
                }
                if ($field === 'parent_account_id') {
                    $value = ($value === null || $value === '' || (int) $value <= 0) ? null : (int) $value;
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

    /** @return float Signed balance in the account's Normal Balance units (positive = natural side). */
    public static function getBalance(PDO $pdo, int $accountId, ?string $asOfDate = null): float
    {
        $account = self::getById($pdo, $accountId);
        if (!$account) {
            return 0.0;
        }

        $normal = AccountingBalanceService::resolveNormalBalance($account);
        $masterSigned = AccountingBalanceService::signMasterOpening(
            (float) ($account['opening_balance'] ?? 0),
            (string) ($account['opening_balance_type'] ?? $normal),
            $normal
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
        $result = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return AccountingBalanceService::calculateLedgerClosingBalance(
            $normal,
            $masterSigned,
            (float) ($result['total_debit'] ?? 0),
            (float) ($result['total_credit'] ?? 0)
        );
    }

    /**
     * General Ledger for one Chart of Accounts line (independent ledger).
     *
     * @param array<string,mixed> $filters voucher_type, branch_id, status
     * @return array<string,mixed>
     */
    public static function getLedger(
        PDO $pdo,
        int $accountId,
        ?string $fromDate = null,
        ?string $toDate = null,
        array $filters = []
    ): array {
        if ($fromDate === null && isset($filters['from_date']) && $filters['from_date'] !== '') {
            $fromDate = (string) $filters['from_date'];
        }
        if ($toDate === null && isset($filters['to_date']) && $filters['to_date'] !== '') {
            $toDate = (string) $filters['to_date'];
        }

        $account = self::getById($pdo, $accountId);
        if (!$account) {
            return [];
        }

        return self::buildLedgerReport($pdo, $account, $fromDate, $toDate, $filters);
    }

    /**
     * @param array<string,mixed> $account
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public static function buildLedgerReport(
        PDO $pdo,
        array $account,
        ?string $fromDate,
        ?string $toDate,
        array $filters = []
    ): array {
        $accountId = (int) ($account['id'] ?? 0);
        $normal = AccountingBalanceService::resolveNormalBalance($account);
        $masterSigned = AccountingBalanceService::signMasterOpening(
            (float) ($account['opening_balance'] ?? 0),
            (string) ($account['opening_balance_type'] ?? $normal),
            $normal
        );

        $valid = AccountingBalanceService::validVoucherPredicate('v');
        $statusFilter = strtoupper(trim((string) ($filters['status'] ?? '')));
        if ($statusFilter !== '' && $statusFilter !== 'POSTED') {
            $valid = 'v.deleted_at IS NULL AND v.status = ' . $pdo->quote($statusFilter);
        }

        $voucherType = trim((string) ($filters['voucher_type'] ?? ''));
        $branchId = (int) ($filters['branch_id'] ?? 0);

        $debitBefore = 0.0;
        $creditBefore = 0.0;
        if ($fromDate) {
            // Opening carry-forward includes all prior types for this account only
            $priorSql = "SELECT COALESCE(SUM(le.debit_amount), 0) AS total_debit,
                                COALESCE(SUM(le.credit_amount), 0) AS total_credit
                         FROM ledger_entries le
                         INNER JOIN vouchers v ON v.id = le.voucher_id AND {$valid}
                         WHERE le.account_id = ? AND le.entry_date < ?";
            $priorParams = [$accountId, $fromDate];
            if ($branchId > 0) {
                $priorSql .= ' AND v.branch_id = ?';
                $priorParams[] = $branchId;
            }
            $st = $pdo->prepare($priorSql);
            $st->execute($priorParams);
            $prior = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            $debitBefore = (float) ($prior['total_debit'] ?? 0);
            $creditBefore = (float) ($prior['total_credit'] ?? 0);
        }

        $openingSigned = AccountingBalanceService::calculateLedgerOpeningBalance(
            $normal,
            $masterSigned,
            $debitBefore,
            $creditBefore
        );

        $entries = self::getLedgerTransactions($pdo, $accountId, $fromDate, $toDate, [
            'voucher_type' => $voucherType,
            'branch_id' => $branchId,
            'valid_sql' => $valid,
        ]);

        $running = $openingSigned;
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($entries as &$entry) {
            $sides = AccountingBalanceService::normalizeSingleSidedAmounts(
                (float) ($entry['debit_amount'] ?? 0),
                (float) ($entry['credit_amount'] ?? 0)
            );
            $debit = $sides['debit'];
            $credit = $sides['credit'];
            $entry['debit_amount'] = $debit;
            $entry['credit_amount'] = $credit;
            $entry['debit_display'] = AccountingBalanceService::formatSideAmount($debit);
            $entry['credit_display'] = AccountingBalanceService::formatSideAmount($credit);
            $totalDebit += $debit;
            $totalCredit += $credit;
            $running = AccountingBalanceService::calculateRunningBalance($normal, $running, $debit, $credit);
            $display = AccountingBalanceService::displayLedgerBalance($normal, $running);
            $entry['running_balance'] = $display['amount'];
            $entry['running_balance_signed'] = $running;
            $entry['balance_type'] = $display['type'];
            $entry['running_balance_display'] = $display['label'];
        }
        unset($entry);

        $openingDisplay = AccountingBalanceService::displayLedgerBalance($normal, $openingSigned);
        $closingSigned = AccountingBalanceService::calculateLedgerClosingBalance(
            $normal,
            $openingSigned,
            $totalDebit,
            $totalCredit
        );
        $closingDisplay = AccountingBalanceService::displayLedgerBalance($normal, $closingSigned);
        $accountType = AccountingBalanceService::resolveAccountType($account);

        return [
            'account' => [
                'id' => $accountId,
                'account_code' => (string) ($account['account_code'] ?? ''),
                'account_name' => (string) ($account['account_name'] ?? ''),
                'group_name' => (string) ($account['group_name'] ?? ''),
                'group_type' => (string) ($account['group_type'] ?? ''),
                'account_type' => $accountType,
                'ledger_type' => (string) ($account['ledger_type'] ?? 'GENERAL'),
                'normal_balance' => $normal,
                'parent_account_id' => isset($account['parent_account_id']) ? (int) $account['parent_account_id'] : null,
            ],
            'normal_balance' => $normal,
            'account_type' => $accountType,
            'opening_balance' => $openingDisplay['amount'],
            'opening_balance_type' => $openingDisplay['type'],
            'opening_balance_display' => $openingDisplay['label'],
            'opening_balance_signed' => $openingSigned,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'total_debit_display' => number_format($totalDebit, 2, '.', ','),
            'total_credit_display' => number_format($totalCredit, 2, '.', ','),
            'closing_balance' => $closingDisplay['amount'],
            'closing_balance_type' => $closingDisplay['type'],
            'closing_balance_display' => $closingDisplay['label'],
            'closing_balance_signed' => $closingSigned,
            'running_balance' => $closingDisplay['amount'],
            'running_balance_type' => $closingDisplay['type'],
            'running_balance_display' => $closingDisplay['label'],
            'total_transactions' => count($entries),
            'entries' => $entries,
        ];
    }

    /**
     * Posted lines for one account only — no cross-ledger duplication.
     *
     * @param array<string,mixed> $filters
     * @return list<array<string,mixed>>
     */
    public static function getLedgerTransactions(
        PDO $pdo,
        int $accountId,
        ?string $fromDate = null,
        ?string $toDate = null,
        array $filters = []
    ): array {
        $valid = (string) ($filters['valid_sql'] ?? AccountingBalanceService::validVoucherPredicate('v'));
        $voucherType = trim((string) ($filters['voucher_type'] ?? ''));
        $branchId = (int) ($filters['branch_id'] ?? 0);

        $sql = "SELECT le.id,
                       le.entry_date,
                       le.voucher_number,
                       le.voucher_type,
                       le.debit_amount,
                       le.credit_amount,
                       le.narration,
                       v.reference_number AS reference,
                       v.narration AS voucher_narration,
                       v.branch_id,
                       b.name AS branch_name,
                       COALESCE(u.full_name, u.username, '') AS created_by
                FROM ledger_entries le
                INNER JOIN vouchers v ON v.id = le.voucher_id AND {$valid}
                LEFT JOIN branches b ON b.id = v.branch_id
                LEFT JOIN users u ON u.id = v.created_by
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
        if ($voucherType !== '') {
            $sql .= ' AND v.voucher_type = ?';
            $params[] = $voucherType;
        }
        if ($branchId > 0) {
            $sql .= ' AND v.branch_id = ?';
            $params[] = $branchId;
        }
        $sql .= ' ORDER BY le.entry_date ASC, le.id ASC';

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'entry_date' => (string) ($row['entry_date'] ?? ''),
                'voucher_date' => (string) ($row['entry_date'] ?? ''),
                'voucher_number' => (string) ($row['voucher_number'] ?? ''),
                'voucher_type' => (string) ($row['voucher_type'] ?? ''),
                'reference' => (string) ($row['reference'] ?? ''),
                'narration' => (string) (($row['narration'] ?? '') !== '' ? $row['narration'] : ($row['voucher_narration'] ?? '')),
                'voucher_narration' => (string) ($row['voucher_narration'] ?? ''),
                'debit_amount' => (float) ($row['debit_amount'] ?? 0),
                'credit_amount' => (float) ($row['credit_amount'] ?? 0),
                'branch' => (string) ($row['branch_name'] ?? ''),
                'branch_id' => isset($row['branch_id']) ? (int) $row['branch_id'] : null,
                'created_by' => (string) ($row['created_by'] ?? ''),
            ];
        }, $rows);
    }
}
