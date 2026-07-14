<?php

declare(strict_types=1);

/**
 * Enterprise voucher deletion service.
 *
 * Permanently removes a voucher and all linked accounting records inside a
 * single DB transaction so Day Book / Ledger / Trial Balance stay consistent
 * (balances are derived from remaining POSTED ledger data).
 */
class AccountingVoucherDeleteService
{
    /**
     * Full secure delete. Returns true on success; throws RuntimeException on validation / failure.
     */
    public static function deleteVoucher(
        PDO $pdo,
        int $voucherId,
        ?int $userId = null,
        string $reason = ''
    ): bool {
        if ($voucherId <= 0) {
            throw new RuntimeException('Invalid voucher id.');
        }

        $voucher = AccountingVoucherRepository::getById($pdo, $voucherId);
        if (!$voucher) {
            throw new RuntimeException('Voucher not found.');
        }

        self::assertCanDelete($pdo, $voucher);

        // Ensure audit schema outside the delete transaction (DDL would auto-commit on MySQL).
        AuditLogRepository::ensureSchema($pdo);

        $ownTxn = !$pdo->inTransaction();
        if ($ownTxn) {
            $pdo->beginTransaction();
        }

        try {
            // Capture snapshot for audit before any remove
            $snapshot = [
                'voucher_number' => (string) ($voucher['voucher_number'] ?? ''),
                'voucher_type' => (string) ($voucher['voucher_type'] ?? ''),
                'voucher_date' => (string) ($voucher['voucher_date'] ?? ''),
                'fiscal_year' => (string) ($voucher['fiscal_year'] ?? ''),
                'status' => (string) ($voucher['status'] ?? ''),
                'approval_status' => (string) ($voucher['approval_status'] ?? ''),
                'total_debit' => (float) ($voucher['total_debit'] ?? 0),
                'total_credit' => (float) ($voucher['total_credit'] ?? 0),
                'narration' => (string) ($voucher['narration'] ?? ''),
                'reason' => $reason,
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ];

            self::logDeletion($pdo, $voucherId, $snapshot, $userId);

            self::deleteAccountingTransactions($pdo, $voucherId);
            self::deleteJournalEntries($pdo, $voucherId);
            self::deleteLedgerEntries($pdo, $voucherId);
            self::deleteVoucherDetails($pdo, $voucherId);
            self::deleteVoucherHeader($pdo, $voucherId);

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

    /**
     * Validation gates before delete.
     *
     * @param array<string,mixed> $voucher
     */
    public static function assertCanDelete(PDO $pdo, array $voucher): void
    {
        $id = (int) ($voucher['id'] ?? 0);
        $number = (string) ($voucher['voucher_number'] ?? '');

        // Locked voucher (approval_status LOCKED, or is_locked column if present)
        if (self::isVoucherLocked($voucher)) {
            throw new RuntimeException('Voucher is locked and cannot be deleted.');
        }

        // Closed financial year
        $fiscalYear = (string) ($voucher['fiscal_year'] ?? '');
        if ($fiscalYear !== '' && self::isFiscalYearClosed($fiscalYear)) {
            throw new RuntimeException('Voucher belongs to a closed financial year and cannot be deleted.');
        }

        // Approved + system policy block
        $approval = strtoupper((string) ($voucher['approval_status'] ?? ''));
        if ($approval === 'APPROVED' && self::blockDeleteWhenApproved()) {
            throw new RuntimeException('Approved vouchers cannot be deleted under current system policy.');
        }

        // Dependent records that must block delete
        $dependents = self::findBlockingDependents($pdo, $id);
        if ($dependents !== []) {
            throw new RuntimeException(
                'Unable to delete voucher ' . ($number !== '' ? $number : '#' . $id)
                . '. Dependent records exist: ' . implode(', ', $dependents) . '.'
            );
        }
    }

    /** @param array<string,mixed> $voucher */
    private static function isVoucherLocked(array $voucher): bool
    {
        if (array_key_exists('is_locked', $voucher) && (int) $voucher['is_locked'] === 1) {
            return true;
        }
        $approval = strtoupper(trim((string) ($voucher['approval_status'] ?? '')));
        // Future-safe: reject LOCKED even if enum is extended later
        return $approval === 'LOCKED';
    }

    private static function isFiscalYearClosed(string $fiscalYear): bool
    {
        $closed = Helpers::configGet('accounting.closed_fiscal_years', []);
        if (!is_array($closed)) {
            return false;
        }
        $normalized = array_map('strval', $closed);
        return in_array($fiscalYear, $normalized, true);
    }

    private static function blockDeleteWhenApproved(): bool
    {
        return (bool) Helpers::configGet('accounting.block_delete_when_approved', true);
    }

    /**
     * Soft dependents that block hard delete (e.g. approved expenses still pointing here).
     *
     * @return list<string>
     */
    private static function findBlockingDependents(PDO $pdo, int $voucherId): array
    {
        $blocking = [];

        if (self::tableExists($pdo, 'expenses') && self::columnExists($pdo, 'expenses', 'voucher_id')) {
            $st = $pdo->prepare(
                "SELECT COUNT(*) FROM expenses
                 WHERE voucher_id = ?
                   AND COALESCE(status, '') IN ('approved', 'APPROVED')"
            );
            $st->execute([$voucherId]);
            $count = (int) $st->fetchColumn();
            if ($count > 0) {
                $blocking[] = $count . ' approved expense(s)';
            }
        }

        return $blocking;
    }

    public static function deleteVoucherDetails(PDO $pdo, int $voucherId): void
    {
        $st = $pdo->prepare('DELETE FROM voucher_details WHERE voucher_id = ?');
        $st->execute([$voucherId]);
    }

    public static function deleteLedgerEntries(PDO $pdo, int $voucherId): void
    {
        $st = $pdo->prepare('DELETE FROM ledger_entries WHERE voucher_id = ?');
        $st->execute([$voucherId]);
    }

    /**
     * Journal / line-item tables used by payment/transfer voucher engines.
     */
    public static function deleteJournalEntries(PDO $pdo, int $voucherId): void
    {
        if (self::tableExists($pdo, 'voucher_items') && self::columnExists($pdo, 'voucher_items', 'voucher_id')) {
            $st = $pdo->prepare('DELETE FROM voucher_items WHERE voucher_id = ?');
            $st->execute([$voucherId]);
        }
    }

    /**
     * Linked accounting / TMS bridge records.
     */
    public static function deleteAccountingTransactions(PDO $pdo, int $voucherId): void
    {
        if (self::tableExists($pdo, 'transport_voucher_mapping')) {
            $st = $pdo->prepare('DELETE FROM transport_voucher_mapping WHERE voucher_id = ?');
            $st->execute([$voucherId]);
        }

        if (self::tableExists($pdo, 'employee_payments') && self::columnExists($pdo, 'employee_payments', 'voucher_id')) {
            $st = $pdo->prepare('DELETE FROM employee_payments WHERE voucher_id = ?');
            $st->execute([$voucherId]);
        }

        // Non-blocking draft expenses: clear link so header can be removed
        if (self::tableExists($pdo, 'expenses') && self::columnExists($pdo, 'expenses', 'voucher_id')) {
            $st = $pdo->prepare(
                "UPDATE expenses SET voucher_id = NULL
                 WHERE voucher_id = ?
                   AND COALESCE(status, '') NOT IN ('approved', 'APPROVED')"
            );
            $st->execute([$voucherId]);
        }
    }

    public static function deleteVoucherHeader(PDO $pdo, int $voucherId): void
    {
        $st = $pdo->prepare('DELETE FROM vouchers WHERE id = ?');
        $st->execute([$voucherId]);
        if ($st->rowCount() < 1) {
            throw new RuntimeException('Unable to delete voucher header.');
        }
    }

    /**
     * Audit trail: voucher number, deleted by, date/time, IP, optional reason.
     *
     * @param array<string,mixed> $snapshot
     */
    public static function logDeletion(PDO $pdo, int $voucherId, array $snapshot, ?int $userId): void
    {
        AuditLogRepository::log(
            $pdo,
            'voucher',
            $voucherId,
            'DELETE',
            $snapshot,
            [
                'voucher_number' => $snapshot['voucher_number'] ?? '',
                'deleted_by' => $userId,
                'deleted_date' => date('Y-m-d'),
                'deleted_time' => date('H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'reason' => (string) ($snapshot['reason'] ?? ''),
            ],
            $userId
        );
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }
        $st = $pdo->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        $st->execute([$table]);
        $cache[$table] = (bool) $st->fetchColumn();
        return $cache[$table];
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        static $cache = [];
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $st = $pdo->prepare(
            'SELECT 1 FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1'
        );
        $st->execute([$table, $column]);
        $cache[$key] = (bool) $st->fetchColumn();
        return $cache[$key];
    }
}
