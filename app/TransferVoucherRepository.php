<?php

declare(strict_types=1);

final class TransferVoucherRepository
{
    private static bool $schemaChecked = false;

    public static function ensureSchema(PDO $pdo): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            $pdo->exec(
                <<<'SQL'
CREATE TABLE IF NOT EXISTS transfer_vouchers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  voucher_no VARCHAR(50) NOT NULL,
  sequence_no INT UNSIGNED NOT NULL,
  fiscal_year VARCHAR(10) NOT NULL,
  voucher_date DATE NOT NULL,
  from_account_id INT UNSIGNED NOT NULL,
  to_account_id INT UNSIGNED NOT NULL,
  amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  reference_number VARCHAR(100) NULL DEFAULT NULL,
  narration TEXT NULL,
  status ENUM('DRAFT','POSTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  cashbook_transfer_id INT UNSIGNED NULL DEFAULT NULL,
  created_by INT UNSIGNED NULL DEFAULT NULL,
  posted_by INT UNSIGNED NULL DEFAULT NULL,
  cancelled_by INT UNSIGNED NULL DEFAULT NULL,
  posted_at DATETIME NULL DEFAULT NULL,
  cancelled_at DATETIME NULL DEFAULT NULL,
  cancel_reason TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_transfer_voucher_no (voucher_no),
  UNIQUE KEY uq_transfer_voucher_seq (fiscal_year, sequence_no),
  KEY idx_transfer_voucher_status (status),
  KEY idx_transfer_voucher_date (voucher_date),
  KEY idx_transfer_voucher_created_by (created_by),
  KEY idx_transfer_voucher_posted_at (posted_at),
  KEY idx_transfer_voucher_cashbook_transfer (cashbook_transfer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            );
        } catch (Throwable $e) {
            /* non-fatal */
        }
    }

    public static function fiscalYearFromDate(string $date): string
    {
        $ts = strtotime($date) ?: time();
        return date('Y', $ts);
    }

    public static function formatVoucherNo(string $fiscalYear, int $sequenceNo): string
    {
        return sprintf('TRF-%s-%06d', $fiscalYear, $sequenceNo);
    }

    public static function nextSequenceNo(PDO $pdo, string $fiscalYear): int
    {
        $st = $pdo->prepare('SELECT COALESCE(MAX(sequence_no), 0) + 1 AS next_seq FROM transfer_vouchers WHERE fiscal_year = ?');
        $st->execute([$fiscalYear]);
        return max(1, (int) ($st->fetchColumn() ?: 1));
    }

    /** @return array<string,mixed> */
    private static function normalizePayload(array $payload): array
    {
        $voucherDate = trim((string) ($payload['voucher_date'] ?? ''));
        if ($voucherDate === '') {
            $voucherDate = date('Y-m-d');
        }

        $fromAccountId = (int) ($payload['from_account_id'] ?? 0);
        $toAccountId = (int) ($payload['to_account_id'] ?? 0);
        $amount = (float) str_replace(',', '', (string) ($payload['amount'] ?? '0'));
        $referenceNumber = trim((string) ($payload['reference_number'] ?? ''));
        $narration = trim((string) ($payload['narration'] ?? ''));

        return [
            'voucher_date' => $voucherDate,
            'fiscal_year' => self::fiscalYearFromDate($voucherDate),
            'from_account_id' => $fromAccountId,
            'to_account_id' => $toAccountId,
            'amount' => $amount,
            'reference_number' => $referenceNumber !== '' ? $referenceNumber : null,
            'narration' => $narration !== '' ? $narration : null,
        ];
    }

