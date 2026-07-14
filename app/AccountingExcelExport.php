<?php

declare(strict_types=1);

/**
 * Accounting Excel Export Service
 * Generates Excel exports for accounting reports
 */
class AccountingExcelExport
{
    private static function fmtMoney(float|int|string $amount): string
    {
        return Helpers::formatMoney($amount);
    }

    /**
     * Export Day Book to Excel
     */
    public static function exportDayBook(PDO $pdo, string $fromDate, string $toDate, ?string $voucherType = null): void
    {
        $entries = LedgerEntryRepository::getDayBook($pdo, $fromDate, $toDate, $voucherType);
        $summary = LedgerEntryRepository::getDayBookSummary($pdo, $fromDate, $toDate, $voucherType);

        $csv = self::generateDayBookCsv($entries, $summary, $fromDate, $toDate, $voucherType);
        self::outputCsv('Day_Book_' . $fromDate . '_to_' . $toDate . '.csv', $csv);
    }

    /**
     * Export Ledger to Excel/CSV
     */
    public static function exportLedger(
        PDO $pdo,
        int $accountId,
        ?string $fromDate = null,
        ?string $toDate = null,
        array $filters = []
    ): void {
        $ledger = AccountRepository::getLedger($pdo, $accountId, $fromDate, $toDate, $filters);
        $account = AccountRepository::getById($pdo, $accountId);

        $csv = self::generateLedgerCsv($ledger, $account, $fromDate, $toDate);
        self::outputCsv('Ledger_' . ($account['account_code'] ?? 'Account') . '.csv', $csv);
    }

    /**
     * Export Trial Balance to Excel
     */
    public static function exportTrialBalance(PDO $pdo, string $asOfDate): void
    {
        $trialBalance = LedgerEntryRepository::getTrialBalance($pdo, $asOfDate);
        
        $csv = self::generateTrialBalanceCsv($trialBalance, $asOfDate);
        self::outputCsv('Trial_Balance_' . $asOfDate . '.csv', $csv);
    }

    /**
     * Export Profit & Loss to Excel
     */
    public static function exportProfitLoss(PDO $pdo, string $fromDate, string $toDate): void
    {
        $profitLoss = LedgerEntryRepository::getProfitLoss($pdo, $fromDate, $toDate);
        
        $csv = self::generateProfitLossCsv($profitLoss, $fromDate, $toDate);
        self::outputCsv('Profit_Loss_' . $fromDate . '_to_' . $toDate . '.csv', $csv);
    }

    /**
     * Export Balance Sheet to Excel
     */
    public static function exportBalanceSheet(PDO $pdo, string $asOfDate): void
    {
        $balanceSheet = LedgerEntryRepository::getBalanceSheet($pdo, $asOfDate);
        
        $csv = self::generateBalanceSheetCsv($balanceSheet, $asOfDate);
        self::outputCsv('Balance_Sheet_' . $asOfDate . '.csv', $csv);
    }

