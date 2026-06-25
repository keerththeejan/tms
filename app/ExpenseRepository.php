<?php

declare(strict_types=1);

class ExpenseRepository
{
    private const PAYMENT_METHODS = ['cash', 'bank', 'cheque', 'credit', 'transfer'];
    private const STATUSES = ['draft', 'pending', 'approved', 'rejected', 'cancelled'];

    /** @return list<string> */
    public static function paymentMethods(): array
    {
        return self::PAYMENT_METHODS;
    }

    /** @return list<string> */
    public static function statuses(): array
    {
        return self::STATUSES;
    }

    /** @param array<string,mixed> $filters */
    public static function list(PDO $pdo, array $filters = [], int $page = 1, int $limit = 25): array
    {
        $page = max(1, $page);
        $limit = max(1, min(200, $limit));
        $offset = ($page - 1) * $limit;

        [$where, $params] = self::buildWhere($filters);
        $whereSql = implode(' AND ', $where);

        $sql = "SELECT e.*,
                       b.name AS branch_name,
                       c.name AS category_name,
                       c.code AS category_code,
                       s.name AS supplier_name,
                       ua.full_name AS approver_name,
                       uc.full_name AS creator_name,
                       COALESCE(pay.paid_total, 0) AS payments_total,
                       CASE
                         WHEN e.payment_method = 'credit' OR e.payment_mode = 'credit' THEN GREATEST(0, COALESCE(e.total_amount, e.amount) - COALESCE(pay.paid_total, 0))
                         ELSE 0
                       END AS balance_calc,
                       CASE
                         WHEN e.payment_method IN ('cash','bank','cheque','transfer') OR e.payment_mode = 'cash' THEN COALESCE(e.paid_amount, COALESCE(e.total_amount, e.amount))
                         ELSE COALESCE(pay.paid_total, 0)
                       END AS paid_calc
                FROM expenses e
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN expense_categories c ON c.id = e.category_id
                LEFT JOIN suppliers s ON s.id = e.supplier_id
                LEFT JOIN users ua ON ua.id = e.approved_by
                LEFT JOIN users uc ON uc.id = e.created_by
                LEFT JOIN (
                    SELECT expense_id, SUM(amount) AS paid_total
                    FROM expense_payments
                    GROUP BY expense_id
                ) pay ON pay.expense_id = e.id
                WHERE {$whereSql}
                ORDER BY e.expense_date DESC, e.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row = self::normalizeRow($row);
        }
        unset($row);

