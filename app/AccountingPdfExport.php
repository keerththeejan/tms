<?php

declare(strict_types=1);

/**
 * Accounting PDF Export Service
 * Generates PDF exports for accounting reports
 */
class AccountingPdfExport
{
    private static function fmtMoney(float|int|string $amount): string
    {
        return Helpers::formatMoney($amount);
    }

    /**
     * Export Day Book to PDF
     */
    public static function exportDayBook(PDO $pdo, string $fromDate, string $toDate, ?string $voucherType = null): void
    {
        $entries = LedgerEntryRepository::getDayBook($pdo, $fromDate, $toDate, $voucherType);
        
        $html = self::generateDayBookHtml($entries, $fromDate, $toDate, $voucherType);
        self::outputPdf('Day_Book_' . $fromDate . '_to_' . $toDate . '.pdf', $html);
    }

    /**
     * Export Ledger to PDF
     */
    public static function exportLedger(PDO $pdo, int $accountId, ?string $fromDate = null, ?string $toDate = null): void
    {
        $ledger = AccountRepository::getLedger($pdo, $accountId, $fromDate, $toDate);
        $account = AccountRepository::getById($pdo, $accountId);
        
        $html = self::generateLedgerHtml($ledger, $account, $fromDate, $toDate);
        self::outputPdf('Ledger_' . ($account['account_code'] ?? 'Account') . '.pdf', $html);
    }

    /**
     * Export Trial Balance to PDF
     */
    public static function exportTrialBalance(PDO $pdo, string $asOfDate): void
    {
        $trialBalance = LedgerEntryRepository::getTrialBalance($pdo, $asOfDate);
        
        $html = self::generateTrialBalanceHtml($trialBalance, $asOfDate);
        self::outputPdf('Trial_Balance_' . $asOfDate . '.pdf', $html);
    }

    /**
     * Export Profit & Loss to PDF
     */
    public static function exportProfitLoss(PDO $pdo, string $fromDate, string $toDate): void
    {
        $profitLoss = LedgerEntryRepository::getProfitLoss($pdo, $fromDate, $toDate);
        
        $html = self::generateProfitLossHtml($profitLoss, $fromDate, $toDate);
        self::outputPdf('Profit_Loss_' . $fromDate . '_to_' . $toDate . '.pdf', $html);
    }

    /**
     * Export Balance Sheet to PDF
     */
    public static function exportBalanceSheet(PDO $pdo, string $asOfDate): void
    {
        $balanceSheet = LedgerEntryRepository::getBalanceSheet($pdo, $asOfDate);
        
        $html = self::generateBalanceSheetHtml($balanceSheet, $asOfDate);
        self::outputPdf('Balance_Sheet_' . $asOfDate . '.pdf', $html);
    }

    /**
     * Generate Day Book HTML
     */
    private static function generateDayBookHtml(array $entries, string $fromDate, string $toDate, ?string $voucherType): string
    {
        $rows = '';
        foreach ($entries as $entry) {
            $rows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td style="text-align: right;">%s</td>
                    <td style="text-align: right;">%s</td>
                    <td>%s</td>
                    <td>%s</td>
                </tr>',
                htmlspecialchars($entry['entry_date'] ?? ''),
                htmlspecialchars($entry['voucher_number'] ?? ''),
                htmlspecialchars($entry['voucher_type'] ?? ''),
                htmlspecialchars($entry['account_name'] ?? ''),
                htmlspecialchars($entry['reference'] ?? ''),
                htmlspecialchars($entry['narration'] ?? ''),
                self::fmtMoney($entry['debit_amount'] ?? 0),
                self::fmtMoney($entry['credit_amount'] ?? 0),
                htmlspecialchars($entry['branch'] ?? ''),
                htmlspecialchars($entry['created_by'] ?? '')
            );
        }

        $recordCount = count($entries);

