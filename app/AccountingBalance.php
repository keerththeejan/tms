<?php

declare(strict_types=1);

/**
 * AccountingBalanceService — single source of truth for TMS cash-based ERP balances.
 *
 * =============================================================================
 * BUSINESS RULES (locked — do not invert)
 * =============================================================================
 *   Credit = Cash In  (money received)  → increases balance
 *   Debit  = Cash Out (money paid)      → decreases balance
 *
 *   Closing Balance = Opening Balance + Total Credit − Total Debit
 *
 * Voucher mapping:
 *   Receipt  → Credit (Cash In)
 *   Payment  → Debit  (Cash Out)
 *   Journal / Contra / Transfer → follow line-level debit/credit as posted
 *
 * Opening for a period uses ONLY transactions with date STRICTLY BEFORE From Date,
 * plus Chart-of-Accounts master openings (CREDIT type = Cash In, DEBIT type = Cash Out).
 *
 * Valid vouchers only: status = POSTED, deleted_at IS NULL
 * (excludes Draft, Cancelled, Deleted).
 * =============================================================================
 */
class AccountingBalanceService
{
    /**
     * Sign a master opening balance using Cash-In / Cash-Out rules.
     * CREDIT opening → positive (Cash In carried forward)
     * DEBIT opening  → negative (Cash Out / liability-style opening)
     */
    public static function signedAmount(float $amount, string $balanceType): float
    {
        $type = strtoupper(trim($balanceType));
        if ($type === 'DEBIT') {
            return -abs($amount);
        }

        // CREDIT (or blank treated as Cash In)
        return abs($amount);
    }

    /**
     * Opening balance before From Date.
     *
     * Opening = master_openings_net + credit_before − debit_before
     * (excludes the selected From Date itself)
     */
    public static function calculateOpeningBalance(
        float $debitBefore,
        float $creditBefore,
        float $masterOpeningsNet = 0.0
    ): float {
        return $masterOpeningsNet + $creditBefore - $debitBefore;
    }

    /** Period Cash Out total (Debit). */
    public static function calculateDebit(float $totalDebit): float
    {
        return $totalDebit;
    }

    /** Period Cash In total (Credit). */
    public static function calculateCredit(float $totalCredit): float
    {
        return $totalCredit;
    }

    /**
     * Closing Balance — THE only valid ERP formula:
     *   Closing = Opening + Total Credit − Total Debit
     */
    public static function calculateClosingBalance(
        float $openingBalance,
        float $totalDebit,
        float $totalCredit
    ): float {
        return $openingBalance + $totalCredit - $totalDebit;
    }

    /**
     * Alias used by repositories after a single posting line:
     * running = previous + credit − debit
     */
    public static function applyMovement(float $balance, float $debit, float $credit): float
    {
        return self::calculateClosingBalance($balance, $debit, $credit);
    }

    /**
     * Full period summary for Day Book / exports.
     *
     * @return array{
     *   total_records: int,
     *   opening_balance: float,
     *   total_debit: float,
     *   total_credit: float,
     *   closing_balance: float
     * }
     */
    public static function periodSummary(
        int $totalRecords,
        float $debitBefore,
        float $creditBefore,
        float $totalDebit,
        float $totalCredit,
        float $masterOpeningsNet = 0.0
    ): array {
        $opening = self::calculateOpeningBalance($debitBefore, $creditBefore, $masterOpeningsNet);
        $debit = self::calculateDebit($totalDebit);
        $credit = self::calculateCredit($totalCredit);
        $closing = self::calculateClosingBalance($opening, $debit, $credit);

        return [
            'total_records' => $totalRecords,
            'opening_balance' => $opening,
            'total_debit' => $debit,
            'total_credit' => $credit,
            'closing_balance' => $closing,
        ];
    }

    /**
     * SQL predicate: only posted, non-deleted vouchers affect books.
     */
    public static function validVoucherPredicate(string $alias = 'v'): string
    {
        return "{$alias}.deleted_at IS NULL AND {$alias}.status = 'POSTED'";
    }

