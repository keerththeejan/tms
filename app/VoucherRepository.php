<?php
declare(strict_types=1);

/**
 * VoucherRepository - Data Access Layer for Payment Vouchers
 * 
 * Handles all database operations for vouchers, items, and related entities
 * Implements repository pattern for clean data access
 */
class VoucherRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new voucher record
     */
    public function createVoucher(array $data): int
    {
        $sql = "INSERT INTO vouchers (
            voucher_number, voucher_type, fiscal_year, voucher_date, payment_mode,
            cheque_number, cheque_date, cheque_bank, reference_number, narration,
            status, total_debit, total_credit, balance_amount, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['voucher_number'],
            $data['voucher_type'] ?? 'PAYMENT',
            $data['fiscal_year'],
            $data['voucher_date'],
            $data['payment_mode'] ?? 'CASH',
            $data['cheque_number'] ?? null,
            $data['cheque_date'] ?? null,
            $data['cheque_bank'] ?? null,
            $data['reference_number'] ?? null,
            $data['narration'] ?? null,
            'DRAFT',
            $data['total_debit'] ?? 0,
            $data['total_credit'] ?? 0,
            $data['balance_amount'] ?? 0,
            $data['created_by'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Get voucher by ID
     */
    public function getVoucher(int $id): ?array
    {
        $sql = "SELECT * FROM vouchers WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get voucher by number
     */
    public function getVoucherByNumber(string $voucherNumber): ?array
    {
        $sql = "SELECT * FROM vouchers WHERE voucher_number = ? AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$voucherNumber]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update voucher record
     */
    public function updateVoucher(int $id, array $data): bool
    {
        $updates = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $updates[] = "`{$key}` = ?";
                $values[] = $value;
            }
        }

        $values[] = $id;
        $sql = "UPDATE vouchers SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Add line item to voucher
     */
    public function addLineItem(int $voucherId, array $item): int
    {
        $sql = "INSERT INTO voucher_items (
            voucher_id, line_number, ledger_account_id, account_name, account_code,
            employee_id, customer_id, supplier_id, debit_amount, credit_amount, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $voucherId,
            $item['line_number'] ?? 0,
            $item['ledger_account_id'] ?? null,
            $item['account_name'] ?? '',
            $item['account_code'] ?? '',
            $item['employee_id'] ?? null,
            $item['customer_id'] ?? null,
            $item['supplier_id'] ?? null,
            $item['debit_amount'] ?? 0,
            $item['credit_amount'] ?? 0,
            $item['description'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Get all line items for a voucher
     */
    public function getLineItems(int $voucherId): array
    {
        $sql = "SELECT * FROM voucher_items WHERE voucher_id = ? ORDER BY line_number ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$voucherId]);
        return $stmt->fetchAll();
    }

    /**
     * Delete line item
     */
    public function deleteLineItem(int $itemId): bool
    {
        $sql = "DELETE FROM voucher_items WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$itemId]);
    }

    /**
     * Generate next voucher number
     */
    public function generateVoucherNumber(string $fiscalYear, string $voucherType = 'PAYMENT'): string
    {
        $prefix = substr($voucherType, 0, 3) . '-' . $fiscalYear . '-';
        
        $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(voucher_number, LENGTH(?) + 1) AS UNSIGNED)), 0) + 1 as next_number
                FROM vouchers WHERE voucher_number LIKE ? AND deleted_at IS NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix, $prefix . '%']);
        $result = $stmt->fetch();
        
        $nextNumber = $result['next_number'] ?? 1;
        return $prefix . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get vouchers list with pagination
     */
    public function getVouchersList(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $page = max(1, $page);
        $limit = max(1, min(500, $limit));
        $where = ['deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['voucher_type'])) {
            $where[] = 'voucher_type = ?';
            $params[] = $filters['voucher_type'];
        }

        if (!empty($filters['from_date'])) {
            $where[] = 'voucher_date >= ?';
            $params[] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $where[] = 'voucher_date <= ?';
            $params[] = $filters['to_date'];
        }

        $offset = ($page - 1) * $limit;

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM vouchers WHERE " . implode(' AND ', $where);
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'] ?? 0;

        // Get paginated results
        $sql = "SELECT * FROM vouchers WHERE " . implode(' AND ', $where) . 
               " ORDER BY voucher_date DESC, id DESC LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $vouchers = $stmt->fetchAll();

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
            'data' => $vouchers
        ];
    }

    /**
     * Log transaction in audit trail
     */
    public function logAudit(int $voucherId, array $auditData): int
    {
        $sql = "INSERT INTO transaction_audit_logs (
            voucher_id, action_type, old_values, new_values, changed_fields,
            user_id, ip_address, user_agent, reason, status_before, status_after
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $voucherId,
            $auditData['action_type'] ?? 'UPDATE',
            isset($auditData['old_values']) ? json_encode($auditData['old_values']) : null,
            isset($auditData['new_values']) ? json_encode($auditData['new_values']) : null,
            isset($auditData['changed_fields']) ? json_encode($auditData['changed_fields']) : null,
            $auditData['user_id'] ?? null,
            $auditData['ip_address'] ?? null,
            $auditData['user_agent'] ?? null,
            $auditData['reason'] ?? null,
            $auditData['status_before'] ?? null,
            $auditData['status_after'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Record employee payment
     */
    public function recordEmployeePayment(int $voucherId, int $employeeId, array $paymentData): int
    {
        $sql = "INSERT INTO employee_payments (
            voucher_id, employee_id, salary_amount, advance_amount, bonus_amount,
            ot_payment, allowance_amount, deduction_amount, total_payment,
            employee_balance_before, employee_balance_after, payment_date, payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $voucherId,
            $employeeId,
            $paymentData['salary_amount'] ?? 0,
            $paymentData['advance_amount'] ?? 0,
            $paymentData['bonus_amount'] ?? 0,
            $paymentData['ot_payment'] ?? 0,
            $paymentData['allowance_amount'] ?? 0,
            $paymentData['deduction_amount'] ?? 0,
            $paymentData['total_payment'] ?? 0,
            $paymentData['balance_before'] ?? 0,
            $paymentData['balance_after'] ?? 0,
            $paymentData['payment_date'] ?? date('Y-m-d'),
            'PENDING'
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Get ledger account by code
     */
    public function getLedgerAccountByCode(string $accountCode): ?array
    {
        $sql = "SELECT * FROM ledger_accounts WHERE account_code = ? AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$accountCode]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all active ledger accounts
     */
    public function getLedgerAccounts(string $type = '', int $limit = 100): array
    {
        $where = ['is_active = 1'];
        $params = [];

        if ($type) {
            $where[] = 'account_type = ?';
            $params[] = $type;
        }

        $sql = "SELECT * FROM ledger_accounts WHERE " . implode(' AND ', $where) . 
               " ORDER BY account_code ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get audit logs for voucher
     */
    public function getAuditLogs(int $voucherId): array
    {
        $sql = "SELECT * FROM transaction_audit_logs WHERE voucher_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$voucherId]);
        return $stmt->fetchAll();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
}