        return self::getPdfTemplate('Day Book', $fromDate, $toDate, $voucherType, sprintf(
            '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 11px;">
                <thead>
                    <tr style="background-color: #4A4A4A; color: #FFF;">
                        <th>Date</th>
                        <th>Voucher No</th>
                        <th>Type</th>
                        <th>Account</th>
                        <th>Reference</th>
                        <th>Narration</th>
                        <th style="text-align: right;">Debit</th>
                        <th style="text-align: right;">Credit</th>
                        <th>Branch</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>%s</tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #E0E0E0;">
                        <td colspan="10">Showing %d Voucher Entr%s (Total Records: %d)</td>
                    </tr>
                </tfoot>
            </table>',
            $rows,
            $recordCount,
            $recordCount === 1 ? 'y' : 'ies',
            $recordCount
        ));
    }

    /**
     * Generate Ledger HTML
     */
    private static function generateLedgerHtml(array $ledger, ?array $account, ?string $fromDate, ?string $toDate): string
    {
        $rows = '';
        foreach ($ledger['entries'] ?? [] as $entry) {
            $rows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td style="text-align: right;">%s</td>
                    <td style="text-align: right;">%s</td>
                    <td style="text-align: right;">%s %s</td>
                </tr>',
                htmlspecialchars($entry['entry_date'] ?? $entry['voucher_date'] ?? ''),
                htmlspecialchars($entry['voucher_number'] ?? ''),
                htmlspecialchars($entry['voucher_type'] ?? ''),
                htmlspecialchars($entry['narration'] ?? $entry['voucher_narration'] ?? ''),
                self::fmtMoney($entry['debit_amount'] ?? 0),
                self::fmtMoney($entry['credit_amount'] ?? 0),
                self::fmtMoney($entry['running_balance'] ?? 0),
                htmlspecialchars($entry['balance_type'] ?? '')
            );
        }

        return self::getPdfTemplate(
            'Ledger - ' . ($account['account_name'] ?? 'Account'),
            $fromDate ?? '',
            $toDate ?? '',
            null,
            sprintf(
                '<div style="margin-bottom: 10px;">
                    <strong>Account:</strong> %s (%s)<br>
                    <strong>Opening Balance:</strong> %s %s
                </div>
                <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <thead>
                        <tr style="background-color: #4A4A4A; color: #FFF;">
                            <th>Date</th>
                            <th>Voucher No</th>
                            <th>Type</th>
                            <th>Narration</th>
                            <th style="text-align: right;">Debit</th>
                            <th style="text-align: right;">Credit</th>
                            <th style="text-align: right;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>%s</tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background-color: #E0E0E0;">
                            <td colspan="6" style="text-align: right;">Closing Balance:</td>
                            <td style="text-align: right;">%s %s</td>
                        </tr>
                    </tfoot>
                </table>',
                htmlspecialchars($account['account_name'] ?? ''),
                htmlspecialchars($account['account_code'] ?? ''),
                self::fmtMoney($ledger['opening_balance'] ?? 0),
                htmlspecialchars($ledger['opening_balance_type'] ?? ''),
                $rows,
                self::fmtMoney($ledger['closing_balance'] ?? 0),
                htmlspecialchars($ledger['closing_balance_type'] ?? '')
            )
        );
    }

    /**
     * Generate Trial Balance HTML
     */
    private static function generateTrialBalanceHtml(array $trialBalance, string $asOfDate): string
    {
        $rows = '';
        foreach ($trialBalance['accounts'] ?? [] as $acc) {
            $rows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td style="text-align: right;">%s</td>
                    <td style="text-align: right;">%s</td>
                </tr>',
                htmlspecialchars($acc['account_code'] ?? ''),
                htmlspecialchars($acc['account_name'] ?? ''),
                htmlspecialchars($acc['group_name'] ?? ''),
                htmlspecialchars($acc['group_type'] ?? ''),
                self::fmtMoney($acc['debit_amount'] ?? 0),
                self::fmtMoney($acc['credit_amount'] ?? 0)
            );
        }

        return self::getPdfTemplate('Trial Balance', $asOfDate, $asOfDate, null, sprintf(
            '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 11px;">
                <thead>
                    <tr style="background-color: #4A4A4A; color: #FFF;">
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Group</th>
                        <th>Group Type</th>
                        <th style="text-align: right;">Debit</th>
                        <th style="text-align: right;">Credit</th>
                    </tr>
                </thead>
                <tbody>%s</tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #E0E0E0;">
                        <td colspan="4" style="text-align: right;">Total:</td>
                        <td style="text-align: right;">%s</td>
                        <td style="text-align: right;">%s</td>
                    </tr>
                    <tr style="font-weight: bold; background-color: #FFFFE0;">
                        <td colspan="5" style="text-align: right;">Difference:</td>
                        <td style="text-align: right;">%s</td>
                    </tr>
                </tfoot>
            </table>',
            $rows,
            self::fmtMoney($trialBalance['debit_total'] ?? 0),
            self::fmtMoney($trialBalance['credit_total'] ?? 0),
            self::fmtMoney(($trialBalance['debit_total'] ?? 0) - ($trialBalance['credit_total'] ?? 0))
        ));
    }

    /**
     * Generate Profit & Loss HTML
     */
    private static function generateProfitLossHtml(array $profitLoss, string $fromDate, string $toDate): string
    {
        $incomeRows = '';
        foreach ($profitLoss['income_accounts'] ?? [] as $acc) {
            $incomeRows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td style="text-align: right;">%s</td>
                </tr>',
                htmlspecialchars($acc['account_code'] ?? ''),
                htmlspecialchars($acc['account_name'] ?? ''),
                htmlspecialchars($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }

        $expenseRows = '';
        foreach ($profitLoss['expense_accounts'] ?? [] as $acc) {
            $expenseRows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td style="text-align: right;">%s</td>
                </tr>',
                htmlspecialchars($acc['account_code'] ?? ''),
                htmlspecialchars($acc['account_name'] ?? ''),
                htmlspecialchars($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }

        return self::getPdfTemplate('Profit & Loss Statement', $fromDate, $toDate, null, sprintf(
            '<h3 style="background-color: #4A90E2; color: #FFF; padding: 8px; margin: 10px 0;">Income</h3>
            <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #4A4A4A; color: #FFF;">
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Group</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>%s</tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #E0E0E0;">
                        <td colspan="3" style="text-align: right;">Total Income:</td>
                        <td style="text-align: right;">%s</td>
                    </tr>
                </tfoot>
            </table>
            <h3 style="background-color: #E74C3C; color: #FFF; padding: 8px; margin: 10px 0;">Expenses</h3>
            <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #4A4A4A; color: #FFF;">
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Group</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>%s</tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #E0E0E0;">
                        <td colspan="3" style="text-align: right;">Total Expenses:</td>
                        <td style="text-align: right;">%s</td>
                    </tr>
                </tfoot>
            </table>
            <div style="background-color: #90EE90; padding: 10px; font-weight: bold; font-size: 14px; text-align: right;">
                Net %s: %s
            </div>',
            $incomeRows,
            self::fmtMoney($profitLoss['total_income'] ?? 0),
            $expenseRows,
            self::fmtMoney($profitLoss['total_expenses'] ?? 0),
            ((float) ($profitLoss['net_profit'] ?? 0) >= 0) ? 'Profit' : 'Loss',
            self::fmtMoney(abs((float) ($profitLoss['net_profit'] ?? 0)))
        ));
    }

    /**
     * Generate Balance Sheet HTML
     */
    private static function generateBalanceSheetHtml(array $balanceSheet, string $asOfDate): string
    {
        $assetRows = '';
        foreach ($balanceSheet['assets'] ?? [] as $acc) {
            $assetRows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td style="text-align: right;">%s</td>
                </tr>',
                htmlspecialchars($acc['account_code'] ?? ''),
                htmlspecialchars($acc['account_name'] ?? ''),
                htmlspecialchars($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }

        $liabilityRows = '';
        foreach ($balanceSheet['liabilities'] ?? [] as $acc) {
            $liabilityRows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td style="text-align: right;">%s</td>
                </tr>',
                htmlspecialchars($acc['account_code'] ?? ''),
                htmlspecialchars($acc['account_name'] ?? ''),
                htmlspecialchars($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }

        $capitalRows = '';
        foreach ($balanceSheet['capital'] ?? [] as $acc) {
            $capitalRows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td style="text-align: right;">%s</td>
                </tr>',
                htmlspecialchars($acc['account_code'] ?? ''),
                htmlspecialchars($acc['account_name'] ?? ''),
                htmlspecialchars($acc['group_name'] ?? ''),
                self::fmtMoney($acc['amount'] ?? 0)
            );
        }

        return self::getPdfTemplate('Balance Sheet', $asOfDate, $asOfDate, null, sprintf(
            '<h3 style="background-color: #4A90E2; color: #FFF; padding: 8px; margin: 10px 0;">Assets</h3>
            <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #4A4A4A; color: #FFF;">
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Group</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>%s</tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #E0E0E0;">
                        <td colspan="3" style="text-align: right;">Total Assets:</td>
                        <td style="text-align: right;">%s</td>
                    </tr>
                </tfoot>
            </table>
            <h3 style="background-color: #E74C3C; color: #FFF; padding: 8px; margin: 10px 0;">Liabilities</h3>
            <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #4A4A4A; color: #FFF;">
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Group</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>%s</tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #E0E0E0;">
                        <td colspan="3" style="text-align: right;">Total Liabilities:</td>
                        <td style="text-align: right;">%s</td>
                    </tr>
                </tfoot>
            </table>
            <h3 style="background-color: #27AE60; color: #FFF; padding: 8px; margin: 10px 0;">Capital</h3>
            <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #4A4A4A; color: #FFF;">
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Group</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>%s</tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #E0E0E0;">
                        <td colspan="3" style="text-align: right;">Total Capital:</td>
                        <td style="text-align: right;">%s</td>
                    </tr>
                </tfoot>
            </table>
            <div style="background-color: #90EE90; padding: 10px; font-weight: bold; font-size: 14px; text-align: right;">
                Assets = Liabilities + Capital: %s
            </div>',
            $assetRows,
            self::fmtMoney($balanceSheet['total_assets'] ?? 0),
            $liabilityRows,
            self::fmtMoney($balanceSheet['total_liabilities'] ?? 0),
            $capitalRows,
            self::fmtMoney($balanceSheet['total_capital'] ?? 0),
            (abs((float) ($balanceSheet['total_assets'] ?? 0) - ((float) ($balanceSheet['total_liabilities'] ?? 0) + (float) ($balanceSheet['total_capital'] ?? 0))) < 0.01) ? 'BALANCED' : 'NOT BALANCED'
        ));
    }

    /**
     * Get PDF template
     */
    private static function getPdfTemplate(string $title, string $fromDate, string $toDate, ?string $filter, string $content): string
    {
        $filterText = $filter ? " (Filter: {$filter})" : '';
        
        return sprintf(
            '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>%s</title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
                    h1 { color: #333; border-bottom: 2px solid #4A4A4A; padding-bottom: 10px; }
                    .header { margin-bottom: 20px; }
                    .date-range { color: #666; margin-bottom: 10px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>%s%s</h1>
                    <div class="date-range">
                        %s
                    </div>
                </div>
                %s
            </body>
            </html>',
            htmlspecialchars($title),
            htmlspecialchars($title),
            htmlspecialchars($filterText),
            $fromDate && $toDate ? "Period: {$fromDate} to {$toDate}" : "As of: {$fromDate}",
            $content
        );
    }

    /**
     * Output PDF
     */
    private static function outputPdf(string $filename, string $html): void
    {
        // For now, output as HTML (can be enhanced with TCPDF or DomPDF library)
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $html;
        exit;
    }
}