    /** @return array<string,mixed> */
    public static function upsertDraft(PDO $pdo, array $payload, ?int $userId = null): array
    {
        self::ensureSchema($pdo);
        $data = self::normalizePayload($payload);
        if ($data['from_account_id'] <= 0 || $data['to_account_id'] <= 0 || $data['from_account_id'] === $data['to_account_id'] || $data['amount'] <= 0) {
            throw new InvalidArgumentException('Source account, destination account, and amount are required.');
        }

        $existingId = (int) ($payload['id'] ?? 0);
        $voucher = null;
        if ($existingId > 0) {
            $voucher = self::getVoucher($pdo, $existingId);
            if (!$voucher) {
                throw new RuntimeException('Transfer voucher not found.');
            }
            if (($voucher['status'] ?? '') !== 'DRAFT') {
                throw new RuntimeException('Only draft vouchers can be edited.');
            }
            $data['fiscal_year'] = (string) ($voucher['fiscal_year'] ?? $data['fiscal_year']);
        }

        $attempts = 0;
        while ($attempts < 3) {
            $attempts++;
            try {
                $pdo->beginTransaction();
                if ($voucher === null) {
                    $sequenceNo = self::nextSequenceNo($pdo, $data['fiscal_year']);
                    $voucherNo = self::formatVoucherNo($data['fiscal_year'], $sequenceNo);
                    $st = $pdo->prepare(
                        'INSERT INTO transfer_vouchers (voucher_no, sequence_no, fiscal_year, voucher_date, from_account_id, to_account_id, amount, reference_number, narration, status, created_by)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?)'
                    );
                    $st->execute([
                        $voucherNo,
                        $sequenceNo,
                        $data['fiscal_year'],
                        $data['voucher_date'],
                        $data['from_account_id'],
                        $data['to_account_id'],
                        $data['amount'],
                        $data['reference_number'],
                        $data['narration'],
                        'DRAFT',
                        $userId,
                    ]);
                    $voucherId = (int) $pdo->lastInsertId();
                } else {
                    $voucherId = (int) $voucher['id'];
                    $sequenceNo = (int) $voucher['sequence_no'];
                    $voucherNo = (string) $voucher['voucher_no'];
                    $st = $pdo->prepare(
                        'UPDATE transfer_vouchers
                         SET voucher_date = ?, fiscal_year = ?, from_account_id = ?, to_account_id = ?, amount = ?, reference_number = ?, narration = ?, updated_at = CURRENT_TIMESTAMP
                         WHERE id = ?'
                    );
                    $st->execute([
                        $data['voucher_date'],
                        $data['fiscal_year'],
                        $data['from_account_id'],
                        $data['to_account_id'],
                        $data['amount'],
                        $data['reference_number'],
                        $data['narration'],
                        $voucherId,
                    ]);
                }
                $pdo->commit();
                return self::getVoucher($pdo, $voucherId) ?? [
                    'id' => $voucherId,
                    'voucher_no' => $voucherNo,
                    'sequence_no' => $sequenceNo,
                    'fiscal_year' => $data['fiscal_year'],
                ];
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if (str_contains($e->getMessage(), 'uq_transfer_voucher_seq') || str_contains($e->getMessage(), 'Duplicate entry')) {
                    continue;
                }
                throw $e;
            }
        }

        throw new RuntimeException('Unable to allocate a transfer voucher number.');
    }

