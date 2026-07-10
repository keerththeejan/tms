<?php

declare(strict_types=1);

/**
 * Customer Ledger — links TMS customers to Accounts Receivable (SUNDRY_DEBTORS) accounts.
 */
class CustomerLedgerRepository
{
    public const LEDGER_TYPE = 'Accounts Receivable';
    public const CODE_PREFIX = 'AR-CUS-';

    private static bool $schemaChecked = false;

    public static function ensureSchema(PDO $pdo): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;
        AccountingSchemaRepository::ensureSchema($pdo);
        AccountingSchemaRepository::ensureCustomerLedgerTable($pdo);
    }

    /**
     * Create a customer ledger account if missing. Idempotent.
     *
     * @return int customer_ledger.id
     */
    public static function ensureForCustomer(PDO $pdo, int $customerId, string $customerName, bool $isActive = true, ?int $userId = null): int
    {
        self::ensureSchema($pdo);
        $name = trim($customerName);
        if ($customerId <= 0) {
            throw new InvalidArgumentException('Invalid customer id.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('Customer name is required for ledger link.');
        }

        $existing = self::getByCustomerId($pdo, $customerId);
        if ($existing) {
            self::syncCustomerName($pdo, $customerId, $name);
            self::setActiveForCustomer($pdo, $customerId, $isActive);

            return (int) $existing['id'];
        }

        $groupId = self::sundryDebtorsGroupId($pdo);
        $ledgerCode = self::nextLedgerCode($pdo);
        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $account = AccountRepository::create($pdo, [
                'account_code' => $ledgerCode,
                'account_name' => $name,
                'account_group_id' => $groupId,
                'opening_balance' => 0,
                'opening_balance_type' => 'DEBIT',
                'is_active' => $isActive ? 1 : 0,
                'is_system' => 0,
                'created_by' => $userId,
            ]);
            $accountId = (int) $account['id'];

            $st = $pdo->prepare(
                'INSERT INTO customer_ledger (customer_id, account_id, ledger_code, ledger_type)
                 VALUES (?, ?, ?, ?)'
            );
            $st->execute([$customerId, $accountId, $ledgerCode, self::LEDGER_TYPE]);
            $ledgerId = (int) $pdo->lastInsertId();

            if ($ownTransaction) {
                $pdo->commit();
            }

            return $ledgerId;
        } catch (Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Resolve linked AR account id, creating the ledger link when needed. */
    public static function resolveAccountIdForCustomer(PDO $pdo, int $customerId, string $customerName, ?int $userId = null): int
    {
        self::ensureSchema($pdo);
        $row = self::getByCustomerId($pdo, $customerId);
        if ($row) {
            return (int) $row['account_id'];
        }

        self::ensureForCustomer($pdo, $customerId, $customerName, true, $userId);
        $row = self::getByCustomerId($pdo, $customerId);

        return (int) ($row['account_id'] ?? 0);
    }

    public static function getAccountIdForCustomer(PDO $pdo, int $customerId): ?int
    {
        self::ensureSchema($pdo);
        $row = self::getByCustomerId($pdo, $customerId);

        return $row ? (int) $row['account_id'] : null;
    }

    /** @return array<string,mixed>|null */
    public static function getByCustomerId(PDO $pdo, int $customerId): ?array
    {
        self::ensureSchema($pdo);
        $st = $pdo->prepare(
            'SELECT cl.*, a.account_name, a.is_active, a.opening_balance, a.opening_balance_type, a.deleted_at
             FROM customer_ledger cl
             INNER JOIN accounts a ON a.id = cl.account_id
             WHERE cl.customer_id = ?
             LIMIT 1'
        );
        $st->execute([$customerId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public static function getByAccountId(PDO $pdo, int $accountId): ?array
    {
        self::ensureSchema($pdo);
        $st = $pdo->prepare(
            'SELECT cl.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email,
                    a.account_name, a.is_active
             FROM customer_ledger cl
             INNER JOIN accounts a ON a.id = cl.account_id
             INNER JOIN customers c ON c.id = cl.customer_id
             WHERE cl.account_id = ?
             LIMIT 1'
        );
        $st->execute([$accountId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function syncCustomerName(PDO $pdo, int $customerId, string $customerName): void
    {
        self::ensureSchema($pdo);
        $name = trim($customerName);
        if ($name === '') {
            return;
        }
        $row = self::getByCustomerId($pdo, $customerId);
        if (!$row) {
            return;
        }
        AccountRepository::update($pdo, (int) $row['account_id'], ['account_name' => $name]);
    }

    public static function setActiveForCustomer(PDO $pdo, int $customerId, bool $isActive): void
    {
        self::ensureSchema($pdo);
        $row = self::getByCustomerId($pdo, $customerId);
        if (!$row) {
            return;
        }
        AccountRepository::update($pdo, (int) $row['account_id'], ['is_active' => $isActive ? 1 : 0]);
    }

    public static function accountHasLedgerTransactions(PDO $pdo, int $accountId): bool
    {
        self::ensureSchema($pdo);
        $st = $pdo->prepare('SELECT COUNT(*) FROM ledger_entries WHERE account_id = ?');
        $st->execute([$accountId]);
        if ((int) $st->fetchColumn() > 0) {
            return true;
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM voucher_details WHERE account_id = ?');
        $st->execute([$accountId]);

        return (int) $st->fetchColumn() > 0;
    }

    public static function customerHasLedgerTransactions(PDO $pdo, int $customerId): bool
    {
        $accountId = self::getAccountIdForCustomer($pdo, $customerId);
        if ($accountId === null) {
            return false;
        }

        return self::accountHasLedgerTransactions($pdo, $accountId);
    }

    public static function detachForDeletedCustomer(PDO $pdo, int $customerId): void
    {
        self::ensureSchema($pdo);
        $row = self::getByCustomerId($pdo, $customerId);
        if (!$row) {
            return;
        }
        $accountId = (int) $row['account_id'];
        $pdo->prepare('DELETE FROM customer_ledger WHERE customer_id = ?')->execute([$customerId]);

        if (!self::accountHasLedgerTransactions($pdo, $accountId)) {
            try {
                AccountRepository::delete($pdo, $accountId);
            } catch (Throwable $e) {
                AccountRepository::update($pdo, $accountId, ['is_active' => 0]);
            }
        } else {
            AccountRepository::update($pdo, $accountId, ['is_active' => 0]);
        }
    }

    /** @return array{created:int, skipped:int, errors:list<string>} */
    public static function syncMissingForAllCustomers(PDO $pdo, ?int $userId = null): array
    {
        self::ensureSchema($pdo);
        $created = 0;
        $skipped = 0;
        $errors = [];
        $rows = $pdo->query('SELECT id, name FROM customers ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            $customerId = (int) ($row['id'] ?? 0);
            $name = trim((string) ($row['name'] ?? ''));
            if ($customerId <= 0 || $name === '') {
                $skipped++;
                continue;
            }
            if (self::getByCustomerId($pdo, $customerId)) {
                $skipped++;
                continue;
            }
            try {
                self::ensureForCustomer($pdo, $customerId, $name, true, $userId);
                $created++;
            } catch (Throwable $e) {
                $errors[] = 'Customer #' . $customerId . ': ' . $e->getMessage();
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    public static function syncMissingIfNeeded(PDO $pdo, ?int $userId = null): void
    {
        self::ensureSchema($pdo);
        $missing = (int) $pdo->query(
            'SELECT COUNT(*) FROM customers c
             LEFT JOIN customer_ledger cl ON cl.customer_id = c.id
             WHERE cl.id IS NULL'
        )->fetchColumn();
        if ($missing === 0) {
            return;
        }
        self::syncMissingForAllCustomers($pdo, $userId);
    }

    /**
     * Operational + accounting stats for one customer.
     *
     * @return array<string,mixed>
     */
    public static function getCustomerStats(PDO $pdo, int $customerId, ?int $accountId = null): array
    {
        self::ensureSchema($pdo);
        $accountId = $accountId ?? self::getAccountIdForCustomer($pdo, $customerId);

        $invoiceStmt = $pdo->prepare(
            'SELECT COUNT(*) AS invoice_count,
                    COALESCE(SUM(total_amount - COALESCE(discount, 0)), 0) AS total_invoices
             FROM delivery_notes
             WHERE customer_id = ?'
        );
        $invoiceStmt->execute([$customerId]);
        $invoiceRow = $invoiceStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $payStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(p.amount), 0) AS total_payments
             FROM payments p
             INNER JOIN delivery_notes dn ON dn.id = p.delivery_note_id
             WHERE dn.customer_id = ?'
        );
        $payStmt->execute([$customerId]);
        $payRow = $payStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalInvoices = (float) ($invoiceRow['total_invoices'] ?? 0);
        $totalPayments = (float) ($payRow['total_payments'] ?? 0);
        $debitNotes = 0.0;
        $creditNotes = 0.0;

        if ($accountId !== null && $accountId > 0) {
            $debitNotes = self::sumLedgerDebitsExcludingSales($pdo, $accountId);
            $creditNotes = self::sumLedgerCreditsExcludingReceipts($pdo, $accountId);
        }

        $outstanding = $totalInvoices + $debitNotes - $totalPayments - $creditNotes;
        $currentBalance = ($accountId !== null && $accountId > 0)
            ? AccountRepository::getBalance($pdo, $accountId)
            : 0.0;

        return [
            'total_invoices' => round($totalInvoices, 2),
            'invoice_count' => (int) ($invoiceRow['invoice_count'] ?? 0),
            'total_payments' => round($totalPayments, 2),
            'debit_notes' => round($debitNotes, 2),
            'credit_notes' => round($creditNotes, 2),
            'outstanding_amount' => round(max(0, $outstanding), 2),
            'current_balance' => round($currentBalance, 2),
        ];
    }

    /**
     * @param list<array<string,mixed>> $customers
     * @return list<array<string,mixed>>
     */
    public static function enrichCustomerRows(PDO $pdo, array $customers): array
    {
        if ($customers === []) {
            return [];
        }
        self::ensureSchema($pdo);

        $ids = array_values(array_filter(array_map(static fn ($c) => (int) ($c['id'] ?? 0), $customers)));
        if ($ids === []) {
            return $customers;
        }

        $ledgerMap = [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare(
            "SELECT cl.customer_id, cl.ledger_code, cl.account_id, a.is_active
             FROM customer_ledger cl
             INNER JOIN accounts a ON a.id = cl.account_id
             WHERE cl.customer_id IN ($placeholders)"
        );
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $ledgerMap[(int) $row['customer_id']] = $row;
        }

        $invoiceMap = [];
        $st = $pdo->prepare(
            "SELECT customer_id,
                    COUNT(*) AS invoice_count,
                    COALESCE(SUM(total_amount - COALESCE(discount, 0)), 0) AS total_invoices
             FROM delivery_notes
             WHERE customer_id IN ($placeholders)
             GROUP BY customer_id"
        );
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $invoiceMap[(int) $row['customer_id']] = $row;
        }

        $payMap = [];
        $st = $pdo->prepare(
            "SELECT dn.customer_id, COALESCE(SUM(p.amount), 0) AS total_payments
             FROM payments p
             INNER JOIN delivery_notes dn ON dn.id = p.delivery_note_id
             WHERE dn.customer_id IN ($placeholders)
             GROUP BY dn.customer_id"
        );
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $payMap[(int) $row['customer_id']] = $row;
        }

        foreach ($customers as &$customer) {
            $cid = (int) ($customer['id'] ?? 0);
            $ledger = $ledgerMap[$cid] ?? null;
            $inv = $invoiceMap[$cid] ?? [];
            $pay = $payMap[$cid] ?? [];
            $totalInvoices = (float) ($inv['total_invoices'] ?? 0);
            $totalPayments = (float) ($pay['total_payments'] ?? 0);
            $outstanding = max(0, $totalInvoices - $totalPayments);

            $customer['ledger_code'] = $ledger['ledger_code'] ?? null;
            $customer['ledger_account_id'] = isset($ledger['account_id']) ? (int) $ledger['account_id'] : null;
            $customer['ledger_active'] = isset($ledger['is_active']) ? (int) $ledger['is_active'] === 1 : null;
            $customer['total_invoices'] = round($totalInvoices, 2);
            $customer['total_payments'] = round($totalPayments, 2);
            $customer['outstanding_amount'] = round($outstanding, 2);
        }
        unset($customer);

        return $customers;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function listWithStats(PDO $pdo, array $filters = []): array
    {
        self::ensureSchema($pdo);
        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $sql = 'SELECT cl.id, cl.customer_id, cl.account_id, cl.ledger_code, cl.ledger_type, cl.created_at,
                       c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email,
                       a.account_name, a.is_active
                FROM customer_ledger cl
                INNER JOIN customers c ON c.id = cl.customer_id
                INNER JOIN accounts a ON a.id = cl.account_id
                WHERE a.deleted_at IS NULL';
        $params = [];

        if ($q !== '') {
            $sql .= ' AND (c.name LIKE ? OR cl.ledger_code LIKE ? OR c.phone LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($status === 'active') {
            $sql .= ' AND a.is_active = 1';
        } elseif ($status === 'inactive') {
            $sql .= ' AND a.is_active = 0';
        }

        $sql .= ' ORDER BY cl.ledger_code ASC LIMIT 500';
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $stats = self::getCustomerStats($pdo, (int) $row['customer_id'], (int) $row['account_id']);
            $row = array_merge($row, $stats);
        }
        unset($row);

        return $rows;
    }

    private static function sundryDebtorsGroupId(PDO $pdo): int
    {
        $st = $pdo->prepare('SELECT id FROM account_groups WHERE group_code = ? LIMIT 1');
        $st->execute(['SUNDRY_DEBTORS']);
        $groupId = $st->fetchColumn();
        if (!$groupId) {
            throw new RuntimeException('Sundry Debtors group not found in Chart of Accounts.');
        }

        return (int) $groupId;
    }

    private static function nextLedgerCode(PDO $pdo): string
    {
        $maxNum = 0;
        $st = $pdo->query(
            "SELECT ledger_code FROM customer_ledger
             WHERE ledger_code LIKE 'AR-CUS-%'
             ORDER BY CAST(SUBSTRING(ledger_code, 8) AS UNSIGNED) DESC, id DESC
             LIMIT 1"
        );
        $last = $st ? $st->fetchColumn() : false;
        if (is_string($last) && preg_match('/AR-CUS-(\d+)/', $last, $m)) {
            $maxNum = (int) $m[1];
        }

        $st2 = $pdo->query(
            "SELECT account_code FROM accounts
             WHERE account_code LIKE 'AR-CUS-%' AND deleted_at IS NULL
             ORDER BY CAST(SUBSTRING(account_code, 8) AS UNSIGNED) DESC, id DESC
             LIMIT 1"
        );
        $lastAcc = $st2 ? $st2->fetchColumn() : false;
        if (is_string($lastAcc) && preg_match('/AR-CUS-(\d+)/', $lastAcc, $m2)) {
            $maxNum = max($maxNum, (int) $m2[1]);
        }

        return sprintf('AR-CUS-%05d', $maxNum + 1);
    }

    private static function sumLedgerDebitsExcludingSales(PDO $pdo, int $accountId): float
    {
        $st = $pdo->prepare(
            'SELECT COALESCE(SUM(le.debit_amount), 0)
             FROM ledger_entries le
             INNER JOIN vouchers v ON v.id = le.voucher_id
             WHERE le.account_id = ?
               AND le.debit_amount > 0
               AND v.voucher_type NOT IN (\'RECEIPT\')'
        );
        $st->execute([$accountId]);

        return (float) $st->fetchColumn();
    }

    private static function sumLedgerCreditsExcludingReceipts(PDO $pdo, int $accountId): float
    {
        $st = $pdo->prepare(
            'SELECT COALESCE(SUM(le.credit_amount), 0)
             FROM ledger_entries le
             INNER JOIN vouchers v ON v.id = le.voucher_id
             WHERE le.account_id = ?
               AND le.credit_amount > 0
               AND v.voucher_type NOT IN (\'RECEIPT\', \'PAYMENT\')'
        );
        $st->execute([$accountId]);

        return (float) $st->fetchColumn();
    }
}