    /**
     * Net Chart-of-Accounts master openings under Cash-In/Cash-Out rules.
     * CREDIT openings add; DEBIT openings subtract.
     */
    public static function masterOpeningsNet(PDO $pdo): float
    {
        $st = $pdo->query(
            'SELECT COALESCE(SUM(
                CASE WHEN a.opening_balance_type = \'DEBIT\'
                     THEN -a.opening_balance
                     ELSE a.opening_balance
                END
             ), 0) AS net_opening
             FROM accounts a
             WHERE a.deleted_at IS NULL'
        );
        $row = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;

        return (float) ($row['net_opening'] ?? 0);
    }

    /**
     * Ledger / Cash Book balance for one account as of a date (inclusive).
     * Uses master opening + posted ledger lines via AccountingBalanceService formula.
     */
    public static function calculateLedgerBalance(
        PDO $pdo,
        int $accountId,
        ?string $asOfDate = null
    ): float {
        return AccountRepository::getBalance($pdo, $accountId, $asOfDate);
    }

    /**
     * Cash (or bank) group balance as of a date — sum of account balances in group.
     */
    public static function calculateCashBalance(
        PDO $pdo,
        int $accountId,
        ?string $asOfDate = null
    ): float {
        return self::calculateLedgerBalance($pdo, $accountId, $asOfDate);
    }

    /**
     * Display side for a signed balance under Cash-In/Cash-Out rules (Day Book).
     * Positive → CREDIT (Cash In surplus); Negative → DEBIT (Cash Out surplus).
     *
     * @return array{amount: float, type: string}
     */
    public static function displayBalance(float $signedBalance): array
    {
        return [
            'amount' => abs($signedBalance),
            'type' => $signedBalance >= 0 ? 'CREDIT' : 'DEBIT',
        ];
    }

    // -------------------------------------------------------------------------
    // General Ledger — Normal Balance (double-entry account ledgers)
    // -------------------------------------------------------------------------

    /**
     * Resolve Normal Balance for an account (DEBIT or CREDIT).
     * Prefers accounts.normal_balance, falls back to group nature, then DEBIT.
     *
     * @param array<string,mixed> $account
     */
    public static function resolveNormalBalance(array $account): string
    {
        $nb = strtoupper(trim((string) ($account['normal_balance'] ?? '')));
        if ($nb === 'DEBIT' || $nb === 'CREDIT') {
            return $nb;
        }
        $groupNature = strtoupper(trim((string) ($account['group_nature'] ?? $account['nature'] ?? '')));
        if ($groupNature === 'DEBIT' || $groupNature === 'CREDIT') {
            return $groupNature;
        }
        $groupType = strtoupper(trim((string) ($account['group_type'] ?? '')));
        if (in_array($groupType, ['LIABILITIES', 'CAPITAL', 'INCOME'], true)) {
            return 'CREDIT';
        }

        return 'DEBIT';
    }

    /**
     * Sign a Chart-of-Accounts master opening into the account's Normal Balance units.
     * Same side as Normal Balance → positive; opposite side → negative.
     */
    public static function signMasterOpening(
        float $amount,
        string $openingBalanceType,
        string $normalBalance
    ): float {
        $amount = abs($amount);
        $type = strtoupper(trim($openingBalanceType));
        $normal = strtoupper(trim($normalBalance)) === 'CREDIT' ? 'CREDIT' : 'DEBIT';
        if ($type === '') {
            $type = $normal;
        }
        if ($type === $normal) {
            return $amount;
        }

        return -$amount;
    }

    /**
     * Opening balance for a ledger period (Normal Balance convention).
     *
     * Debit-normal:  masterSigned + debit_before − credit_before
     * Credit-normal: masterSigned + credit_before − debit_before
     *
     * (Transactions on/after From Date are excluded — only date < From Date.)
     */
    public static function calculateLedgerOpeningBalance(
        string $normalBalance,
        float $masterSigned,
        float $debitBefore,
        float $creditBefore
    ): float {
        $normal = strtoupper(trim($normalBalance)) === 'CREDIT' ? 'CREDIT' : 'DEBIT';
        if ($normal === 'DEBIT') {
            return $masterSigned + $debitBefore - $creditBefore;
        }

        return $masterSigned + $creditBefore - $debitBefore;
    }

    /**
     * Apply one ledger line to the running balance (Normal Balance convention).
     *
     * Debit-normal:  balance + Debit − Credit
     * Credit-normal: balance + Credit − Debit
     */
    public static function calculateRunningBalance(
        string $normalBalance,
        float $currentBalance,
        float $debit,
        float $credit
    ): float {
        $normal = strtoupper(trim($normalBalance)) === 'CREDIT' ? 'CREDIT' : 'DEBIT';
        if ($normal === 'DEBIT') {
            return $currentBalance + $debit - $credit;
        }

        return $currentBalance + $credit - $debit;
    }

    /**
     * Closing balance for a ledger period (Normal Balance convention).
     * Same arithmetic as running: Opening ± period movements.
     */
    public static function calculateLedgerClosingBalance(
        string $normalBalance,
        float $openingBalance,
        float $totalDebit,
        float $totalCredit
    ): float {
        return self::calculateRunningBalance($normalBalance, $openingBalance, $totalDebit, $totalCredit);
    }

    /**
     * Present a signed ledger balance as absolute amount + Debit/Credit side.
     * Positive → on Normal Balance side; Negative → opposite side.
     * Never returns a negative amount.
     *
     * @return array{amount: float, type: string, signed: float, label: string}
     */
    public static function displayLedgerBalance(string $normalBalance, float $signedBalance): array
    {
        $normal = strtoupper(trim($normalBalance)) === 'CREDIT' ? 'CREDIT' : 'DEBIT';
        $opposite = $normal === 'DEBIT' ? 'CREDIT' : 'DEBIT';
        if (abs($signedBalance) < 0.0000001) {
            return [
                'amount' => 0.0,
                'type' => $normal,
                'signed' => 0.0,
                'label' => self::formatBalanceDrCr(0.0, $normal),
            ];
        }
        if ($signedBalance > 0) {
            $amount = abs($signedBalance);

            return [
                'amount' => $amount,
                'type' => $normal,
                'signed' => $signedBalance,
                'label' => self::formatBalanceDrCr($amount, $normal),
            ];
        }

        $amount = abs($signedBalance);

        return [
            'amount' => $amount,
            'type' => $opposite,
            'signed' => $signedBalance,
            'label' => self::formatBalanceDrCr($amount, $opposite),
        ];
    }

    /**
     * ERP display: "10,000.00 DR" / "25,500.00 CR" (never negative).
     */
    public static function formatBalanceDrCr(float $amount, string $balanceType): string
    {
        $side = strtoupper(trim($balanceType)) === 'CREDIT' ? 'CR' : 'DR';
        $formatted = number_format(abs($amount), 2, '.', ',');

        return $formatted . ' ' . $side;
    }

    /**
     * Ensure a ledger line is single-sided (only Debit OR only Credit has an amount).
     *
     * @return array{debit: float, credit: float}
     */
    public static function normalizeSingleSidedAmounts(float $debit, float $credit): array
    {
        $debit = round(abs($debit), 2);
        $credit = round(abs($credit), 2);
        if ($debit < 0.005 && $credit < 0.005) {
            return ['debit' => 0.0, 'credit' => 0.0];
        }
        if ($debit >= $credit) {
            return ['debit' => $debit, 'credit' => 0.0];
        }

        return ['debit' => 0.0, 'credit' => $credit];
    }

    /**
     * Format a debit/credit cell: blank when zero (ERP / Tally style).
     */
    public static function formatSideAmount(float $amount): string
    {
        if (abs($amount) < 0.005) {
            return '';
        }

        return number_format(abs($amount), 2, '.', ',');
    }

    /**
     * Resolve Chart of Accounts "Account Type" from stored value or group type.
     */
    public static function resolveAccountType(array $account): string
    {
        $stored = strtoupper(trim((string) ($account['account_type'] ?? '')));
        if ($stored !== '') {
            return $stored;
        }
        $groupType = strtoupper(trim((string) ($account['group_type'] ?? '')));

        return match ($groupType) {
            'ASSETS' => 'ASSET',
            'LIABILITIES' => 'LIABILITY',
            'CAPITAL' => 'CAPITAL',
            'INCOME' => 'INCOME',
            'EXPENSES' => 'EXPENSE',
            default => 'GENERAL',
        };
    }

    // -------------------------------------------------------------------------
    // Backward-compatible aliases (previous AccountingBalance method names)
    // -------------------------------------------------------------------------

    public static function openingBalance(
        float $debitBefore,
        float $creditBefore,
        float $masterOpeningsNet = 0.0
    ): float {
        return self::calculateOpeningBalance($debitBefore, $creditBefore, $masterOpeningsNet);
    }

    public static function closingBalance(
        float $openingBalance,
        float $totalDebit,
        float $totalCredit
    ): float {
        return self::calculateClosingBalance($openingBalance, $totalDebit, $totalCredit);
    }
}

/**
 * @deprecated Prefer AccountingBalanceService — kept as thin alias for existing call sites.
 */
class AccountingBalance extends AccountingBalanceService
{
}