        $countSt = $pdo->prepare("SELECT COUNT(*) FROM expenses e WHERE {$whereSql}");
        $countSt->execute($params);
        $total = (int) $countSt->fetchColumn();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / max(1, $limit)),
        ];
    }

    /** @param array<string,mixed> $filters */
    public static function stats(PDO $pdo, array $filters = []): array
    {
        [$where, $params] = self::buildWhere($filters);
        $whereSql = implode(' AND ', $where);
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $yearStart = date('Y-01-01');
        $weekStart = date('Y-m-d', strtotime('-6 days'));

        $base = "FROM expenses e WHERE {$whereSql}";

        $sum = static function (string $extra = '', array $extraParams = []) use ($pdo, $base, $params): float {
            $sql = "SELECT COALESCE(SUM(COALESCE(e.total_amount, e.amount)), 0) {$base}" . ($extra ? " AND {$extra}" : '');
            $st = $pdo->prepare($sql);
            $st->execute(array_merge($params, $extraParams));

            return (float) $st->fetchColumn();
        };

        $count = static function (string $extra = '', array $extraParams = []) use ($pdo, $base, $params): int {
            $sql = "SELECT COUNT(*) {$base}" . ($extra ? " AND {$extra}" : '');
            $st = $pdo->prepare($sql);
            $st->execute(array_merge($params, $extraParams));

            return (int) $st->fetchColumn();
        };

        $outstandingSql = "SELECT COALESCE(SUM(
            CASE WHEN e.payment_method = 'credit' OR e.payment_mode = 'credit'
              THEN GREATEST(0, COALESCE(e.total_amount, e.amount) - COALESCE(pay.paid_total, 0))
              ELSE 0 END
          ), 0)
          FROM expenses e
          LEFT JOIN (SELECT expense_id, SUM(amount) AS paid_total FROM expense_payments GROUP BY expense_id) pay
            ON pay.expense_id = e.id
          WHERE {$whereSql}";
        $outSt = $pdo->prepare($outstandingSql);
        $outSt->execute($params);

        $catSql = "SELECT COALESCE(c.name, e.expense_type, 'Other') AS label,
                          SUM(COALESCE(e.total_amount, e.amount)) AS total
                   FROM expenses e
                   LEFT JOIN expense_categories c ON c.id = e.category_id
                   WHERE {$whereSql}
                   GROUP BY label
                   ORDER BY total DESC
                   LIMIT 8";
        $catSt = $pdo->prepare($catSql);
        $catSt->execute($params);
        $topCategories = $catSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $branchSql = "SELECT b.name AS label, SUM(COALESCE(e.total_amount, e.amount)) AS total
                      FROM expenses e
                      LEFT JOIN branches b ON b.id = e.branch_id
                      WHERE {$whereSql}
                      GROUP BY e.branch_id, b.name
                      ORDER BY total DESC
                      LIMIT 8";
        $branchSt = $pdo->prepare($branchSql);
        $branchSt->execute($params);
        $byBranch = $branchSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Monthly trend (last 6 months within filter range or default)
        $trendSql = "SELECT DATE_FORMAT(e.expense_date, '%Y-%m') AS month,
                            SUM(COALESCE(e.total_amount, e.amount)) AS total
                     FROM expenses e
                     WHERE {$whereSql}
                     GROUP BY month
                     ORDER BY month ASC
                     LIMIT 12";
        $trendSt = $pdo->prepare($trendSql);
        $trendSt->execute($params);
        $monthlyTrend = $trendSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_expenses' => $sum(),
            'cash_expenses' => $sum("(e.payment_method IN ('cash','bank','cheque','transfer') OR (e.payment_method IS NULL AND COALESCE(e.payment_mode,'cash') <> 'credit'))"),
            'credit_expenses' => $sum("(e.payment_method = 'credit' OR e.payment_mode = 'credit')"),
            'pending_payments' => $count("(e.payment_method = 'credit' OR e.payment_mode = 'credit') AND e.status = 'approved' AND COALESCE(e.credit_settled,0) = 0"),
            'approved_expenses' => $sum("e.status = 'approved'"),
            'pending_approvals' => $count("e.status IN ('pending','draft')"),
            'this_month' => $sum('e.expense_date >= ? AND e.expense_date <= ?', [$monthStart, $today]),
            'today' => $sum('e.expense_date = ?', [$today]),
            'weekly' => $sum('e.expense_date >= ? AND e.expense_date <= ?', [$weekStart, $today]),
            'yearly' => $sum('e.expense_date >= ? AND e.expense_date <= ?', [$yearStart, $today]),
            'outstanding_balance' => (float) $outSt->fetchColumn(),
            'top_categories' => $topCategories,
            'by_branch' => $byBranch,
            'monthly_trend' => $monthlyTrend,
        ];
    }

    /** @return array<string,mixed>|null */
    public static function getById(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare(
            'SELECT e.*, b.name AS branch_name, c.name AS category_name, c.code AS category_code,
                    s.name AS supplier_name
             FROM expenses e
             LEFT JOIN branches b ON b.id = e.branch_id
             LEFT JOIN expense_categories c ON c.id = e.category_id
             LEFT JOIN suppliers s ON s.id = e.supplier_id
             WHERE e.id = ?
             LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ? self::normalizeRow($row) : null;
    }

    /** @param array<string,mixed> $data */
    public static function validate(array $data, bool $isUpdate = false): array
    {
        $errors = [];
        $amount = (float) ($data['amount'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);
        $discount = (float) ($data['discount_amount'] ?? 0);
        $total = (float) ($data['total_amount'] ?? ($amount + $tax - $discount));
        $branchId = (int) ($data['branch_id'] ?? 0);
        $date = trim((string) ($data['expense_date'] ?? ''));
        $method = strtolower(trim((string) ($data['payment_method'] ?? $data['payment_mode'] ?? 'cash')));

        if ($branchId <= 0) {
            $errors['branch_id'] = 'Branch is required.';
        }
        if ($date === '') {
            $errors['expense_date'] = 'Expense date is required.';
        }
        if ($total <= 0 && $amount <= 0) {
            $errors['amount'] = 'Amount must be greater than zero.';
        }
        if (!in_array($method, self::PAYMENT_METHODS, true)) {
            $errors['payment_method'] = 'Invalid payment method.';
        }
        if ($method === 'credit') {
            if (trim((string) ($data['credit_party'] ?? '')) === '' && empty($data['supplier_id'])) {
                $errors['supplier_id'] = 'Supplier or credit party is required for credit expenses.';
            }
            if (empty($data['credit_due_date'])) {
                $errors['credit_due_date'] = 'Due date is required for credit expenses.';
            }
        }
        $status = strtolower(trim((string) ($data['status'] ?? 'pending')));
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Invalid status.';
        }

        return $errors;
    }

    /** @param array<string,mixed> $data */
    public static function save(PDO $pdo, array $data, int $userId): array
    {
        $errors = self::validate($data, !empty($data['id']));
        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $id = (int) ($data['id'] ?? 0);
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $tax = round((float) ($data['tax_amount'] ?? 0), 2);
        $discount = round((float) ($data['discount_amount'] ?? 0), 2);
        $total = round((float) ($data['total_amount'] ?? ($amount + $tax - $discount)), 2);
        if ($total <= 0) {
            $total = $amount;
        }

        $method = strtolower(trim((string) ($data['payment_method'] ?? $data['payment_mode'] ?? 'cash')));
        $paidAmount = round((float) ($data['paid_amount'] ?? 0), 2);
        if ($method !== 'credit' && $paidAmount <= 0) {
            $paidAmount = $total;
        }
        $balance = $method === 'credit' ? max(0, $total - $paidAmount) : 0.0;

        $categoryId = !empty($data['category_id']) ? (int) $data['category_id'] : null;
        $expenseType = trim((string) ($data['expense_type'] ?? ''));
        if ($categoryId) {
            $cat = ExpenseCategoryRepository::getById($pdo, $categoryId);
            if ($cat) {
                $expenseType = (string) $cat['code'];
            }
        } elseif ($expenseType === '') {
            $expenseType = 'miscellaneous';
            $cat = ExpenseCategoryRepository::getByCode($pdo, 'miscellaneous');
            $categoryId = $cat ? (int) $cat['id'] : null;
        }

        $status = strtolower(trim((string) ($data['status'] ?? 'pending')));
        $notes = trim((string) ($data['notes'] ?? ''));
        $description = trim((string) ($data['description'] ?? $notes));
        $reference = trim((string) ($data['reference_number'] ?? ''));
        $creditParty = trim((string) ($data['credit_party'] ?? ''));
        $creditDue = $data['credit_due_date'] ?? null;
        $supplierId = !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;

        if ($supplierId && $creditParty === '') {
            $st = $pdo->prepare('SELECT name FROM suppliers WHERE id = ?');
            $st->execute([$supplierId]);
            $creditParty = (string) ($st->fetchColumn() ?: '');
        }

        $paymentMode = $method === 'credit' ? 'credit' : 'cash';
        $creditSettled = ($method === 'credit' && $balance <= 0.0001) ? 1 : (int) ($data['credit_settled'] ?? 0);
        if ($method !== 'credit') {
            $creditSettled = 1;
        }

        $ownTxn = !$pdo->inTransaction();
        if ($ownTxn) {
            $pdo->beginTransaction();
        }

        try {
            if ($id > 0) {
                $existing = self::getById($pdo, $id);
                if (!$existing) {
                    throw new RuntimeException('Expense not found.');
                }
                if (($existing['status'] ?? '') === 'approved' && !empty($existing['voucher_id'])) {
                    throw new RuntimeException('Approved posted expenses cannot be edited. Cancel and re-create.');
                }

                $st = $pdo->prepare(
                    'UPDATE expenses SET
                       expense_type = ?, category_id = ?, supplier_id = ?, account_id = ?,
                       reference_number = ?, description = ?, amount = ?, tax_amount = ?, discount_amount = ?,
                       total_amount = ?, branch_id = ?, expense_date = ?, notes = ?,
                       payment_method = ?, payment_mode = ?, payment_account_id = ?,
                       paid_amount = ?, balance_amount = ?,
                       credit_party = ?, credit_due_date = ?, credit_settled = ?,
                       status = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?'
                );
                $st->execute([
                    $expenseType,
                    $categoryId,
                    $supplierId,
                    !empty($data['account_id']) ? (int) $data['account_id'] : null,
                    $reference ?: null,
                    $description ?: null,
                    $amount,
                    $tax,
                    $discount,
                    $total,
                    (int) $data['branch_id'],
                    $data['expense_date'],
                    $notes ?: null,
                    $method,
                    $paymentMode,
                    !empty($data['payment_account_id']) ? (int) $data['payment_account_id'] : null,
                    $paidAmount,
                    $balance,
                    $creditParty ?: null,
                    $creditDue ?: null,
                    $creditSettled,
                    $status,
                    $id,
                ]);
            } else {
                $expenseNumber = self::generateExpenseNumber($pdo);
                $st = $pdo->prepare(
                    'INSERT INTO expenses (
                       expense_number, expense_type, category_id, supplier_id, account_id,
                       reference_number, description, amount, tax_amount, discount_amount, total_amount,
                       branch_id, expense_date, notes, payment_method, payment_mode, payment_account_id,
                       paid_amount, balance_amount, credit_party, credit_due_date, credit_settled,
                       status, created_by
                     ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                );
                $st->execute([
                    $expenseNumber,
                    $expenseType,
                    $categoryId,
                    $supplierId,
                    !empty($data['account_id']) ? (int) $data['account_id'] : null,
                    $reference ?: null,
                    $description ?: null,
                    $amount,
                    $tax,
                    $discount,
                    $total,
                    (int) $data['branch_id'],
                    $data['expense_date'],
                    $notes ?: null,
                    $method,
                    $paymentMode,
                    !empty($data['payment_account_id']) ? (int) $data['payment_account_id'] : null,
                    $paidAmount,
                    $balance,
                    $creditParty ?: null,
                    $creditDue ?: null,
                    $creditSettled,
                    $status,
                    $userId,
                ]);
                $id = (int) $pdo->lastInsertId();
            }

            if ($ownTxn) {
                $pdo->commit();
            }

            $saved = self::getById($pdo, $id) ?: [];
            if ($status === 'approved' && empty($saved['voucher_id'])) {
                return self::approve($pdo, $id, $userId);
            }

            return $saved;
        } catch (Throwable $e) {
            if ($ownTxn && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function delete(PDO $pdo, int $id): bool
    {
        $row = self::getById($pdo, $id);
        if (!$row) {
            return false;
        }
        if (!empty($row['voucher_id']) && ($row['status'] ?? '') === 'approved') {
            throw new RuntimeException('Cannot delete an approved posted expense. Cancel it first.');
        }

        $pdo->prepare('DELETE FROM expense_payments WHERE expense_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM expenses WHERE id = ?')->execute([$id]);

        return true;
    }

    public static function approve(PDO $pdo, int $id, int $userId): array
    {
        $row = self::getById($pdo, $id);
        if (!$row) {
            throw new RuntimeException('Expense not found.');
        }
        if (($row['status'] ?? '') === 'approved') {
            return $row;
        }
        if (in_array($row['status'] ?? '', ['rejected', 'cancelled'], true)) {
            throw new RuntimeException('Cannot approve a rejected or cancelled expense.');
        }

        $pdo->prepare(
            "UPDATE expenses SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?"
        )->execute([$userId, $id]);

        $updated = self::getById($pdo, $id) ?: [];
        if (empty($updated['voucher_id'])) {
            ExpenseAccountingService::postExpense($pdo, $id, $userId);
            $updated = self::getById($pdo, $id) ?: $updated;
        }

        return $updated;
    }

    public static function reject(PDO $pdo, int $id, int $userId): array
    {
        $row = self::getById($pdo, $id);
        if (!$row) {
            throw new RuntimeException('Expense not found.');
        }
        if (($row['status'] ?? '') === 'approved' && !empty($row['voucher_id'])) {
            throw new RuntimeException('Cannot reject a posted expense.');
        }

        $pdo->prepare(
            "UPDATE expenses SET status = 'rejected', rejected_by = ?, rejected_at = NOW() WHERE id = ?"
        )->execute([$userId, $id]);

        return self::getById($pdo, $id) ?: [];
    }

    public static function cancel(PDO $pdo, int $id, int $userId): array
    {
        $row = self::getById($pdo, $id);
        if (!$row) {
            throw new RuntimeException('Expense not found.');
        }

        if (!empty($row['voucher_id'])) {
            ExpenseAccountingService::cancelExpenseVoucher($pdo, $id, $userId, 'Expense cancelled');
        }

        $pdo->prepare("UPDATE expenses SET status = 'cancelled' WHERE id = ?")->execute([$id]);

        return self::getById($pdo, $id) ?: [];
    }

    /** @return array<string,mixed> */
    public static function settle(PDO $pdo, int $expenseId, float $amount, int $userId, string $notes = ''): array
    {
        $row = self::getById($pdo, $expenseId);
        if (!$row) {
            throw new RuntimeException('Expense not found.');
        }
        $method = $row['payment_method'] ?? $row['payment_mode'] ?? 'cash';
        if ($method !== 'credit' && ($row['payment_mode'] ?? '') !== 'credit') {
            throw new RuntimeException('Only credit expenses can be settled.');
        }

        $total = (float) ($row['total_amount'] ?? $row['amount'] ?? 0);
        $st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM expense_payments WHERE expense_id = ?');
        $st->execute([$expenseId]);
        $paidSoFar = (float) $st->fetchColumn();
        $balance = max(0, $total - $paidSoFar);
        $apply = $amount <= 0 ? $balance : min($amount, $balance);
        if ($apply <= 0) {
            throw new RuntimeException('Nothing to settle.');
        }

        $pdo->prepare(
            'INSERT INTO expense_payments (expense_id, amount, paid_at, paid_by, notes) VALUES (?,?,?,?,?)'
        )->execute([$expenseId, $apply, date('Y-m-d H:i:s'), $userId, $notes ?: null]);

        $newPaid = $paidSoFar + $apply;
        $settled = ($newPaid + 0.0001) >= $total ? 1 : 0;
        $pdo->prepare(
            'UPDATE expenses SET paid_amount = ?, balance_amount = ?, credit_settled = ? WHERE id = ?'
        )->execute([$newPaid, max(0, $total - $newPaid), $settled, $expenseId]);

        if ($settled && ($row['status'] ?? '') === 'approved') {
            ExpenseAccountingService::postSettlement($pdo, $expenseId, $apply, $userId);
        }

        return self::getById($pdo, $expenseId) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function listSuppliers(PDO $pdo, ?int $branchId = null): array
    {
        $sql = 'SELECT s.id, s.name, s.phone, s.branch_id, s.supplier_code FROM suppliers s WHERE 1=1';
        $params = [];
        if ($branchId) {
            $sql .= ' AND (s.branch_id IS NULL OR s.branch_id = ?)';
            $params[] = $branchId;
        }
        $sql .= ' ORDER BY s.name ASC';
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function expenseAccounts(PDO $pdo): array
    {
        $st = $pdo->query(
            "SELECT a.id, a.account_code, a.account_name
             FROM accounts a
             INNER JOIN account_groups g ON g.id = a.account_group_id
             WHERE a.deleted_at IS NULL AND a.is_active = 1
               AND g.group_type IN ('EXPENSES', 'EXPENSE')
             ORDER BY a.account_name ASC"
        );

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function generateExpenseNumber(PDO $pdo): string
    {
        $prefix = 'EXP-' . date('Ym') . '-';
        $st = $pdo->prepare(
            "SELECT expense_number FROM expenses
             WHERE expense_number LIKE ?
             ORDER BY id DESC LIMIT 1 FOR UPDATE"
        );
        $st->execute([$prefix . '%']);
        $last = (string) ($st->fetchColumn() ?: '');
        $seq = 1;
        if ($last !== '' && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    /** @param array<string,mixed> $filters @return array{0:list<string>,1:list<mixed>} */
    private static function buildWhere(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));
        if ($from !== '' && $to !== '') {
            $where[] = 'e.expense_date BETWEEN ? AND ?';
            $params[] = $from;
            $params[] = $to;
        } elseif ($from !== '') {
            $where[] = 'e.expense_date >= ?';
            $params[] = $from;
        } elseif ($to !== '') {
            $where[] = 'e.expense_date <= ?';
            $params[] = $to;
        }

        if (!empty($filters['branch_id'])) {
            $where[] = 'e.branch_id = ?';
            $params[] = (int) $filters['branch_id'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'e.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['supplier_id'])) {
            $where[] = 'e.supplier_id = ?';
            $params[] = (int) $filters['supplier_id'];
        }
        if (!empty($filters['payment_method'])) {
            $where[] = '(e.payment_method = ? OR e.payment_mode = ?)';
            $params[] = $filters['payment_method'];
            $params[] = $filters['payment_method'] === 'credit' ? 'credit' : 'cash';
        }
        if (!empty($filters['status'])) {
            if (($filters['status'] ?? '') === 'approved_legacy') {
                $where[] = "(e.status = 'approved' OR e.approved_by IS NOT NULL)";
            } else {
                $where[] = 'e.status = ?';
                $params[] = $filters['status'];
            }
        }
        if (($filters['approval'] ?? '') === 'yes') {
            $where[] = "(e.status = 'approved' OR e.approved_by IS NOT NULL)";
        } elseif (($filters['approval'] ?? '') === 'no') {
            $where[] = "(e.status NOT IN ('approved') AND e.approved_by IS NULL)";
        }
        if (($filters['credit_status'] ?? '') === 'open') {
            $where[] = "(e.payment_method = 'credit' OR e.payment_mode = 'credit') AND COALESCE(e.credit_settled,0) = 0";
        } elseif (($filters['credit_status'] ?? '') === 'settled') {
            $where[] = "(e.payment_method = 'credit' OR e.payment_mode = 'credit') AND e.credit_settled = 1";
        } elseif (($filters['credit_status'] ?? '') === 'overdue') {
            $where[] = "(e.payment_method = 'credit' OR e.payment_mode = 'credit') AND COALESCE(e.credit_settled,0) = 0 AND e.credit_due_date IS NOT NULL AND e.credit_due_date < CURDATE()";
        }
        if (!empty($filters['q'])) {
            $q = '%' . $filters['q'] . '%';
            $where[] = '(e.expense_number LIKE ? OR e.reference_number LIKE ? OR e.notes LIKE ? OR e.description LIKE ? OR e.credit_party LIKE ?)';
            array_push($params, $q, $q, $q, $q, $q);
        }
        if (!empty($filters['amount_min'])) {
            $where[] = 'COALESCE(e.total_amount, e.amount) >= ?';
            $params[] = (float) $filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
            $where[] = 'COALESCE(e.total_amount, e.amount) <= ?';
            $params[] = (float) $filters['amount_max'];
        }

        return [$where, $params];
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private static function normalizeRow(array $row): array
    {
        $total = (float) ($row['total_amount'] ?? $row['amount'] ?? 0);
        $paid = isset($row['paid_calc']) ? (float) $row['paid_calc'] : (float) ($row['paid_amount'] ?? 0);
        $balance = isset($row['balance_calc']) ? (float) $row['balance_calc'] : (float) ($row['balance_amount'] ?? 0);
        $method = $row['payment_method'] ?? $row['payment_mode'] ?? 'cash';

        $row['total_amount'] = $total;
        $row['paid_amount'] = $paid;
        $row['balance_amount'] = $balance;
        $row['payment_method'] = $method;
        $row['status'] = $row['status'] ?? ($row['approved_by'] ? 'approved' : 'pending');
        $row['expense_number'] = $row['expense_number'] ?? ('EXP-' . $row['id']);

        if (empty($row['category_name']) && !empty($row['expense_type'])) {
            $row['category_name'] = ucwords(str_replace('_', ' ', (string) $row['expense_type']));
        }

        return $row;
    }
}