    /**
     * Generate Day Book CSV (uses same AccountingBalance summary as the Day Book page)
     */
    private static function generateDayBookCsv(
        array $entries,
        array $summary,
        string $fromDate,
        string $toDate,
        ?string $voucherType
    ): string {
        $lines = [];

        // Header
        $lines[] = 'Day Book';
        $lines[] = 'Period: ' . $fromDate . ' to ' . $toDate;
        if ($voucherType) {
            $lines[] = 'Filter: ' . $voucherType;
        }
        $lines[] = '';

        // Summary — Closing = Opening + Credit − Debit (Credit = Cash In)
        $lines[] = 'Opening Balance,' . self::fmtMoney($summary['opening_balance'] ?? 0);
        $lines[] = 'Total Debit,' . self::fmtMoney($summary['total_debit'] ?? 0);
        $lines[] = 'Total Credit,' . self::fmtMoney($summary['total_credit'] ?? 0);
        $lines[] = 'Closing Balance,' . self::fmtMoney($summary['closing_balance'] ?? 0);
        $lines[] = 'Total Records,' . (int) ($summary['total_records'] ?? count($entries));
        $lines[] = '';

        // Column headers
        $lines[] = 'Date,Voucher No,Type,Account,Reference,Narration,Debit,Credit,Branch,Created By';

        // Data rows
        foreach ($entries as $entry) {
            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s,%s,%s,%s,%s',
                self::escapeCsv($entry['entry_date'] ?? ''),
                self::escapeCsv($entry['voucher_number'] ?? ''),
                self::escapeCsv($entry['voucher_type'] ?? ''),
                self::escapeCsv($entry['account_name'] ?? ''),
                self::escapeCsv($entry['reference'] ?? ''),
                self::escapeCsv($entry['narration'] ?? ''),
                self::fmtMoney($entry['debit_amount'] ?? 0),
                self::fmtMoney($entry['credit_amount'] ?? 0),
                self::escapeCsv($entry['branch'] ?? ''),
                self::escapeCsv($entry['created_by'] ?? '')
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Generate Ledger CSV
     */
    private static function generateLedgerCsv(array $ledger, ?array $account, ?string $fromDate, ?string $toDate): string
    {
        $company = Helpers::company();
        $companyName = (string) ($company['name'] ?? 'TS Transport');
        $printedBy = '';
        try {
            $u = Auth::user();
            $printedBy = (string) ($u['full_name'] ?? $u['username'] ?? '');
        } catch (Throwable $e) {
            $printedBy = '';
        }

        $lines = [];
        $lines[] = $companyName;
        $lines[] = 'General Ledger Report';
        $lines[] = 'Account Name: ' . ($account['account_name'] ?? '');
        $lines[] = 'Account Code: ' . ($account['account_code'] ?? '');
        $lines[] = 'Account Type: ' . ($ledger['account_type'] ?? $account['account_type'] ?? '');
        $lines[] = 'Normal Balance: ' . ($ledger['normal_balance'] ?? '');
        if ($fromDate && $toDate) {
            $lines[] = 'Date Range: ' . $fromDate . ' to ' . $toDate;
        }
        $lines[] = 'Printed By: ' . ($printedBy !== '' ? $printedBy : '—');
        $lines[] = 'Printed Date: ' . date('Y-m-d H:i');
        $lines[] = '';
        $lines[] = 'Date,Voucher No,Voucher Type,Narration,Debit,Credit,Running Balance';

        foreach ($ledger['entries'] ?? [] as $entry) {
            $debit = AccountingBalanceService::formatSideAmount((float) ($entry['debit_amount'] ?? 0));
            $credit = AccountingBalanceService::formatSideAmount((float) ($entry['credit_amount'] ?? 0));
            $running = (string) ($entry['running_balance_display']
                ?? AccountingBalanceService::formatBalanceDrCr(
                    (float) ($entry['running_balance'] ?? 0),
                    (string) ($entry['balance_type'] ?? 'DEBIT')
                ));
            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s,%s',
                self::escapeCsv($entry['entry_date'] ?? $entry['voucher_date'] ?? ''),
                self::escapeCsv($entry['voucher_number'] ?? ''),
                self::escapeCsv($entry['voucher_type'] ?? ''),
                self::escapeCsv($entry['narration'] ?? $entry['voucher_narration'] ?? ''),
                self::escapeCsv($debit),
                self::escapeCsv($credit),
                self::escapeCsv($running)
            );
        }

        $openingLabel = (string) ($ledger['opening_balance_display']
            ?? AccountingBalanceService::formatBalanceDrCr(
                (float) ($ledger['opening_balance'] ?? 0),
                (string) ($ledger['opening_balance_type'] ?? 'DEBIT')
            ));
        $closingLabel = (string) ($ledger['closing_balance_display']
            ?? AccountingBalanceService::formatBalanceDrCr(
                (float) ($ledger['closing_balance'] ?? 0),
                (string) ($ledger['closing_balance_type'] ?? 'DEBIT')
            ));

        $lines[] = '';
        $lines[] = 'Opening Balance,' . self::escapeCsv($openingLabel);
        $lines[] = 'Total Debit,' . number_format((float) ($ledger['total_debit'] ?? 0), 2, '.', ',');
        $lines[] = 'Total Credit,' . number_format((float) ($ledger['total_credit'] ?? 0), 2, '.', ',');
        $lines[] = 'Closing Balance,' . self::escapeCsv($closingLabel);
        $lines[] = 'Total Transactions,' . (int) ($ledger['total_transactions'] ?? count($ledger['entries'] ?? []));

        return implode("\n", $lines);
    }

    /**
     * Generate Trial Balance CSV
     */
    private static function generateTrialBalanceCsv(array $trialBalance, string $asOfDate): string
    {
        $lines = [];
        
        // Header
        $lines[] = 'Trial Balance';
        $lines[] = 'As of: ' . $asOfDate;
        $lines[] = '';
        
        // Column headers
        $lines[] = 'Account Code,Account Name,Group,Group Type,Debit,Credit';
        
        // Data rows
        foreach ($trialBalance['accounts'] ?? [] as $acc) {
            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s',
                self::escapeCsv($acc['account_code'] ?? ''),
                self::escapeCsv($acc['account_name'] ?? ''),
                self::escapeCsv($acc['group_name'] ?? ''),
                self::escapeCsv($acc['group_type'] ?? ''),
                self::fmtMoney($acc['debit_amount'] ?? 0),
                self::fmtMoney($acc['credit_amount'] ?? 0)
            );
        }
        
        // Totals
        $lines[] = '';
        $lines[] = 'Total,,' . self::fmtMoney($trialBalance['debit_total'] ?? 0) . ',' . self::fmtMoney($trialBalance['credit_total'] ?? 0);
        $lines[] = 'Difference,,' . self::fmtMoney(($trialBalance['debit_total'] ?? 0) - ($trialBalance['credit_total'] ?? 0));
        
        return implode("\n", $lines);
    }

