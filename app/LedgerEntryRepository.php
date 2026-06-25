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
        $sql = 'SELECT le.*, v.voucher_date, v.voucher_type, v.narration AS voucher_narration
                FROM ledger_entries le
                INNER JOIN vouchers v ON v.id = le.voucher_id
                WHERE le.account_id = ?';
        
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

    /** @return list<array<string,mixed>> */
    public static function getDayBook(PDO $pdo, string $fromDate, string $toDate, ?string $voucherType = null): array
    {
        $sql = 'SELECT le.entry_date, le.voucher_number, le.voucher_type, a.account_name, 
                       le.narration, le.debit_amount, le.credit_amount
                FROM ledger_entries le
                INNER JOIN accounts a ON a.id = le.account_id
                WHERE le.entry_date BETWEEN ? AND ?';
        
        $params = [$fromDate, $toDate];
        
        if ($voucherType) {
            $sql .= ' AND le.voucher_type = ?';
            $params[] = $voucherType;
        }
        
        $sql .= ' ORDER BY le.entry_date ASC, le.voucher_number ASC, le.id ASC';
        
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function getCashBook(PDO $pdo, string $fromDate, string $toDate, int $cashAccountId): array
    {
        $sql = 'SELECT le.entry_date, le.voucher_number, le.voucher_type, le.narration, 
                       le.debit_amount, le.credit_amount, le.running_balance
                FROM ledger_entries le
                WHERE le.account_id = ? AND le.entry_date BETWEEN ? AND ?
                ORDER BY le.entry_date ASC, le.id ASC';
        
        $st = $pdo->prepare($sql);
        $st->execute([$cashAccountId, $fromDate, $toDate]);
        $entries = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $dayBefore = date('Y-m-d', strtotime($fromDate . ' -1 day'));
        $runningBalance = AccountRepository::getBalance($pdo, $cashAccountId, $dayBefore);
        foreach ($entries as &$entry) {
            $debit = (float) ($entry['debit_amount'] ?? 0);
            $credit = (float) ($entry['credit_amount'] ?? 0);
            $runningBalance += $debit - $credit;
            $entry['running_balance'] = $runningBalance;
        }

        return $entries;
    }

    /** @return array<string,mixed> */
    public static function getTrialBalance(PDO $pdo, string $asOfDate): array
    {
        $st = $pdo->prepare(
            'SELECT a.id, a.account_code, a.account_name, a.opening_balance, a.opening_balance_type,
                    ag.group_name, ag.group_type, ag.nature AS group_nature,
                    COALESCE(SUM(le.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(le.credit_amount), 0) AS total_credit
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             LEFT JOIN ledger_entries le ON le.account_id = a.id AND le.entry_date <= ?
             WHERE a.deleted_at IS NULL
             GROUP BY a.id, a.account_code, a.account_name, a.opening_balance, a.opening_balance_type, 
                      ag.group_name, ag.group_type, ag.nature
             ORDER BY ag.group_type, a.account_code'
        );
        $st->execute([$asOfDate]);
        $accounts = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $trialBalance = [
            'debit_total' => 0,
            'credit_total' => 0,
            'accounts' => [],
        ];

        foreach ($accounts as $account) {
            $openingBalance = (float) ($account['opening_balance'] ?? 0);
            $openingBalanceType = $account['opening_balance_type'] ?? 'DEBIT';
            $totalDebit = (float) ($account['total_debit'] ?? 0);
            $totalCredit = (float) ($account['total_credit'] ?? 0);
            $groupNature = $account['group_nature'] ?? 'DEBIT';

            // Calculate net balance
            if ($openingBalanceType === 'CREDIT') {
                $openingBalance = -$openingBalance;
            }
            
            $netBalance = $openingBalance + $totalDebit - $totalCredit;

            if (abs($netBalance) < 0.01) {
                continue;
            }

            if ($netBalance >= 0) {
                $debitAmount = $netBalance;
                $creditAmount = 0;
            } else {
                $debitAmount = 0;
                $creditAmount = abs($netBalance);
            }

            $trialBalance['accounts'][] = [
                'account_code' => $account['account_code'],
                'account_name' => $account['account_name'],
                'group_name' => $account['group_name'],
                'group_type' => $account['group_type'],
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
        $st = $pdo->prepare(
            'SELECT a.id, a.account_code, a.account_name,
                    ag.group_name, ag.group_type,
                    COALESCE(SUM(le.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(le.credit_amount), 0) AS total_credit
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             INNER JOIN ledger_entries le ON le.account_id = a.id
             INNER JOIN vouchers v ON v.id = le.voucher_id AND v.deleted_at IS NULL
             WHERE le.entry_date BETWEEN ? AND ?
             AND ag.group_type IN (?, ?)
             AND a.deleted_at IS NULL
             GROUP BY a.id, a.account_code, a.account_name, ag.group_name, ag.group_type
             ORDER BY ag.group_type, a.account_code'
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
                $amount = $totalCredit - $totalDebit;
                $profitLoss['total_income'] += $amount;
                $profitLoss['income_accounts'][] = [
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'group_name' => $account['group_name'],
                    'amount' => $amount,
                ];
            } else {
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
        $st = $pdo->prepare(
            'SELECT a.id, a.account_code, a.account_name, a.opening_balance, a.opening_balance_type,
                    ag.group_name, ag.group_type, ag.nature AS group_nature,
                    COALESCE(SUM(le.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(le.credit_amount), 0) AS total_credit
             FROM accounts a
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             LEFT JOIN ledger_entries le ON le.account_id = a.id AND le.entry_date <= ?
             WHERE a.deleted_at IS NULL AND ag.group_type IN (?, ?, ?)
             GROUP BY a.id, a.account_code, a.account_name, a.opening_balance, a.opening_balance_type, 
                      ag.group_name, ag.group_type, ag.nature
             ORDER BY ag.group_type, a.account_code'
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
            $openingBalance = (float) ($account['opening_balance'] ?? 0);
            $openingBalanceType = $account['opening_balance_type'] ?? 'DEBIT';
            $totalDebit = (float) ($account['total_debit'] ?? 0);
            $totalCredit = (float) ($account['total_credit'] ?? 0);
            $groupType = $account['group_type'];
            $groupNature = $account['group_nature'] ?? 'DEBIT';

            // Calculate net balance
            if ($openingBalanceType === 'CREDIT') {
                $openingBalance = -$openingBalance;
            }
            
            $netBalance = $openingBalance + $totalDebit - $totalCredit;

            if ($groupType === 'ASSETS') {
                $amount = $netBalance;
            } else {
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
