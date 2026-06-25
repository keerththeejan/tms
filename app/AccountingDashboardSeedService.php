<?php

declare(strict_types=1);

/**
 * Seeds demo accounting vouchers when no posted ledger activity exists.
 * Skips entirely if any POSTED voucher is present.
 */
class AccountingDashboardSeedService
{
    public static function seedIfEmpty(PDO $pdo): bool
    {
        AccountingVoucherRepository::ensureSchema($pdo);

        $posted = (int) $pdo->query(
            "SELECT COUNT(*) FROM vouchers WHERE status = 'POSTED' AND deleted_at IS NULL"
        )->fetchColumn();
        if ($posted > 0) {
            return false;
        }

        $ledger = (int) $pdo->query('SELECT COUNT(*) FROM ledger_entries')->fetchColumn();
        if ($ledger > 0) {
            return false;
        }

        $cash = AccountRepository::getByCode($pdo, 'CASH_MAIN');
        $bank = AccountRepository::getByCode($pdo, 'BANK_MAIN');
        $capital = AccountRepository::getByCode($pdo, 'CAPITAL_OWNER');
        $sales = AccountRepository::getByCode($pdo, 'SALES_FREIGHT');
        $fuel = AccountRepository::getByCode($pdo, 'FUEL_DIESEL');

        if (!$cash || !$bank || !$capital || !$sales || !$fuel) {
            return false;
        }

        $fiscalYear = date('Y') . '-' . (date('Y') + 1);
        $userId = null;

        $pdo->beginTransaction();
        try {
            self::createPostedVoucher($pdo, [
                'voucher_type' => 'JOURNAL',
                'voucher_date' => date('Y-m-01'),
                'fiscal_year' => $fiscalYear,
                'narration' => 'Opening balances (demo)',
                'payment_mode' => 'CASH',
                'created_by' => $userId,
            ], [
                ['account_id' => (int) $cash['id'], 'debit_amount' => 50000, 'credit_amount' => 0],
                ['account_id' => (int) $bank['id'], 'debit_amount' => 200000, 'credit_amount' => 0],
                ['account_id' => (int) $capital['id'], 'debit_amount' => 0, 'credit_amount' => 250000],
            ]);

            $prevMonth = date('Y-m-15', strtotime('first day of last month'));
            self::createPostedVoucher($pdo, [
                'voucher_type' => 'RECEIPT',
                'voucher_date' => $prevMonth,
                'fiscal_year' => $fiscalYear,
                'narration' => 'Freight collection (demo)',
                'payment_mode' => 'CASH',
                'reference_number' => 'DEMO-RCP-001',
                'created_by' => $userId,
            ], [
                ['account_id' => (int) $cash['id'], 'debit_amount' => 120000, 'credit_amount' => 0],
                ['account_id' => (int) $sales['id'], 'debit_amount' => 0, 'credit_amount' => 120000],
            ]);

            self::createPostedVoucher($pdo, [
                'voucher_type' => 'PAYMENT',
                'voucher_date' => $prevMonth,
                'fiscal_year' => $fiscalYear,
                'narration' => 'Fuel expense (demo)',
                'payment_mode' => 'CASH',
                'reference_number' => 'DEMO-PAY-001',
                'created_by' => $userId,
            ], [
                ['account_id' => (int) $fuel['id'], 'debit_amount' => 45000, 'credit_amount' => 0],
                ['account_id' => (int) $cash['id'], 'debit_amount' => 0, 'credit_amount' => 45000],
            ]);

            self::createPostedVoucher($pdo, [
                'voucher_type' => 'RECEIPT',
                'voucher_date' => date('Y-m-d'),
                'fiscal_year' => $fiscalYear,
                'narration' => 'Freight sales MTD (demo)',
                'payment_mode' => 'BANK',
                'reference_number' => 'DEMO-RCP-002',
                'created_by' => $userId,
            ], [
                ['account_id' => (int) $bank['id'], 'debit_amount' => 85000, 'credit_amount' => 0],
                ['account_id' => (int) $sales['id'], 'debit_amount' => 0, 'credit_amount' => 85000],
            ]);

            self::createPostedVoucher($pdo, [
                'voucher_type' => 'PAYMENT',
                'voucher_date' => date('Y-m-d'),
                'fiscal_year' => $fiscalYear,
                'narration' => 'Diesel purchase MTD (demo)',
                'payment_mode' => 'CASH',
                'reference_number' => 'DEMO-PAY-002',
                'created_by' => $userId,
            ], [
                ['account_id' => (int) $fuel['id'], 'debit_amount' => 32000, 'credit_amount' => 0],
                ['account_id' => (int) $cash['id'], 'debit_amount' => 0, 'credit_amount' => 32000],
            ]);

            self::createPostedVoucher($pdo, [
                'voucher_type' => 'JOURNAL',
                'voucher_date' => date('Y-m-d'),
                'fiscal_year' => $fiscalYear,
                'narration' => 'Draft voucher pending approval (demo)',
                'payment_mode' => 'CASH',
                'created_by' => $userId,
                'leave_draft' => true,
            ], [
                ['account_id' => (int) $fuel['id'], 'debit_amount' => 5000, 'credit_amount' => 0],
                ['account_id' => (int) $cash['id'], 'debit_amount' => 0, 'credit_amount' => 5000],
            ]);

            $pdo->commit();

            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @param list<array<string,mixed>> $lines */
    private static function createPostedVoucher(PDO $pdo, array $header, array $lines): void
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit_amount'] ?? 0);
            $totalCredit += (float) ($line['credit_amount'] ?? 0);
        }

        $header['total_debit'] = $totalDebit;
        $header['total_credit'] = $totalCredit;

        $voucher = AccountingVoucherRepository::create($pdo, $header);
        $voucherId = (int) ($voucher['id'] ?? 0);
        if ($voucherId <= 0) {
            throw new RuntimeException('Failed to create demo voucher.');
        }

        VoucherDetailRepository::createBatch($pdo, $voucherId, $lines);

        if (!empty($header['leave_draft'])) {
            return;
        }

        $details = VoucherDetailRepository::getByVoucherId($pdo, $voucherId);
        $ledgerEntries = [];
        foreach ($details as $detail) {
            $ledgerEntries[] = [
                'voucher_detail_id' => $detail['id'],
                'account_id' => $detail['account_id'],
                'entry_date' => $header['voucher_date'],
                'voucher_type' => $header['voucher_type'],
                'voucher_number' => $voucher['voucher_number'],
                'debit_amount' => $detail['debit_amount'],
                'credit_amount' => $detail['credit_amount'],
                'balance_type' => ((float) ($detail['debit_amount'] ?? 0) > 0) ? 'DEBIT' : 'CREDIT',
                'narration' => $detail['narration'] ?? $header['narration'],
                'branch_id' => $header['branch_id'] ?? null,
            ];
        }

        LedgerEntryRepository::createBatch($pdo, $voucherId, $ledgerEntries);

        $st = $pdo->prepare(
            'UPDATE vouchers SET status = ?, posted_at = CURRENT_TIMESTAMP, posted_by = ? WHERE id = ?'
        );
        $st->execute(['POSTED', $header['created_by'] ?? null, $voucherId]);
    }
}
