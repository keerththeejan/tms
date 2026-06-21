<?php

declare(strict_types=1);

/**
 * Accounting Excel Export Service
 * Generates Excel exports for accounting reports
 */
class AccountingExcelExport
{
    /**
     * Export Day Book to Excel
     */
    public static function exportDayBook(PDO $pdo, string $fromDate, string $toDate, ?string $voucherType = null): void
    {
        $entries = LedgerEntryRepository::getDayBook($pdo, $fromDate, $toDate, $voucherType);
        
        $csv = self::generateDayBookCsv($entries, $fromDate, $toDate, $voucherType);
        self::outputCsv('Day_Book_' . $fromDate . '_to_' . $toDate . '.csv', $csv);
    }

    /**
     * Export Ledger to Excel
     */
    public static function exportLedger(PDO $pdo, int $accountId, ?string $fromDate = null, ?string $toDate = null): void
    {
        $ledger = AccountRepository::getLedger($pdo, $accountId, $fromDate, $toDate);
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
     * Generate Day Book CSV
     */
    private static function generateDayBookCsv(array $entries, string $fromDate, string $toDate, ?string $voucherType): string
    {
        $lines = [];
        
        // Header
        $lines[] = 'Day Book';
        $lines[] = 'Period: ' . $fromDate . ' to ' . $toDate;
        if ($voucherType) {
            $lines[] = 'Filter: ' . $voucherType;
        }
        $lines[] = '';
        
        // Column headers
        $lines[] = 'Date,Voucher No,Type,Account,Narration,Debit,Credit';
        
        // Data rows
        foreach ($entries as $entry) {
            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s,%s',
                self::escapeCsv($entry['entry_date'] ?? ''),
                self::escapeCsv($entry['voucher_number'] ?? ''),
                self::escapeCsv($entry['voucher_type'] ?? ''),
                self::escapeCsv($entry['account_name'] ?? ''),
                self::escapeCsv($entry['narration'] ?? ''),
                number_format((float) ($entry['debit_amount'] ?? 0), 2),
                number_format((float) ($entry['credit_amount'] ?? 0), 2)
            );
        }
        
        // Totals
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($entries as $entry) {
            $totalDebit += (float) ($entry['debit_amount'] ?? 0);
            $totalCredit += (float) ($entry['credit_amount'] ?? 0);
        }
        
        $lines[] = '';
        $lines[] = 'Total,' . number_format($totalDebit, 2) . ',' . number_format($totalCredit, 2);
        
        return implode("\n", $lines);
    }

    /**
     * Generate Ledger CSV
     */
    private static function generateLedgerCsv(array $ledger, ?array $account, ?string $fromDate, ?string $toDate): string
    {
        $lines = [];
        
        // Header
        $lines[] = 'Ledger - ' . ($account['account_name'] ?? 'Account');
        $lines[] = 'Account Code: ' . ($account['account_code'] ?? '');
        $lines[] = 'Opening Balance: ' . number_format((float) ($ledger['opening_balance'] ?? 0), 2) . ' ' . ($ledger['opening_balance_type'] ?? '');
        if ($fromDate && $toDate) {
            $lines[] = 'Period: ' . $fromDate . ' to ' . $toDate;
        }
        $lines[] = '';
        
        // Column headers
        $lines[] = 'Date,Voucher No,Type,Narration,Debit,Credit,Balance';
        
        // Data rows
        foreach ($ledger['entries'] ?? [] as $entry) {
            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s,%s %s',
                self::escapeCsv($entry['entry_date'] ?? $entry['voucher_date'] ?? ''),
                self::escapeCsv($entry['voucher_number'] ?? ''),
                self::escapeCsv($entry['voucher_type'] ?? ''),
                self::escapeCsv($entry['narration'] ?? $entry['voucher_narration'] ?? ''),
                number_format((float) ($entry['debit_amount'] ?? 0), 2),
                number_format((float) ($entry['credit_amount'] ?? 0), 2),
                number_format((float) ($entry['running_balance'] ?? 0), 2),
                self::escapeCsv($entry['balance_type'] ?? '')
            );
        }
        
        // Closing balance
        $lines[] = '';
        $lines[] = 'Closing Balance:,' . number_format((float) ($ledger['closing_balance'] ?? 0), 2) . ' ' . ($ledger['closing_balance_type'] ?? '');
        
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
                number_format((float) ($acc['debit_amount'] ?? 0), 2),
                number_format((float) ($acc['credit_amount'] ?? 0), 2)
            );
        }
        
        // Totals
        $lines[] = '';
        $lines[] = 'Total,,' . number_format((float) ($trialBalance['debit_total'] ?? 0), 2) . ',' . number_format((float) ($trialBalance['credit_total'] ?? 0), 2);
        $lines[] = 'Difference,,' . number_format((float) (($trialBalance['debit_total'] ?? 0) - ($trialBalance['credit_total'] ?? 0)), 2);
        
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
                number_format((float) ($acc['amount'] ?? 0), 2)
            );
        }
        
        $lines[] = 'Total Income,,,' . number_format((float) ($profitLoss['total_income'] ?? 0), 2);
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
                number_format((float) ($acc['amount'] ?? 0), 2)
            );
        }
        
        $lines[] = 'Total Expenses,,,' . number_format((float) ($profitLoss['total_expenses'] ?? 0), 2);
        $lines[] = '';
        
        // Net profit/loss
        $netProfit = (float) ($profitLoss['net_profit'] ?? 0);
        $lines[] = 'Net ' . ($netProfit >= 0 ? 'Profit' : 'Loss') . ',,,' . number_format(abs($netProfit), 2);
        
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
                number_format((float) ($acc['amount'] ?? 0), 2)
            );
        }
        
        $lines[] = 'Total Assets,,,' . number_format((float) ($balanceSheet['total_assets'] ?? 0), 2);
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
                number_format((float) ($acc['amount'] ?? 0), 2)
            );
        }
        
        $lines[] = 'Total Liabilities,,,' . number_format((float) ($balanceSheet['total_liabilities'] ?? 0), 2);
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
                number_format((float) ($acc['amount'] ?? 0), 2)
            );
        }
        
        $lines[] = 'Total Capital,,,' . number_format((float) ($balanceSheet['total_capital'] ?? 0), 2);
        $lines[] = '';
        
        // Balance check
        $totalAssets = (float) ($balanceSheet['total_assets'] ?? 0);
        $totalLiabilitiesCapital = (float) ($balanceSheet['total_liabilities'] ?? 0) + (float) ($balanceSheet['total_capital'] ?? 0);
        $isBalanced = abs($totalAssets - $totalLiabilitiesCapital) < 0.01;
        
        $lines[] = 'Balance Check:,' . ($isBalanced ? 'BALANCED' : 'NOT BALANCED');
        $lines[] = 'Difference:,' . number_format($totalAssets - $totalLiabilitiesCapital, 2);
        
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
