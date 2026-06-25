<?php

declare(strict_types=1);

/**
 * Posts expense transactions to the accounting module (GL, cash book, bank book, AP).
 */
class ExpenseAccountingService
{
    public static function postExpense(PDO $pdo, int $expenseId, int $userId): ?array
    {
        $expense = ExpenseRepository::getById($pdo, $expenseId);
        if (!$expense) {
            throw new RuntimeException('Expense not found.');
        }
        if (!empty($expense['voucher_id'])) {
            return AccountingVoucherRepository::getById($pdo, (int) $expense['voucher_id']);
        }

        $total = (float) ($expense['total_amount'] ?? $expense['amount'] ?? 0);
        if ($total <= 0) {
            throw new RuntimeException('Expense amount must be positive to post.');
        }

        $method = strtolower((string) ($expense['payment_method'] ?? $expense['payment_mode'] ?? 'cash'));
        $expenseAccountId = self::resolveExpenseAccountId($pdo, $expense);
        $creditAccountId = self::resolveCreditAccountId($pdo, $expense, $method);

        $narration = trim(
            'Expense ' . ($expense['expense_number'] ?? $expenseId)
            . ' - ' . ($expense['category_name'] ?? $expense['expense_type'] ?? 'General')
        );

        $ownTxn = !$pdo->inTransaction();
        if ($ownTxn) {
            $pdo->beginTransaction();
        }

        try {
            AccountingVoucherRepository::ensureSchema($pdo);

            $voucher = AccountingVoucherRepository::create($pdo, [
                'voucher_type' => 'PAYMENT',
                'voucher_date' => $expense['expense_date'] ?? date('Y-m-d'),
                'fiscal_year' => date('Y', strtotime((string) ($expense['expense_date'] ?? 'now'))),
                'reference_number' => $expense['reference_number'] ?? $expense['expense_number'] ?? null,
                'payment_mode' => strtoupper($method === 'credit' ? 'CREDIT' : ($method === 'bank' || $method === 'transfer' ? 'BANK' : 'CASH')),
                'bank_account_id' => in_array($method, ['bank', 'cheque', 'transfer'], true)
                    ? ($expense['payment_account_id'] ?? null)
                    : null,
                'narration' => $narration,
                'total_debit' => $total,
                'total_credit' => $total,
                'branch_id' => $expense['branch_id'] ?? null,
                'created_by' => $userId,
            ]);

            $voucherId = (int) $voucher['id'];
            $details = [
                [
                    'account_id' => $expenseAccountId,
                    'debit_amount' => $total,
                    'credit_amount' => 0,
                    'narration' => $narration,
                ],
                [
                    'account_id' => $creditAccountId,
                    'debit_amount' => 0,
                    'credit_amount' => $total,
                    'narration' => 'Payment for ' . ($expense['expense_number'] ?? 'expense'),
                ],
            ];
            VoucherDetailRepository::createBatch($pdo, $voucherId, $details);

            self::postVoucherToLedger($pdo, $voucherId, $userId);

            $pdo->prepare('UPDATE expenses SET voucher_id = ? WHERE id = ?')->execute([$voucherId, $expenseId]);

            try {
                $st = $pdo->prepare(
                    'INSERT INTO transport_voucher_mapping (voucher_id, transport_type, transport_id, mapping_details)
                     VALUES (?, ?, ?, ?)'
                );
                $st->execute([
                    $voucherId,
                    'EXPENSE',
                    $expenseId,
                    json_encode(['expense_id' => $expenseId, 'expense_number' => $expense['expense_number'] ?? null]),
                ]);
            } catch (Throwable $e) {
                /* mapping table optional */
            }

            if ($ownTxn) {
                $pdo->commit();
            }

            return AccountingVoucherRepository::getById($pdo, $voucherId);
        } catch (Throwable $e) {
            if ($ownTxn && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function postSettlement(PDO $pdo, int $expenseId, float $amount, int $userId): ?array
    {
        if ($amount <= 0) {
            return null;
        }

        $expense = ExpenseRepository::getById($pdo, $expenseId);
        if (!$expense) {
            throw new RuntimeException('Expense not found.');
        }

        $creditAccountId = self::resolveCreditAccountId($pdo, $expense, 'credit');
        $cashAccountId = self::resolveCashAccountId($pdo, $expense);

        $ownTxn = !$pdo->inTransaction();
        if ($ownTxn) {
            $pdo->beginTransaction();
        }

        try {
            AccountingVoucherRepository::ensureSchema($pdo);

            $voucher = AccountingVoucherRepository::create($pdo, [
                'voucher_type' => 'PAYMENT',
                'voucher_date' => date('Y-m-d'),
                'fiscal_year' => date('Y'),
                'reference_number' => ($expense['expense_number'] ?? 'EXP') . '-SETTLE',
                'payment_mode' => 'CASH',
                'narration' => 'Settlement for expense ' . ($expense['expense_number'] ?? $expenseId),
                'total_debit' => $amount,
                'total_credit' => $amount,
                'branch_id' => $expense['branch_id'] ?? null,
                'created_by' => $userId,
            ]);

            $voucherId = (int) $voucher['id'];
            VoucherDetailRepository::createBatch($pdo, $voucherId, [
                [
                    'account_id' => $creditAccountId,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'narration' => 'Creditor settlement',
                ],
                [
                    'account_id' => $cashAccountId,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'narration' => 'Cash payment',
                ],
            ]);

            self::postVoucherToLedger($pdo, $voucherId, $userId);

            if ($ownTxn) {
                $pdo->commit();
            }

            return AccountingVoucherRepository::getById($pdo, $voucherId);
        } catch (Throwable $e) {
            if ($ownTxn && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function cancelExpenseVoucher(PDO $pdo, int $expenseId, int $userId, string $reason): void
    {
        $expense = ExpenseRepository::getById($pdo, $expenseId);
        if (!$expense || empty($expense['voucher_id'])) {
            return;
        }

        $voucherId = (int) $expense['voucher_id'];
        $voucher = AccountingVoucherRepository::getById($pdo, $voucherId);
        if ($voucher && ($voucher['status'] ?? '') === 'POSTED') {
            AccountingVoucherRepository::cancel($pdo, $voucherId, $reason, $userId);
        }
    }

    private static function postVoucherToLedger(PDO $pdo, int $voucherId, int $userId): void
    {
        $voucher = AccountingVoucherRepository::getById($pdo, $voucherId);
        if (!$voucher) {
            throw new RuntimeException('Voucher not found.');
        }
        if (($voucher['status'] ?? '') === 'POSTED') {
            return;
        }

        $details = VoucherDetailRepository::getByVoucherId($pdo, $voucherId);
        if ($details === []) {
            throw new RuntimeException('Voucher has no line items.');
        }

        $totals = VoucherDetailRepository::getTotals($pdo, $voucherId);
        if (abs($totals['difference']) > 0.01) {
            throw new RuntimeException('Voucher must be balanced.');
        }

        $ledgerEntries = [];
        foreach ($details as $detail) {
            $ledgerEntries[] = [
                'voucher_detail_id' => $detail['id'],
                'account_id' => $detail['account_id'],
                'entry_date' => $voucher['voucher_date'],
                'voucher_type' => $voucher['voucher_type'],
                'voucher_number' => $voucher['voucher_number'],
                'debit_amount' => $detail['debit_amount'],
                'credit_amount' => $detail['credit_amount'],
                'balance_type' => ((float) ($detail['debit_amount'] ?? 0) > 0) ? 'DEBIT' : 'CREDIT',
                'narration' => $detail['narration'] ?? $voucher['narration'],
                'branch_id' => $voucher['branch_id'],
                'reference_id' => $voucherId,
                'reference_type' => 'EXPENSE_VOUCHER',
            ];
        }

        LedgerEntryRepository::createBatch($pdo, $voucherId, $ledgerEntries);

        $st = $pdo->prepare(
            'UPDATE vouchers SET status = ?, posted_at = CURRENT_TIMESTAMP, posted_by = ? WHERE id = ?'
        );
        $st->execute(['POSTED', $userId, $voucherId]);
    }

    /** @param array<string,mixed> $expense */
    private static function resolveExpenseAccountId(PDO $pdo, array $expense): int
    {
        if (!empty($expense['account_id'])) {
            return (int) $expense['account_id'];
        }
        if (!empty($expense['category_id'])) {
            $cat = ExpenseCategoryRepository::getById($pdo, (int) $expense['category_id']);
            if (!empty($cat['account_id'])) {
                return (int) $cat['account_id'];
            }
        }

        $candidates = ['OFFICE_EXPENSE', 'GENERAL_EXPENSE', 'VEH_MAINTENANCE', 'EXPENSE_GENERAL'];
        foreach ($candidates as $code) {
            $acc = AccountRepository::getByCode($pdo, $code);
            if ($acc) {
                return (int) $acc['id'];
            }
        }

        $st = $pdo->query(
            "SELECT a.id FROM accounts a
             INNER JOIN account_groups g ON g.id = a.account_group_id
             WHERE a.deleted_at IS NULL AND g.group_type IN ('EXPENSES','EXPENSE')
             ORDER BY a.id ASC LIMIT 1"
        );
        $id = $st ? $st->fetchColumn() : false;
        if ($id) {
            return (int) $id;
        }

        throw new RuntimeException('No expense account configured in Chart of Accounts.');
    }

    /** @param array<string,mixed> $expense */
    private static function resolveCreditAccountId(PDO $pdo, array $expense, string $method): int
    {
        if ($method === 'credit') {
            return self::resolveSupplierPayableAccountId($pdo, $expense);
        }

        if (in_array($method, ['bank', 'cheque', 'transfer'], true) && !empty($expense['payment_account_id'])) {
            return (int) $expense['payment_account_id'];
        }

        if (in_array($method, ['bank', 'cheque', 'transfer'], true)) {
            $st = $pdo->query(
                "SELECT a.id FROM accounts a
                 INNER JOIN account_groups g ON g.id = a.account_group_id
                 WHERE a.deleted_at IS NULL AND g.group_type = 'BANK'
                 ORDER BY a.id ASC LIMIT 1"
            );
            $id = $st ? $st->fetchColumn() : false;
            if ($id) {
                return (int) $id;
            }
        }

        return self::resolveCashAccountId($pdo, $expense);
    }

    /** @param array<string,mixed> $expense */
    private static function resolveCashAccountId(PDO $pdo, array $expense): int
    {
        if (!empty($expense['payment_account_id'])) {
            return (int) $expense['payment_account_id'];
        }

        foreach (['CASH_MAIN', 'CASH', 'PETTY_CASH'] as $code) {
            $acc = AccountRepository::getByCode($pdo, $code);
            if ($acc) {
                return (int) $acc['id'];
            }
        }

        $st = $pdo->query(
            "SELECT a.id FROM accounts a
             INNER JOIN account_groups g ON g.id = a.account_group_id
             WHERE a.deleted_at IS NULL AND g.group_type = 'CASH'
             ORDER BY a.id ASC LIMIT 1"
        );
        $id = $st ? $st->fetchColumn() : false;
        if ($id) {
            return (int) $id;
        }

        throw new RuntimeException('No cash account configured in Chart of Accounts.');
    }

    /** @param array<string,mixed> $expense */
    private static function resolveSupplierPayableAccountId(PDO $pdo, array $expense): int
    {
        $party = trim((string) ($expense['credit_party'] ?? $expense['supplier_name'] ?? ''));
        if ($party !== '') {
            $st = $pdo->prepare(
                "SELECT a.id FROM accounts a
                 INNER JOIN account_groups g ON g.id = a.account_group_id
                 WHERE a.deleted_at IS NULL AND a.account_name LIKE ?
                   AND g.group_code IN ('SUNDRY_CREDITORS', 'ACCOUNTS_PAYABLE')
                 LIMIT 1"
            );
            $st->execute(['%' . $party . '%']);
            $id = $st->fetchColumn();
            if ($id) {
                return (int) $id;
            }
        }

        foreach (['SUNDRY_CREDITORS', 'ACCOUNTS_PAYABLE', 'CREDITORS'] as $code) {
            $st = $pdo->prepare(
                'SELECT a.id FROM accounts a
                 INNER JOIN account_groups g ON g.id = a.account_group_id
                 WHERE g.group_code = ? LIMIT 1'
            );
            $st->execute([$code]);
            $id = $st->fetchColumn();
            if ($id) {
                return (int) $id;
            }
        }

        return self::createSupplierAccount($pdo, $party !== '' ? $party : 'Supplier');
    }

    private static function createSupplierAccount(PDO $pdo, string $supplierName): int
    {
        $st = $pdo->prepare('SELECT id FROM account_groups WHERE group_code = ? LIMIT 1');
        $st->execute(['SUNDRY_CREDITORS']);
        $groupId = $st->fetchColumn();

        if (!$groupId) {
            throw new RuntimeException('Sundry Creditors group not found in Chart of Accounts.');
        }

        $accountCode = 'SUPP-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $supplierName) ?: 'SUP', 0, 8)) . '-' . time();

        $account = AccountRepository::create($pdo, [
            'account_code' => $accountCode,
            'account_name' => $supplierName,
            'account_group_id' => (int) $groupId,
            'opening_balance' => 0,
            'opening_balance_type' => 'CREDIT',
            'is_active' => 1,
            'is_system' => 0,
        ]);

        return (int) $account['id'];
    }
}