    /** @return array<string,mixed> */
    public static function postVoucher(PDO $pdo, array $payload, ?int $userId = null): array
    {
        self::ensureSchema($pdo);
        $voucher = self::upsertDraft($pdo, $payload, $userId);
        $voucherId = (int) ($voucher['id'] ?? 0);
        if ($voucherId <= 0) {
            throw new RuntimeException('Unable to save transfer voucher.');
        }

        $voucher = self::getVoucher($pdo, $voucherId);
        if (!$voucher) {
            throw new RuntimeException('Transfer voucher not found.');
        }
        if (($voucher['status'] ?? '') !== 'DRAFT') {
            throw new RuntimeException('Only draft vouchers can be posted.');
        }

        $pdo->beginTransaction();
        try {
            $transferId = CashbookRepository::addTransfer(
                $pdo,
                (int) $voucher['from_account_id'],
                (int) $voucher['to_account_id'],
                (float) $voucher['amount'],
                (string) ($voucher['voucher_date'] . ' 12:00:00'),
                trim((string) ($voucher['narration'] ?? '')) !== '' ? (string) $voucher['narration'] : (string) ($voucher['voucher_no'] ?? null),
                true,
                $userId
            );

            $st = $pdo->prepare(
                'UPDATE transfer_vouchers
                 SET status = ?, cashbook_transfer_id = ?, posted_by = ?, posted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?'
            );
            $st->execute(['POSTED', $transferId, $userId, $voucherId]);

            CashbookRepository::audit($pdo, 'transfer_voucher', (string) $voucherId, 'post', $userId, [
                'voucher_no' => $voucher['voucher_no'],
                'cashbook_transfer_id' => $transferId,
                'amount' => (float) $voucher['amount'],
                'from_account_id' => (int) $voucher['from_account_id'],
                'to_account_id' => (int) $voucher['to_account_id'],
            ]);

            $pdo->commit();
            return self::getVoucher($pdo, $voucherId) ?? $voucher;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed> */
    public static function cancelVoucher(PDO $pdo, int $id, ?int $userId = null, ?string $reason = null): array
    {
        self::ensureSchema($pdo);
        $voucher = self::getVoucher($pdo, $id);
        if (!$voucher) {
            throw new RuntimeException('Transfer voucher not found.');
        }
        if (($voucher['status'] ?? '') !== 'DRAFT') {
            throw new RuntimeException('Only draft transfer vouchers can be cancelled.');
        }

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'UPDATE transfer_vouchers
                 SET status = ?, cancelled_by = ?, cancelled_at = CURRENT_TIMESTAMP, cancel_reason = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?'
            );
            $st->execute(['CANCELLED', $userId, $reason, $id]);
            CashbookRepository::audit($pdo, 'transfer_voucher', (string) $id, 'cancel', $userId, [
                'voucher_no' => $voucher['voucher_no'],
                'reason' => $reason,
            ]);
            $pdo->commit();
            return self::getVoucher($pdo, $id) ?? $voucher;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed>|null */
    public static function getVoucher(PDO $pdo, int $id): ?array
    {
        self::ensureSchema($pdo);
        $st = $pdo->prepare(
            'SELECT tv.*, fa.name AS from_account_name, fa.type AS from_account_type, ta.name AS to_account_name, ta.type AS to_account_type,
                    cu.full_name AS created_by_name, pu.full_name AS posted_by_name, xu.full_name AS cancelled_by_name
             FROM transfer_vouchers tv
             LEFT JOIN cashbook_accounts fa ON fa.id = tv.from_account_id
             LEFT JOIN cashbook_accounts ta ON ta.id = tv.to_account_id
             LEFT JOIN users cu ON cu.id = tv.created_by
             LEFT JOIN users pu ON pu.id = tv.posted_by
             LEFT JOIN users xu ON xu.id = tv.cancelled_by
             WHERE tv.id = ?
             LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return array{rows:list<array<string,mixed>>,total:int,page:int,limit:int} */
    public static function listVouchers(PDO $pdo, array $filters = [], int $page = 1, int $limit = 20): array
    {
        self::ensureSchema($pdo);
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $where = ['1=1'];
        $params = [];

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'tv.status = ?';
            $params[] = $status;
        }
        $from = trim((string) ($filters['from_date'] ?? ''));
        $to = trim((string) ($filters['to_date'] ?? ''));
        if ($from !== '') {
            $where[] = 'tv.voucher_date >= ?';
            $params[] = $from;
        }
        if ($to !== '') {
            $where[] = 'tv.voucher_date <= ?';
            $params[] = $to;
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(tv.voucher_no LIKE ? OR fa.name LIKE ? OR ta.name LIKE ? OR tv.reference_number LIKE ? OR tv.narration LIKE ?)';
            $term = '%' . $q . '%';
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        $whereSql = implode(' AND ', $where);
        $countSt = $pdo->prepare('SELECT COUNT(*) FROM transfer_vouchers tv LEFT JOIN cashbook_accounts fa ON fa.id = tv.from_account_id LEFT JOIN cashbook_accounts ta ON ta.id = tv.to_account_id WHERE ' . $whereSql);
        $countSt->execute($params);
        $total = (int) $countSt->fetchColumn();

        $offset = ($page - 1) * $limit;
        $sql = 'SELECT tv.*, fa.name AS from_account_name, ta.name AS to_account_name, cu.full_name AS created_by_name, pu.full_name AS posted_by_name, xu.full_name AS cancelled_by_name '
            . 'FROM transfer_vouchers tv '
            . 'LEFT JOIN cashbook_accounts fa ON fa.id = tv.from_account_id '
            . 'LEFT JOIN cashbook_accounts ta ON ta.id = tv.to_account_id '
            . 'LEFT JOIN users cu ON cu.id = tv.created_by '
            . 'LEFT JOIN users pu ON pu.id = tv.posted_by '
            . 'LEFT JOIN users xu ON xu.id = tv.cancelled_by '
            . 'WHERE ' . $whereSql . ' ORDER BY tv.id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return [
            'rows' => $st->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /** @return array<string,mixed> */
    public static function summary(PDO $pdo, string $fromDate, string $toDate): array
    {
        self::ensureSchema($pdo);
        $st = $pdo->prepare(
            'SELECT COUNT(*) AS total_vouchers,
                    COALESCE(SUM(amount), 0) AS total_amount,
                    SUM(CASE WHEN status = "POSTED" THEN 1 ELSE 0 END) AS posted_count,
                    SUM(CASE WHEN status = "DRAFT" THEN 1 ELSE 0 END) AS draft_count,
                    SUM(CASE WHEN status = "CANCELLED" THEN 1 ELSE 0 END) AS cancelled_count
             FROM transfer_vouchers
             WHERE voucher_date BETWEEN ? AND ?'
        );
        $st->execute([$fromDate, $toDate]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: [
            'total_vouchers' => 0,
            'total_amount' => 0,
            'posted_count' => 0,
            'draft_count' => 0,
            'cancelled_count' => 0,
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function recentAuditLogs(PDO $pdo, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $st = $pdo->prepare(
            'SELECT a.id, a.entity, a.entity_id, a.action, a.user_id, a.ip, a.meta_json, a.created_at,
                    u.full_name AS user_name, u.username AS user_username
             FROM cashbook_audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.entity = ?
             ORDER BY a.id DESC LIMIT ' . (int) $limit
        );
        $st->execute(['transfer_voucher']);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed> */
    public static function upsertDraftWithItems(PDO $pdo, array $payload, ?int $userId = null): array
    {
        self::ensureSchema($pdo);
        self::ensureVoucherSchema($pdo);
        
        $data = self::normalizePayload($payload);
        $items = $payload['items'] ?? [];
        
        if (empty($items)) {
            throw new InvalidArgumentException('At least one line item is required.');
        }

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($items as $item) {
            $debit = (float) ($item['debit_amount'] ?? 0);
            $credit = (float) ($item['credit_amount'] ?? 0);
            if ($debit <= 0 && $credit <= 0) {
                throw new InvalidArgumentException('Each line item must have either debit or credit amount.');
            }
            if (empty($item['account_name'])) {
                throw new InvalidArgumentException('Account name is required for each line item.');
            }
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        $existingId = (int) ($payload['id'] ?? 0);
        $voucher = null;
        if ($existingId > 0) {
            $voucher = self::getVoucher($pdo, $existingId);
            if (!$voucher) {
                throw new RuntimeException('Transfer voucher not found.');
            }
            if (($voucher['status'] ?? '') !== 'DRAFT') {
                throw new RuntimeException('Only draft vouchers can be edited.');
            }
            $data['fiscal_year'] = (string) ($voucher['fiscal_year'] ?? $data['fiscal_year']);
        }

        $attempts = 0;
        while ($attempts < 3) {
            $attempts++;
            try {
                $pdo->beginTransaction();
                
                if ($voucher === null) {
                    $sequenceNo = self::nextSequenceNo($pdo, $data['fiscal_year']);
                    $voucherNo = self::formatVoucherNo($data['fiscal_year'], $sequenceNo);
                    
                    $st = $pdo->prepare(
                        'INSERT INTO vouchers (voucher_number, voucher_type, fiscal_year, voucher_date, payment_mode, 
                         reference_number, narration, status, total_debit, total_credit, balance_amount, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $st->execute([
                        $voucherNo,
                        'TRANSFER',
                        $data['fiscal_year'],
                        $data['voucher_date'],
                        $data['payment_mode'] ?? 'CASH',
                        $data['reference_number'],
                        $data['narration'],
                        'DRAFT',
                        $totalDebit,
                        $totalCredit,
                        $totalDebit - $totalCredit,
                        $userId,
                    ]);
                    $voucherId = (int) $pdo->lastInsertId();
                    
                    $st = $pdo->prepare(
                        'INSERT INTO transfer_vouchers (voucher_no, sequence_no, fiscal_year, voucher_date, 
                         from_account_id, to_account_id, amount, reference_number, narration, status, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $st->execute([
                        $voucherNo,
                        $sequenceNo,
                        $data['fiscal_year'],
                        $data['voucher_date'],
                        0,
                        0,
                        $totalDebit,
                        $data['reference_number'],
                        $data['narration'],
                        'DRAFT',
                        $userId,
                    ]);
                } else {
                    $voucherId = (int) $voucher['id'];
                    $sequenceNo = (int) $voucher['sequence_no'];
                    $voucherNo = (string) $voucher['voucher_no'];
                    
                    $st = $pdo->prepare(
                        'UPDATE vouchers
                         SET voucher_date = ?, fiscal_year = ?, payment_mode = ?, reference_number = ?, narration = ?,
                         total_debit = ?, total_credit = ?, balance_amount = ?, updated_at = CURRENT_TIMESTAMP
                         WHERE id = ?'
                    );
                    $st->execute([
                        $data['voucher_date'],
                        $data['fiscal_year'],
                        $data['payment_mode'] ?? 'CASH',
                        $data['reference_number'],
                        $data['narration'],
                        $totalDebit,
                        $totalCredit,
                        $totalDebit - $totalCredit,
                        $voucherId,
                    ]);
                    
                    $st = $pdo->prepare(
                        'UPDATE transfer_vouchers
                         SET voucher_date = ?, fiscal_year = ?, amount = ?, reference_number = ?, narration = ?, updated_at = CURRENT_TIMESTAMP
                         WHERE id = ?'
                    );
                    $st->execute([
                        $data['voucher_date'],
                        $data['fiscal_year'],
                        $totalDebit,
                        $data['reference_number'],
                        $data['narration'],
                        $voucherId,
                    ]);
                    
                    $st = $pdo->prepare('DELETE FROM voucher_items WHERE voucher_id = ?');
                    $st->execute([$voucherId]);
                }
                
                $lineNumber = 0;
                foreach ($items as $item) {
                    $lineNumber++;
                    $accountId = (int) ($item['account_id'] ?? 0);
                    
                    if ($accountId <= 0) {
                        $accountName = trim($item['account_name'] ?? '');
                        if ($accountName) {
                            $st = $pdo->prepare('SELECT id FROM cashbook_accounts WHERE name = ? LIMIT 1');
                            $st->execute([$accountName]);
                            $accountId = (int) ($st->fetchColumn() ?: 0);
                        }
                    }
                    
                    $st = $pdo->prepare(
                        'INSERT INTO voucher_items (voucher_id, line_number, ledger_account_id, account_name, account_code,
                         debit_amount, credit_amount, description)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $st->execute([
                        $voucherId,
                        $lineNumber,
                        $accountId > 0 ? $accountId : null,
                        $item['account_name'],
                        $accountId > 0 ? self::getAccountCode($pdo, $accountId) : '',
                        (float) ($item['debit_amount'] ?? 0),
                        (float) ($item['credit_amount'] ?? 0),
                        $item['narration'] ?? null,
                    ]);
                }
                
                $pdo->commit();
                return self::getVoucher($pdo, $voucherId) ?? [
                    'id' => $voucherId,
                    'voucher_no' => $voucherNo,
                    'sequence_no' => $sequenceNo,
                    'fiscal_year' => $data['fiscal_year'],
                ];
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if (str_contains($e->getMessage(), 'uq_transfer_voucher_seq') || str_contains($e->getMessage(), 'Duplicate entry')) {
                    continue;
                }
                throw $e;
            }
        }

        throw new RuntimeException('Unable to allocate a transfer voucher number.');
    }

    /** @return array<string,mixed> */
    public static function postVoucherWithItems(PDO $pdo, array $payload, ?int $userId = null): array
    {
        self::ensureSchema($pdo);
        self::ensureVoucherSchema($pdo);
        
        $voucher = self::upsertDraftWithItems($pdo, $payload, $userId);
        $voucherId = (int) ($voucher['id'] ?? 0);
        if ($voucherId <= 0) {
            throw new RuntimeException('Unable to save transfer voucher.');
        }

        $voucher = self::getVoucher($pdo, $voucherId);
        if (!$voucher) {
            throw new RuntimeException('Transfer voucher not found.');
        }
        if (($voucher['status'] ?? '') !== 'DRAFT') {
            throw new RuntimeException('Only draft vouchers can be posted.');
        }

        $st = $pdo->prepare('SELECT * FROM voucher_items WHERE voucher_id = ? ORDER BY line_number');
        $st->execute([$voucherId]);
        $items = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($items)) {
            throw new RuntimeException('No line items found for voucher.');
        }

        $pdo->beginTransaction();
        try {
            $debitItems = array_filter($items, fn($i) => (float) ($i['debit_amount'] ?? 0) > 0);
            $creditItems = array_filter($items, fn($i) => (float) ($i['credit_amount'] ?? 0) > 0);

            if (count($debitItems) === 1 && count($creditItems) === 1) {
                $debitItem = reset($debitItems);
                $creditItem = reset($creditItems);
                
                $fromAccountId = (int) ($creditItem['ledger_account_id'] ?? 0);
                $toAccountId = (int) ($debitItem['ledger_account_id'] ?? 0);
                $amount = (float) $debitItem['debit_amount'];
                
                if ($fromAccountId > 0 && $toAccountId > 0 && $amount > 0) {
                    $transferId = CashbookRepository::addTransfer(
                        $pdo,
                        $fromAccountId,
                        $toAccountId,
                        $amount,
                        (string) ($voucher['voucher_date'] . ' 12:00:00'),
                        trim((string) ($voucher['narration'] ?? '')) !== '' ? (string) $voucher['narration'] : (string) ($voucher['voucher_no'] ?? null),
                        true,
                        $userId
                    );

                    $st = $pdo->prepare(
                        'UPDATE transfer_vouchers
                         SET status = ?, cashbook_transfer_id = ?, posted_by = ?, posted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                         WHERE id = ?'
                    );
                    $st->execute(['POSTED', $transferId, $userId, $voucherId]);
                }
            } else {
                foreach ($items as $item) {
                    $accountId = (int) ($item['ledger_account_id'] ?? 0);
                    if ($accountId <= 0) {
                        continue;
                    }
                    
                    $debitAmount = (float) ($item['debit_amount'] ?? 0);
                    $creditAmount = (float) ($item['credit_amount'] ?? 0);
                    
                    if ($debitAmount > 0) {
                        $st = $pdo->prepare(
                            'INSERT INTO cashbook_transactions (account_id, txn_type, amount, occurred_at, notes, reference_no, created_by)
                             VALUES (?, ?, ?, ?, ?, ?, ?)'
                        );
                        $st->execute([
                            $accountId,
                            'expense',
                            $debitAmount,
                            $voucher['voucher_date'] . ' 12:00:00',
                            $item['description'] ?? $voucher['narration'],
                            $voucher['voucher_no'],
                            $userId,
                        ]);
                    }
                    
                    if ($creditAmount > 0) {
                        $st = $pdo->prepare(
                            'INSERT INTO cashbook_transactions (account_id, txn_type, amount, occurred_at, notes, reference_no, created_by)
                             VALUES (?, ?, ?, ?, ?, ?, ?)'
                        );
                        $st->execute([
                            $accountId,
                            'income',
                            $creditAmount,
                            $voucher['voucher_date'] . ' 12:00:00',
                            $item['description'] ?? $voucher['narration'],
                            $voucher['voucher_no'],
                            $userId,
                        ]);
                    }
                }
                
                $st = $pdo->prepare(
                    'UPDATE transfer_vouchers
                     SET status = ?, posted_by = ?, posted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?'
                );
                $st->execute(['POSTED', $userId, $voucherId]);
            }

            $st = $pdo->prepare(
                'UPDATE vouchers SET status = ?, posted_by = ?, posted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
            );
            $st->execute(['POSTED', $userId, $voucherId]);

            CashbookRepository::audit($pdo, 'transfer_voucher', (string) $voucherId, 'post', $userId, [
                'voucher_no' => $voucher['voucher_no'],
                'amount' => (float) ($voucher['amount'] ?? 0),
            ]);

            $pdo->commit();
            return self::getVoucher($pdo, $voucherId) ?? $voucher;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function ensureVoucherSchema(PDO $pdo): void
    {
        try {
            $pdo->exec(
                <<<'SQL'
CREATE TABLE IF NOT EXISTS vouchers (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_number varchar(50) NOT NULL UNIQUE,
  voucher_type enum('RECEIPT', 'PAYMENT', 'JOURNAL', 'TRANSFER', 'CONTRA') NOT NULL DEFAULT 'PAYMENT',
  fiscal_year varchar(10) NOT NULL,
  voucher_date date NOT NULL,
  payment_mode enum('CASH', 'BANK', 'CHEQUE', 'ONLINE', 'PETTY_CASH', 'OTHER') NOT NULL DEFAULT 'CASH',
  cheque_number varchar(50) NULL,
  cheque_date date NULL,
  cheque_bank varchar(100) NULL,
  reference_number varchar(100) NULL,
  narration text NULL,
  status enum('DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
  approval_status enum('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
  total_debit decimal(15,2) NOT NULL DEFAULT 0.00,
  total_credit decimal(15,2) NOT NULL DEFAULT 0.00,
  balance_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  created_by bigint unsigned NULL,
  approved_by bigint unsigned NULL,
  posted_by bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  posted_at timestamp NULL,
  deleted_at timestamp NULL,
  KEY idx_voucher_date (voucher_date),
  KEY idx_status (status),
  KEY idx_fiscal_year (fiscal_year),
  KEY idx_voucher_type (voucher_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            );
        } catch (Throwable $e) {
        }

        try {
            $pdo->exec(
                <<<'SQL'
CREATE TABLE IF NOT EXISTS voucher_items (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_id bigint unsigned NOT NULL,
  line_number int NOT NULL,
  ledger_account_id bigint unsigned NULL,
  account_name varchar(255) NOT NULL,
  account_code varchar(50) NOT NULL,
  employee_id bigint unsigned NULL,
  customer_id bigint unsigned NULL,
  supplier_id bigint unsigned NULL,
  debit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  credit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  description text NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_voucher_id (voucher_id),
  KEY idx_account_code (account_code),
  KEY idx_line_number (line_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            );
        } catch (Throwable $e) {
        }
    }

    private static function getAccountCode(PDO $pdo, int $accountId): string
    {
        try {
            $st = $pdo->prepare('SELECT name FROM cashbook_accounts WHERE id = ? LIMIT 1');
            $st->execute([$accountId]);
            $name = $st->fetchColumn();
            return $name ? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 10)) : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    /** @return array<string,mixed> */
    public static function listAllVouchers(PDO $pdo, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $status = $filters['status'] ?? '';
        $fromDate = $filters['from_date'] ?? '';
        $toDate = $filters['to_date'] ?? '';
        $query = $filters['q'] ?? '';

        $offset = ($page - 1) * $limit;
        $where = ['v.voucher_type = ?'];
        $params = ['TRANSFER'];

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
            "SELECT v.id, v.voucher_number, v.voucher_date, v.payment_mode, v.status,
                    v.total_debit AS amount, v.narration, v.reference_number,
                    v.created_at, v.posted_at, v.created_by,
                    GROUP_CONCAT(CONCAT(vi.account_name, ' (', 
                        CASE WHEN vi.debit_amount > 0 THEN CONCAT('Dr: ', vi.debit_amount)
                             WHEN vi.credit_amount > 0 THEN CONCAT('Cr: ', vi.credit_amount)
                             ELSE ''
                        END, ')') SEPARATOR ', ') AS accounts_summary
             FROM vouchers v
             LEFT JOIN voucher_items vi ON v.id = vi.voucher_id
             WHERE {$whereClause}
             GROUP BY v.id
             ORDER BY v.created_at DESC
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
    public static function getVoucherWithItems(PDO $pdo, int $voucherId): ?array
    {
        $st = $pdo->prepare('SELECT * FROM vouchers WHERE id = ? AND voucher_type = ? LIMIT 1');
        $st->execute([$voucherId, 'TRANSFER']);
        $voucher = $st->fetch(PDO::FETCH_ASSOC);
        if (!$voucher) {
            return null;
        }

        $st = $pdo->prepare('SELECT * FROM voucher_items WHERE voucher_id = ? ORDER BY line_number');
        $st->execute([$voucherId]);
        $items = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $voucher['items'] = $items;
        return $voucher;
    }
}