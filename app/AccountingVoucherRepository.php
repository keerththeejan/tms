<?php

declare(strict_types=1);

/**
 * Accounting Voucher Repository
 * Manages vouchers for the new accounting module (BUSY/Tally style)
 */
class AccountingVoucherRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        AccountingSchemaRepository::ensureSchema($pdo);
    }

    /** @return list<array<string,mixed>> */
    public static function listVouchers(PDO $pdo, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min(500, $limit));
        $voucherType = $filters['voucher_type'] ?? '';
        $fromDate = $filters['from_date'] ?? '';
        $toDate = $filters['to_date'] ?? '';
        $status = $filters['status'] ?? '';
        $query = $filters['q'] ?? '';

        $offset = ($page - 1) * $limit;
        $where = ['v.deleted_at IS NULL'];
        $params = [];

        if ($voucherType !== '') {
            $where[] = 'v.voucher_type = ?';
            $params[] = $voucherType;
        }

        if ($status !== '') {
            $where[] = 'v.status = ?';
            $params[] = $status;
        }

        if ($fromDate !== '') {
            $where[] = 'v.voucher_date >= ?';
            $params[] = $fromDate;
        }

        if ($toDate !== '') {
            $where[] = 'v.voucher_date <= ?';
            $params[] = $toDate;
        }

        if ($query !== '') {
            $where[] = '(v.voucher_number LIKE ? OR v.narration LIKE ?)';
            $params[] = "%{$query}%";
            $params[] = "%{$query}%";
        }

        $whereClause = implode(' AND ', $where);

        $st = $pdo->prepare(
            "SELECT v.*, 
                    GROUP_CONCAT(CONCAT(a.account_name, ' (', 
                        CASE WHEN vd.debit_amount > 0 THEN CONCAT('Dr: ', vd.debit_amount)
                             WHEN vd.credit_amount > 0 THEN CONCAT('Cr: ', vd.credit_amount)
                             ELSE ''
                        END, ')') SEPARATOR ', ') AS accounts_summary
             FROM vouchers v
             LEFT JOIN voucher_details vd ON vd.voucher_id = v.id
             LEFT JOIN accounts a ON a.id = vd.account_id
             WHERE {$whereClause}
             GROUP BY v.id
             ORDER BY v.voucher_date DESC, v.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $st = $pdo->prepare("SELECT COUNT(*) FROM vouchers v WHERE {$whereClause}");
        $st->execute($params);
        $total = (int) $st->fetchColumn();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ];
    }

    /** @return array<string,mixed>|null */
    public static function getById(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare(
            'SELECT v.*, 
                    s.series_name, s.prefix
             FROM vouchers v
             LEFT JOIN voucher_series s ON s.id = v.series_id
             WHERE v.id = ? AND v.deleted_at IS NULL
             LIMIT 1'
        );
        $st->execute([$id]);
        $result = $st->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /** @return array<string,mixed>|null */
    public static function getByNumber(PDO $pdo, string $voucherNumber): ?array
    {
        $st = $pdo->prepare(
            'SELECT v.*, 
                    s.series_name, s.prefix
             FROM vouchers v
             LEFT JOIN voucher_series s ON s.id = v.series_id
             WHERE v.voucher_number = ? AND v.deleted_at IS NULL
             LIMIT 1'
        );
        $st->execute([$voucherNumber]);
        $result = $st->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /** @return array<string,mixed> */
    public static function create(PDO $pdo, array $data): array
    {
        $ownTxn = !$pdo->inTransaction();
        if ($ownTxn) {
            $pdo->beginTransaction();
        }
        try {
            $voucherNumber = self::generateVoucherNumber($pdo, $data['voucher_type'], $data['fiscal_year']);

            $st = $pdo->prepare(
                'INSERT INTO vouchers (voucher_number, voucher_type, series_id, voucher_date, fiscal_year, 
                 reference_number, payment_mode, cheque_number, cheque_date, bank_account_id, narration, 
                 total_debit, total_credit, status, branch_id, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $st->execute([
                $voucherNumber,
                $data['voucher_type'],
                $data['series_id'] ?? null,
                $data['voucher_date'],
                $data['fiscal_year'],
                $data['reference_number'] ?? null,
                $data['payment_mode'] ?? 'CASH',
                $data['cheque_number'] ?? null,
                $data['cheque_date'] ?? null,
                $data['bank_account_id'] ?? null,
                $data['narration'] ?? null,
                (float) ($data['total_debit'] ?? 0),
                (float) ($data['total_credit'] ?? 0),
                'DRAFT',
                $data['branch_id'] ?? null,
                $data['created_by'] ?? null,
            ]);

            $voucherId = (int) $pdo->lastInsertId();
            if ($ownTxn) {
                $pdo->commit();
            }

            return self::getById($pdo, $voucherId) ?: [];
        } catch (Throwable $e) {
            if ($ownTxn && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed> */
    public static function update(PDO $pdo, int $id, array $data): array
    {
        $voucher = self::getById($pdo, $id);
        if (!$voucher) {
            throw new RuntimeException('Voucher not found.');
        }

        // Admin users may edit any non-cancelled, non-deleted voucher.
        // Non-admin users may only edit DRAFT vouchers.
        $status = (string) ($voucher['status'] ?? '');
        if ($status === 'CANCELLED') {
            throw new RuntimeException('Cancelled vouchers cannot be edited.');
        }
        if ($status !== 'DRAFT' && !Auth::isAdmin()) {
            throw new RuntimeException('Only draft vouchers can be edited.');
        }

        $fields = [];
        $params = [];
        
        foreach (['voucher_date', 'reference_number', 'payment_mode', 'cheque_number', 'cheque_date', 'bank_account_id', 'narration', 'total_debit', 'total_credit', 'branch_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return self::getById($pdo, $id) ?: [];
        }
        
        $params[] = $id;
        $sql = 'UPDATE vouchers SET ' . implode(', ', $fields) . ' WHERE id = ?';
        
        $st = $pdo->prepare($sql);
        $st->execute($params);
        
        return self::getById($pdo, $id) ?: [];
    }

    public static function delete(PDO $pdo, int $id, ?int $userId = null, string $reason = ''): bool
    {
        return AccountingVoucherDeleteService::deleteVoucher($pdo, $id, $userId, $reason);
    }

    public static function cancel(PDO $pdo, int $id, string $reason, ?int $userId = null): bool
    {
        $voucher = self::getById($pdo, $id);
        if (!$voucher) {
            throw new RuntimeException('Voucher not found.');
        }

        if (($voucher['status'] ?? '') !== 'POSTED') {
            throw new RuntimeException('Only posted vouchers can be cancelled.');
        }

        $ownTxn = !$pdo->inTransaction();
        if ($ownTxn) {
            $pdo->beginTransaction();
        }
        try {
            // Reverse ledger entries
            $st = $pdo->prepare('SELECT * FROM ledger_entries WHERE voucher_id = ?');
            $st->execute([$id]);
            $entries = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            foreach ($entries as $entry) {
                $reverseSt = $pdo->prepare(
                    'INSERT INTO ledger_entries (voucher_id, voucher_detail_id, account_id, entry_date, voucher_type, 
                     voucher_number, debit_amount, credit_amount, balance_type, narration, reference_id, reference_type, branch_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $reverseSt->execute([
                    $id,
                    $entry['voucher_detail_id'],
                    $entry['account_id'],
                    $entry['entry_date'],
                    $entry['voucher_type'],
                    $entry['voucher_number'] . '-CANCEL',
                    (float) ($entry['credit_amount'] ?? 0), // Reverse: credit becomes debit
                    (float) ($entry['debit_amount'] ?? 0),  // Reverse: debit becomes credit
                    $entry['balance_type'] === 'DEBIT' ? 'CREDIT' : 'DEBIT',
                    'Cancellation: ' . ($entry['narration'] ?? ''),
                    $entry['reference_id'],
                    $entry['reference_type'],
                    $entry['branch_id'],
                ]);
            }
            
            // Update voucher status
            $st = $pdo->prepare(
                'UPDATE vouchers SET status = ?, cancelled_at = CURRENT_TIMESTAMP, cancelled_by = ?, cancellation_reason = ? WHERE id = ?'
            );
            $st->execute(['CANCELLED', $userId, $reason, $id]);

            if ($ownTxn) {
                $pdo->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownTxn && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return string */
    private static function generateVoucherNumber(PDO $pdo, string $voucherType, string $fiscalYear): string
    {
        $st = $pdo->prepare(
            'SELECT id, prefix, current_number FROM voucher_series 
             WHERE voucher_type = ? AND is_active = 1 
             ORDER BY id ASC LIMIT 1 FOR UPDATE'
        );
        $st->execute([$voucherType]);
        $series = $st->fetch(PDO::FETCH_ASSOC);

        if (!$series) {
            throw new RuntimeException('No active voucher series found for type: ' . $voucherType);
        }

        $newNumber = (int) ($series['current_number'] ?? 0) + 1;
        $prefix = $series['prefix'];

        $updateSt = $pdo->prepare('UPDATE voucher_series SET current_number = ? WHERE id = ?');
        $updateSt->execute([$newNumber, $series['id']]);

        return $prefix . $fiscalYear . '-' . str_pad((string) $newNumber, 6, '0', STR_PAD_LEFT);
    }

    /** @return array<string,mixed> */
    public static function getSummary(PDO $pdo, string $fromDate, string $toDate): array
    {
        $st = $pdo->prepare(
            'SELECT 
                COUNT(*) AS total_vouchers,
                SUM(CASE WHEN status = ? THEN total_debit ELSE 0 END) AS total_debit,
                SUM(CASE WHEN status = ? THEN total_credit ELSE 0 END) AS total_credit,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS draft_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS posted_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cancelled_count
             FROM vouchers
             WHERE voucher_date BETWEEN ? AND ? AND deleted_at IS NULL'
        );
        $st->execute(['POSTED', 'POSTED', 'DRAFT', 'POSTED', 'CANCELLED', $fromDate, $toDate]);
        $result = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        
        return [
            'total_vouchers' => (int) ($result['total_vouchers'] ?? 0),
            'total_debit' => (float) ($result['total_debit'] ?? 0),
            'total_credit' => (float) ($result['total_credit'] ?? 0),
            'draft_count' => (int) ($result['draft_count'] ?? 0),
            'posted_count' => (int) ($result['posted_count'] ?? 0),
            'cancelled_count' => (int) ($result['cancelled_count'] ?? 0),
        ];
    }
}