    /**
     * Generate Profit & Loss CSV
     */
    private static function generateProfitLossCsv(array $profitLoss, string $fromDate, string $toDate): string
    {
        $lines = [];
        
        // Header
        $lines[] = 'Profit & Loss Statement';
        $lines[] = 'Period: ' . $fromDate . ' to ' . $toDate;
        $lines[] = '';
        
        // Income section
        $lines[] = 'INCOME';
        $lines[] = 'Account Code,Account Name,Group,Amount';
        
        foreach ($profitLoss['income_accounts'] ?? [] as $acc) {
            $lines[] = sprintf(
                '%s,%s,%s,%s',
                self::escapeCsv($acc['account_code'] ?? ''),
                self::escapeCsv($acc['account_name'] ?? ''),
                self::escapeCsv($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }
        
        $lines[] = 'Total Income,,,' . self::fmtMoney($profitLoss['total_income'] ?? 0);
        $lines[] = '';
        
        // Expenses section
        $lines[] = 'EXPENSES';
        $lines[] = 'Account Code,Account Name,Group,Amount';
        
        foreach ($profitLoss['expense_accounts'] ?? [] as $acc) {
            $lines[] = sprintf(
                '%s,%s,%s,%s',
                self::escapeCsv($acc['account_code'] ?? ''),
                self::escapeCsv($acc['account_name'] ?? ''),
                self::escapeCsv($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }
        
        $lines[] = 'Total Expenses,,,' . self::fmtMoney($profitLoss['total_expenses'] ?? 0);
        $lines[] = '';
        
        // Net profit/loss
        $netProfit = (float) ($profitLoss['net_profit'] ?? 0);
        $lines[] = 'Net ' . ($netProfit >= 0 ? 'Profit' : 'Loss') . ',,,' . self::fmtMoney(abs($netProfit));
        
        return implode("\n", $lines);
    }

    /**
     * Generate Balance Sheet CSV
     */
    private static function generateBalanceSheetCsv(array $balanceSheet, string $asOfDate): string
    {
        $lines = [];
        
        // Header
        $lines[] = 'Balance Sheet';
        $lines[] = 'As of: ' . $asOfDate;
        $lines[] = '';
        
        // Assets section
        $lines[] = 'ASSETS';
        $lines[] = 'Account Code,Account Name,Group,Amount';
        
        foreach ($balanceSheet['assets'] ?? [] as $acc) {
            $lines[] = sprintf(
                '%s,%s,%s,%s',
                self::escapeCsv($acc['account_code'] ?? ''),
                self::escapeCsv($acc['account_name'] ?? ''),
                self::escapeCsv($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }
        
        $lines[] = 'Total Assets,,,' . self::fmtMoney($balanceSheet['total_assets'] ?? 0);
        $lines[] = '';
        
        // Liabilities section
        $lines[] = 'LIABILITIES';
        $lines[] = 'Account Code,Account Name,Group,Amount';
        
        foreach ($balanceSheet['liabilities'] ?? [] as $acc) {
            $lines[] = sprintf(
                '%s,%s,%s,%s',
                self::escapeCsv($acc['account_code'] ?? ''),
                self::escapeCsv($acc['account_name'] ?? ''),
                self::escapeCsv($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }
        
        $lines[] = 'Total Liabilities,,,' . self::fmtMoney($balanceSheet['total_liabilities'] ?? 0);
        $lines[] = '';
        
        // Capital section
        $lines[] = 'CAPITAL';
        $lines[] = 'Account Code,Account Name,Group,Amount';
        
        foreach ($balanceSheet['capital'] ?? [] as $acc) {
            $lines[] = sprintf(
                '%s,%s,%s,%s',
                self::escapeCsv($acc['account_code'] ?? ''),
                self::escapeCsv($acc['account_name'] ?? ''),
                self::escapeCsv($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }
        
        $lines[] = 'Total Capital,,,' . self::fmtMoney($balanceSheet['total_capital'] ?? 0);
        $lines[] = '';
        
        // Balance check
        $totalAssets = (float) ($balanceSheet['total_assets'] ?? 0);
        $totalLiabilitiesCapital = (float) ($balanceSheet['total_liabilities'] ?? 0) + (float) ($balanceSheet['total_capital'] ?? 0);
        $isBalanced = abs($totalAssets - $totalLiabilitiesCapital) < 0.01;
        
        $lines[] = 'Balance Check:,' . ($isBalanced ? 'BALANCED' : 'NOT BALANCED');
        $lines[] = 'Difference:,' . self::fmtMoney($totalAssets - $totalLiabilitiesCapital);
        
        return implode("\n", $lines);
    }

    /**
     * Escape CSV value
     */
    private static function escapeCsv(string $value): string
    {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    /**
     * Output CSV
     */
    private static function outputCsv(string $filename, string $csv): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo $csv;
        exit;
    }
}
