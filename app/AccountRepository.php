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

    /** @return list<array<string,mixed>> */
    public static function listAccounts(PDO $pdo, bool $includeInactive = false): array
    {
        $sql = 'SELECT a.*, ag.group_name, ag.group_type, ag.nature AS group_nature
                FROM accounts a
                INNER JOIN account_groups ag ON ag.id = a.account_group_id';
        
        if (!$includeInactive) {
            $sql .= ' WHERE a.is_active = 1 AND a.deleted_at IS NULL';
        }
        
        $sql .= ' ORDER BY a.account_code ASC';
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
        $st = $pdo->prepare(
            'INSERT INTO accounts (account_code, account_name, account_group_id, opening_balance, opening_balance_type, is_active, is_system, branch_id, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $data['account_code'],
            $data['account_name'],
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
                $params[] = $data[$field];
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
        // Check if account has voucher details
        $st = $pdo->prepare('SELECT COUNT(*) FROM voucher_details WHERE account_id = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            throw new RuntimeException('Cannot delete account that has voucher entries.');
        }

        $st = $pdo->prepare('SELECT COUNT(*) FROM ledger_entries WHERE account_id = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            throw new RuntimeException('Cannot delete account that has ledger entries.');
        }

        $st = $pdo->prepare('SELECT COUNT(*) FROM customer_ledger WHERE account_id = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            throw new RuntimeException('Cannot delete account linked to a customer ledger.');
        }

        $st = $pdo->prepare('UPDATE accounts SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?');
        return $st->execute([$id]);
    }

    /** @return float */
    public static function getBalance(PDO $pdo, int $accountId, ?string $asOfDate = null): float
    {
        $account = self::getById($pdo, $accountId);
        if (!$account) {
            return 0.0;
        }

        $openingBalance = (float) ($account['opening_balance'] ?? 0);
        $openingBalanceType = $account['opening_balance_type'] ?? 'DEBIT';
        
        // Calculate opening balance sign
        if ($openingBalanceType === 'CREDIT') {
            $openingBalance = -$openingBalance;
        }

        // Get ledger entries
        $sql = 'SELECT SUM(debit_amount) AS total_debit, SUM(credit_amount) AS total_credit
                FROM ledger_entries
                WHERE account_id = ?';
        
        $params = [$accountId];
        
        if ($asOfDate) {
            $sql .= ' AND entry_date <= ?';
            $params[] = $asOfDate;
        }
        
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $result = $st->fetch(PDO::FETCH_ASSOC);
        
        $totalDebit = (float) ($result['total_debit'] ?? 0);
        $totalCredit = (float) ($result['total_credit'] ?? 0);
        
        return $openingBalance + $totalDebit - $totalCredit;
    }

    /** @return list<array<string,mixed>> */
    public static function getLedger(PDO $pdo, int $accountId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $account = self::getById($pdo, $accountId);
        if (!$account) {
            return [];
        }

        $openingBalance = (float) ($account['opening_balance'] ?? 0);
        $openingBalanceType = $account['opening_balance_type'] ?? 'DEBIT';
        
        // Calculate opening balance sign
        if ($openingBalanceType === 'CREDIT') {
            $openingBalance = -$openingBalance;
        }

        // Get opening balance before from date
        $openingBalanceBefore = $openingBalance;
        if ($fromDate) {
            $st = $pdo->prepare(
                'SELECT SUM(debit_amount) AS total_debit, SUM(credit_amount) AS total_credit
                 FROM ledger_entries
                 WHERE account_id = ? AND entry_date < ?'
            );
            $st->execute([$accountId, $fromDate]);
            $result = $st->fetch(PDO::FETCH_ASSOC);
            $totalDebit = (float) ($result['total_debit'] ?? 0);
            $totalCredit = (float) ($result['total_credit'] ?? 0);
            $openingBalanceBefore = $openingBalance + $totalDebit - $totalCredit;
        }

        // Get ledger entries
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
        $entries = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Calculate running balance
        $runningBalance = $openingBalanceBefore;
        foreach ($entries as &$entry) {
            $debit = (float) ($entry['debit_amount'] ?? 0);
            $credit = (float) ($entry['credit_amount'] ?? 0);
            $runningBalance += $debit - $credit;
            $entry['running_balance'] = $runningBalance;
            $entry['balance_type'] = $runningBalance >= 0 ? 'DEBIT' : 'CREDIT';
        }

        return [
            'opening_balance' => abs($openingBalanceBefore),
            'opening_balance_type' => $openingBalanceBefore >= 0 ? 'DEBIT' : 'CREDIT',
            'entries' => $entries,
            'closing_balance' => abs($runningBalance),
            'closing_balance_type' => $runningBalance >= 0 ? 'DEBIT' : 'CREDIT',
        ];
    }
}
