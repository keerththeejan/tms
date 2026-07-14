<?php

declare(strict_types=1);

/**
 * Ledger Entry Repository
 * Manages double-entry ledger entries for the new accounting module
 */
class LedgerEntryRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        AccountingSchemaRepository::ensureSchema($pdo);
    }

    /** @return list<array<string,mixed>> */
    public static function getByVoucherId(PDO $pdo, int $voucherId): array
    {
        $st = $pdo->prepare(
            'SELECT le.*, a.account_code, a.account_name, ag.group_name
             FROM ledger_entries le
             INNER JOIN accounts a ON a.id = le.account_id
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             WHERE le.voucher_id = ?
             ORDER BY le.id ASC'
        );
        $st->execute([$voucherId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function getByAccountId(PDO $pdo, int $accountId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $valid = AccountingBalanceService::validVoucherPredicate('v');
        $sql = "SELECT le.*, v.voucher_date, v.voucher_type, v.narration AS voucher_narration
                FROM ledger_entries le
                INNER JOIN vouchers v ON v.id = le.voucher_id
                WHERE le.account_id = ? AND {$valid}";

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
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed> */
    public static function create(PDO $pdo, array $data): array
    {
        $st = $pdo->prepare(
            'INSERT INTO ledger_entries (voucher_id, voucher_detail_id, account_id, entry_date, voucher_type, 
             voucher_number, debit_amount, credit_amount, balance_type, narration, reference_id, reference_type, branch_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $data['voucher_id'],
            $data['voucher_detail_id'] ?? null,
            $data['account_id'],
            $data['entry_date'],
            $data['voucher_type'],
            $data['voucher_number'],
            (float) ($data['debit_amount'] ?? 0),
            (float) ($data['credit_amount'] ?? 0),
            $data['balance_type'],
            $data['narration'] ?? null,
            $data['reference_id'] ?? null,
            $data['reference_type'] ?? null,
            $data['branch_id'] ?? null,
        ]);
        
        $id = (int) $pdo->lastInsertId();
        $st = $pdo->prepare('SELECT * FROM ledger_entries WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function createBatch(PDO $pdo, int $voucherId, array $entries): array
    {
        $ownTxn = !$pdo->inTransaction();
        if ($ownTxn) {
            $pdo->beginTransaction();
        }
        try {
            $st = $pdo->prepare('DELETE FROM ledger_entries WHERE voucher_id = ?');
            $st->execute([$voucherId]);

            $created = [];
            foreach ($entries as $entry) {
                $entry['voucher_id'] = $voucherId;
                $created[] = self::create($pdo, $entry);
            }

            if ($ownTxn) {
                $pdo->commit();
            }
            return $created;
        } catch (Throwable $e) {
            if ($ownTxn && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Day Book line listing for [fromDate, toDate].
     * One row per voucher_details line — JOINs to vouchers/accounts/branches/users are 1:1
     * (no fan-out), so amounts are not duplicated by JOINs.
     *
     * @return list<array<string,mixed>>
     */
    public static function getDayBook(PDO $pdo, string $fromDate, string $toDate, ?string $voucherType = null): array
    {
        $valid = AccountingBalanceService::validVoucherPredicate('v');
        $sql = "SELECT v.voucher_date AS entry_date,
                       v.voucher_number,
                       v.voucher_type,
                       a.account_name,
                       v.reference_number AS reference,
                       vd.narration,
                       vd.debit_amount,
                       vd.credit_amount,
                       b.name AS branch_name,
                       COALESCE(u.full_name, u.username, '') AS created_by,
                       vd.id AS detail_id
                FROM voucher_details vd
                INNER JOIN vouchers v ON v.id = vd.voucher_id
                INNER JOIN accounts a ON a.id = vd.account_id
                LEFT JOIN branches b ON b.id = v.branch_id
                LEFT JOIN users u ON u.id = v.created_by
                WHERE {$valid}
                  AND v.voucher_date BETWEEN ? AND ?";

        $params = [$fromDate, $toDate];

        if ($voucherType !== null && $voucherType !== '') {
            $sql .= ' AND v.voucher_type = ?';
            $params[] = $voucherType;
        }

        $sql .= ' ORDER BY v.voucher_date ASC, v.voucher_number ASC, vd.line_number ASC, vd.id ASC';

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function (array $row): array {
            return [
                'entry_date' => (string) ($row['entry_date'] ?? ''),
                'voucher_number' => (string) ($row['voucher_number'] ?? ''),
                'voucher_type' => (string) ($row['voucher_type'] ?? ''),
                'account_name' => (string) ($row['account_name'] ?? ''),
                'reference' => (string) ($row['reference'] ?? ''),
                'narration' => (string) ($row['narration'] ?? ''),
                'debit_amount' => (float) ($row['debit_amount'] ?? 0),
                'credit_amount' => (float) ($row['credit_amount'] ?? 0),
                'branch' => (string) ($row['branch_name'] ?? ''),
                'created_by' => (string) ($row['created_by'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * Day Book summary for the selected period.
     *
     * Business rules: Credit = Cash In, Debit = Cash Out.
     *
     * Opening Balance = Chart-of-Accounts master openings (CREDIT +, DEBIT −)
     *                 + Σ credit of POSTED lines with voucher_date < From Date
     *                 − Σ debit  of POSTED lines with voucher_date < From Date
     *                 (excludes the selected From Date)
     *
     * Total Debit / Total Credit = Σ of POSTED lines with voucher_date in [From, To]
     *
     * Closing Balance = Opening + Total Credit − Total Debit
     *
     * @return array{total_records: int, opening_balance: float, total_debit: float, total_credit: float, closing_balance: float}
     */
    public static function getDayBookSummary(PDO $pdo, string $fromDate, string $toDate, ?string $voucherType = null): array
    {
        $valid = AccountingBalanceService::validVoucherPredicate('v');
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN v.voucher_date >= ? AND v.voucher_date <= ? THEN 1 ELSE 0 END), 0) AS total_records,
                    COALESCE(SUM(CASE WHEN v.voucher_date >= ? AND v.voucher_date <= ? THEN vd.debit_amount ELSE 0 END), 0) AS total_debit,
                    COALESCE(SUM(CASE WHEN v.voucher_date >= ? AND v.voucher_date <= ? THEN vd.credit_amount ELSE 0 END), 0) AS total_credit,
                    COALESCE(SUM(CASE WHEN v.voucher_date < ? THEN vd.debit_amount ELSE 0 END), 0) AS debit_before,
                    COALESCE(SUM(CASE WHEN v.voucher_date < ? THEN vd.credit_amount ELSE 0 END), 0) AS credit_before
                FROM voucher_details vd
                INNER JOIN vouchers v ON v.id = vd.voucher_id
                WHERE {$valid}
                  AND (v.voucher_date < ? OR (v.voucher_date >= ? AND v.voucher_date <= ?))";

        $params = [
            $fromDate, $toDate,
            $fromDate, $toDate,
            $fromDate, $toDate,
            $fromDate,
            $fromDate,
            $fromDate, $fromDate, $toDate,
        ];

        if ($voucherType !== null && $voucherType !== '') {
            $sql .= ' AND v.voucher_type = ?';
            $params[] = $voucherType;
        }

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        // Master openings only when not filtering by voucher type (full books view).
        $masterOpeningsNet = 0.0;
        if ($voucherType === null || $voucherType === '') {
            $masterOpeningsNet = AccountingBalanceService::masterOpeningsNet($pdo);
        }

        return AccountingBalanceService::periodSummary(
            (int) ($row['total_records'] ?? 0),
            (float) ($row['debit_before'] ?? 0),
            (float) ($row['credit_before'] ?? 0),
            (float) ($row['total_debit'] ?? 0),
            (float) ($row['total_credit'] ?? 0),
            $masterOpeningsNet
        );
    }

    /** @return list<array<string,mixed>> */
    public static function getCashBook(PDO $pdo, string $fromDate, string $toDate, int $cashAccountId): array
    {
        $valid = AccountingBalanceService::validVoucherPredicate('v');
        $sql = "SELECT le.entry_date, le.voucher_number, le.voucher_type, le.narration,
                       le.debit_amount, le.credit_amount, le.running_balance
                FROM ledger_entries le
                INNER JOIN vouchers v ON v.id = le.voucher_id
                WHERE le.account_id = ?
                  AND {$valid}
                  AND le.entry_date BETWEEN ? AND ?
                ORDER BY le.entry_date ASC, le.id ASC";

        $st = $pdo->prepare($sql);
        $st->execute([$cashAccountId, $fromDate, $toDate]);
        $entries = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Opening = account balance as of the day before From Date (master + prior POSTED).
        $dayBefore = date('Y-m-d', strtotime($fromDate . ' -1 day'));
        $runningBalance = AccountRepository::getBalance($pdo, $cashAccountId, $dayBefore);
        foreach ($entries as &$entry) {
            $debit = (float) ($entry['debit_amount'] ?? 0);
            $credit = (float) ($entry['credit_amount'] ?? 0);
            // Cash/Bank are DEBIT-normal assets: Running = Opening + Debit − Credit
            $runningBalance = AccountingBalanceService::calculateRunningBalance('DEBIT', $runningBalance, $debit, $credit);
            $entry['running_balance'] = $runningBalance;
        }

        return $entries;
    }

    /** @return array<string,mixed> */
    public static function getTrialBalance(PDO $pdo, string $asOfDate): array
    {
        $valid = AccountingBalanceService::validVoucherPredicate('v');
        $st = $pdo->prepare(
            "SELECT a.id, a.account_code, a.account_name, a.opening_balance, a.opening_balance_type,
                    a.normal_balance, ag.group_name, ag.group_type, ag.nature AS group_nature,
                    COALESCE(SUM(le.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(le.credit_amount), 0) AS total_credit
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             LEFT JOIN ledger_entries le ON le.account_id = a.id
               AND le.entry_date <= ?
               AND EXISTS (
                   SELECT 1 FROM vouchers v
                   WHERE v.id = le.voucher_id AND {$valid}
               )
             WHERE a.deleted_at IS NULL
             GROUP BY a.id, a.account_code, a.account_name, a.opening_balance, a.opening_balance_type,
                      a.normal_balance, ag.group_name, ag.group_type, ag.nature
             ORDER BY ag.group_type, a.account_code"
        );
        $st->execute([$asOfDate]);
        $accounts = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $trialBalance = [
            'debit_total' => 0,
            'credit_total' => 0,
            'accounts' => [],
        ];

        foreach ($accounts as $account) {
            $normal = AccountingBalanceService::resolveNormalBalance($account);
            $masterSigned = AccountingBalanceService::signMasterOpening(
                (float) ($account['opening_balance'] ?? 0),
                (string) ($account['opening_balance_type'] ?? $normal),
                $normal
            );
            $totalDebit = (float) ($account['total_debit'] ?? 0);
            $totalCredit = (float) ($account['total_credit'] ?? 0);

            $netBalance = AccountingBalanceService::calculateLedgerClosingBalance(
                $normal,
                $masterSigned,
                $totalDebit,
                $totalCredit
            );

            if (abs($netBalance) < 0.01) {
                continue;
            }

            $display = AccountingBalanceService::displayLedgerBalance($normal, $netBalance);
            $debitAmount = $display['type'] === 'DEBIT' ? $display['amount'] : 0.0;
            $creditAmount = $display['type'] === 'CREDIT' ? $display['amount'] : 0.0;

            $trialBalance['accounts'][] = [
                'account_code' => $account['account_code'],
                'account_name' => $account['account_name'],
                'group_name' => $account['group_name'],
                'group_type' => $account['group_type'],
                'normal_balance' => $normal,
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
            ];

            $trialBalance['debit_total'] += $debitAmount;
            $trialBalance['credit_total'] += $creditAmount;
        }

        return $trialBalance;
    }

    /** @return array<string,mixed> */
    public static function getProfitLoss(PDO $pdo, string $fromDate, string $toDate): array
    {
        $valid = AccountingBalanceService::validVoucherPredicate('v');
        $st = $pdo->prepare(
            "SELECT a.id, a.account_code, a.account_name,
                    ag.group_name, ag.group_type,
                    COALESCE(SUM(le.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(le.credit_amount), 0) AS total_credit
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             INNER JOIN ledger_entries le ON le.account_id = a.id
             INNER JOIN vouchers v ON v.id = le.voucher_id AND {$valid}
             WHERE le.entry_date BETWEEN ? AND ?
             AND ag.group_type IN (?, ?)
             AND a.deleted_at IS NULL
             GROUP BY a.id, a.account_code, a.account_name, ag.group_name, ag.group_type
             ORDER BY ag.group_type, a.account_code"
        );
        $st->execute([$fromDate, $toDate, 'INCOME', 'EXPENSES']);
        $accounts = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $profitLoss = [
            'total_income' => 0,
            'total_expenses' => 0,
            'net_profit' => 0,
            'income_accounts' => [],
            'expense_accounts' => [],
        ];

        foreach ($accounts as $account) {
            $totalDebit = (float) ($account['total_debit'] ?? 0);
            $totalCredit = (float) ($account['total_credit'] ?? 0);
            $groupType = $account['group_type'];

            if ($groupType === 'INCOME') {
                // Income nature: credit increases income
                $amount = $totalCredit - $totalDebit;
                $profitLoss['total_income'] += $amount;
                $profitLoss['income_accounts'][] = [
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'group_name' => $account['group_name'],
                    'amount' => $amount,
                ];
            } else {
                // Expense nature: debit increases expense
                $amount = $totalDebit - $totalCredit;
                $profitLoss['total_expenses'] += $amount;
                $profitLoss['expense_accounts'][] = [
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'group_name' => $account['group_name'],
                    'amount' => $amount,
                ];
            }
        }

        $profitLoss['net_profit'] = $profitLoss['total_income'] - $profitLoss['total_expenses'];

        return $profitLoss;
    }

    /** @return array<string,mixed> */
    public static function getBalanceSheet(PDO $pdo, string $asOfDate): array
    {
        $valid = AccountingBalanceService::validVoucherPredicate('v');
        $st = $pdo->prepare(
            "SELECT a.id, a.account_code, a.account_name, a.opening_balance, a.opening_balance_type,
                    ag.group_name, ag.group_type, ag.nature AS group_nature,
                    COALESCE(SUM(le.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(le.credit_amount), 0) AS total_credit
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             LEFT JOIN ledger_entries le ON le.account_id = a.id
               AND le.entry_date <= ?
               AND EXISTS (
                   SELECT 1 FROM vouchers v
                   WHERE v.id = le.voucher_id AND {$valid}
               )
             WHERE a.deleted_at IS NULL AND ag.group_type IN (?, ?, ?)
             GROUP BY a.id, a.account_code, a.account_name, a.opening_balance, a.opening_balance_type,
                      ag.group_name, ag.group_type, ag.nature
             ORDER BY ag.group_type, a.account_code"
        );
        $st->execute([$asOfDate, 'ASSETS', 'LIABILITIES', 'CAPITAL']);
        $accounts = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $balanceSheet = [
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_capital' => 0,
            'assets' => [],
            'liabilities' => [],
            'capital' => [],
        ];

        foreach ($accounts as $account) {
            $openingBalance = AccountingBalanceService::signedAmount(
                (float) ($account['opening_balance'] ?? 0),
                (string) ($account['opening_balance_type'] ?? 'CREDIT')
            );
            $totalDebit = (float) ($account['total_debit'] ?? 0);
            $totalCredit = (float) ($account['total_credit'] ?? 0);
            $groupType = $account['group_type'];

            // Net = Opening + Credit − Debit
            $netBalance = AccountingBalanceService::calculateClosingBalance($openingBalance, $totalDebit, $totalCredit);

            if ($groupType === 'ASSETS') {
                $amount = $netBalance;
            } else {
                // Liabilities / Capital presented as opposite of asset-side net
                $amount = -$netBalance;
            }

            if (abs($amount) < 0.01) {
                continue;
            }

            if ($groupType === 'ASSETS') {
                $balanceSheet['total_assets'] += $amount;
                $balanceSheet['assets'][] = [
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'group_name' => $account['group_name'],
                    'amount' => $amount,
                ];
            } elseif ($groupType === 'LIABILITIES') {
                $balanceSheet['total_liabilities'] += $amount;
                $balanceSheet['liabilities'][] = [
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'group_name' => $account['group_name'],
                    'amount' => $amount,
                ];
            } elseif ($groupType === 'CAPITAL') {
                $balanceSheet['total_capital'] += $amount;
                $balanceSheet['capital'][] = [
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'group_name' => $account['group_name'],
                    'amount' => $amount,
                ];
            }
        }

        $fiscalYearStart = substr($asOfDate, 0, 4) . '-01-01';
        $profitLoss = self::getProfitLoss($pdo, $fiscalYearStart, $asOfDate);
        $netProfit = (float) ($profitLoss['net_profit'] ?? 0);
        if (abs($netProfit) >= 0.01) {
            $balanceSheet['capital'][] = [
                'account_code' => '',
                'account_name' => $netProfit >= 0 ? 'Current Year Profit' : 'Current Year Loss',
                'group_name' => 'Capital',
                'amount' => $netProfit,
            ];
            $balanceSheet['total_capital'] += $netProfit;
        }
        $balanceSheet['net_profit'] = $netProfit;

        return $balanceSheet;
    }
}
