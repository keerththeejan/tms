<?php

declare(strict_types=1);

/**
 * VoucherUpdateService — Admin-only in-place editing of saved vouchers (DRAFT or POSTED).
 *
 * Transaction-safe: replaces header, details, and ledger entries atomically.
 * Writes full before/after audit via AuditLogRepository.
 * Does NOT auto-balance, insert counter entries, or change voucher numbers.
 */
class VoucherUpdateService
{
    /**
     * Update an existing voucher (admin only).
     *
     * @param PDO $pdo
     * @param int $voucherId
     * @param array $headerData  Editable header fields
     * @param list<array> $details  User-entered detail lines
     * @param int|null $userId  Current admin user ID
     * @return array{ok: bool, data?: array, error?: string}
     */
    public static function updateVoucher(PDO $pdo, int $voucherId, array $headerData, array $details, ?int $userId = null): array
    {
        // 1. Authorization
        if (!Auth::isAdmin()) {
            return ['ok' => false, 'error' => 'Only administrators can edit vouchers.'];
        }

        // 2. Lock and load existing voucher
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM vouchers WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $st->execute([$voucherId]);
            $oldVoucher = $st->fetch(PDO::FETCH_ASSOC);

            if (!$oldVoucher) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Voucher not found.'];
            }

            $status = (string) ($oldVoucher['status'] ?? '');

            // 3. Eligibility: allow DRAFT and POSTED; reject CANCELLED, locked
            if ($status === 'CANCELLED') {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Cancelled vouchers cannot be edited.'];
            }

            if (!empty($oldVoucher['is_locked']) && (int) $oldVoucher['is_locked'] === 1) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'This voucher is locked and cannot be edited.'];
            }

            // 4. Snapshot old state for audit
            $oldDetails = VoucherDetailRepository::getByVoucherId($pdo, $voucherId);
            $oldLedger = LedgerEntryRepository::getByVoucherId($pdo, $voucherId);

            // 5. Validate new details
            VoucherAutoLedgerService::validateSimpleLines($details);
            $detailsForStorage = VoucherAutoLedgerService::detailsForStorage($details);

            if ($detailsForStorage === []) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'At least one valid voucher line is required.'];
            }

            // 6. Recalculate totals
            $totalDebit = 0.0;
            $totalCredit = 0.0;
            foreach ($detailsForStorage as $line) {
                $totalDebit += (float) ($line['debit_amount'] ?? 0);
                $totalCredit += (float) ($line['credit_amount'] ?? 0);
            }

            // 7. Type-specific validation
            $voucherType = strtoupper((string) ($oldVoucher['voucher_type'] ?? ''));
            if ($voucherType === 'JOURNAL' && abs($totalDebit - $totalCredit) > 0.009) {
                $pdo->rollBack();
                return [
                    'ok' => false,
                    'error' => 'Journal voucher must be balanced: Debit ('
                        . number_format($totalDebit, 2) . ') must equal Credit ('
                        . number_format($totalCredit, 2) . ').',
                ];
            }

            // 8. Update header (preserve immutable identity)
            $allowedFields = [
                'voucher_date', 'reference_number', 'payment_mode',
                'cheque_number', 'cheque_date', 'bank_account_id',
                'narration', 'branch_id',
            ];
            $sets = ['total_debit = ?', 'total_credit = ?'];
            $params = [$totalDebit, $totalCredit];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $headerData)) {
                    $sets[] = "{$field} = ?";
                    $params[] = $headerData[$field];
                }
            }

            $params[] = $voucherId;
            $sql = 'UPDATE vouchers SET ' . implode(', ', $sets) . ' WHERE id = ?';
            $pdo->prepare($sql)->execute($params);

            // 9. Replace details (delete + recreate)
            VoucherDetailRepository::createBatch($pdo, $voucherId, $detailsForStorage);

            // 10. Rebuild ledger entries (handles both DRAFT and POSTED)
            $newDetails = VoucherDetailRepository::getByVoucherId($pdo, $voucherId);
            $updatedVoucher = AccountingVoucherRepository::getById($pdo, $voucherId);
            if (!$updatedVoucher) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Failed to reload voucher after update.'];
            }

            // Delete old ledger entries and recreate from current details
            $ledgerEntries = [];
            foreach ($newDetails as $detail) {
                $debit = (float) ($detail['debit_amount'] ?? 0);
                $credit = (float) ($detail['credit_amount'] ?? 0);
                $ledgerEntries[] = [
                    'voucher_detail_id' => $detail['id'],
                    'account_id' => $detail['account_id'],
                    'entry_date' => $updatedVoucher['voucher_date'],
                    'voucher_type' => $updatedVoucher['voucher_type'],
                    'voucher_number' => $updatedVoucher['voucher_number'],
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'balance_type' => ($debit > 0) ? 'DEBIT' : 'CREDIT',
                    'narration' => $detail['narration'] ?? $updatedVoucher['narration'],
                    'branch_id' => $updatedVoucher['branch_id'],
                ];
            }

            LedgerEntryRepository::createBatch($pdo, $voucherId, $ledgerEntries);

            // 11. Ensure status is POSTED (if it was already posted, keep it; if draft, post it now)
            if ($status !== 'POSTED') {
                $pdo->prepare(
                    'UPDATE vouchers SET status = ?, posted_at = CURRENT_TIMESTAMP, posted_by = ? WHERE id = ?'
                )->execute(['POSTED', $userId, $voucherId]);
            }

            // 12. Audit log
            $newVoucher = AccountingVoucherRepository::getById($pdo, $voucherId);
            $newLedger = LedgerEntryRepository::getByVoucherId($pdo, $voucherId);

            AuditLogRepository::log(
                $pdo,
                'voucher',
                $voucherId,
                'UPDATE',
                [
                    'header' => $oldVoucher,
                    'details' => $oldDetails,
                    'ledger' => $oldLedger,
                ],
                [
                    'header' => $newVoucher,
                    'details' => $newDetails,
                    'ledger' => $newLedger,
                ],
                $userId
            );

            $pdo->commit();

            return [
                'ok' => true,
                'data' => $newVoucher,
                'message' => 'Voucher Updated Successfully.',
            ];
        } catch (InvalidArgumentException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
