<?php
/**
 * Print Preview View for Accounting Reports
 * BUSY/Tally ERP Style Interface
 */
$reportType = $_GET['report_type'] ?? '';
$reportData = $_GET['report_data'] ?? [];
?>
<style>
.acc-print-preview {
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 20px;
    background-color: #FFF;
}

.acc-print-header {
    text-align: center;
    margin-bottom: 20px;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
}

.acc-print-title {
    font-size: 18px;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.acc-print-subtitle {
    font-size: 12px;
    color: #666;
}

.acc-print-toolbar {
    position: fixed;
    top: 10px;
    right: 10px;
    background: #FFF;
    border: 1px solid #999;
    padding: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    z-index: 1000;
}

.acc-print-btn {
    font-size: 11px;
    font-weight: bold;
    padding: 4px 12px;
    border: 1px solid #000;
    background: linear-gradient(180deg, #4A90E2 0%, #357ABD 100%);
    color: #FFF;
    cursor: pointer;
    font-family: 'Tahoma', 'Arial', sans-serif;
    text-transform: uppercase;
    margin-right: 5px;
}

.acc-print-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-print-btn-secondary {
    background: linear-gradient(180deg, #E0E0E0 0%, #C0C0C0 100%);
    color: #000;
    border-color: #999;
}

.acc-print-btn-secondary:hover {
    background: linear-gradient(180deg, #F0F0F0 0%, #D0D0D0 100%);
}

@media print {
    .acc-print-toolbar {
        display: none !important;
    }
    
    .acc-print-preview {
        padding: 0;
    }
}

.acc-print-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-bottom: 20px;
}

.acc-print-table thead {
    background-color: #4A4A4A;
    color: #FFF;
}

.acc-print-table th {
    padding: 6px 8px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-print-table td {
    padding: 6px 8px;
    border: 1px solid #999;
}

.acc-print-table .text-right {
    text-align: right;
}

.acc-print-table .text-center {
    text-align: center;
}

.acc-print-summary {
    background-color: #F0F0F0;
    border: 1px solid #999;
    padding: 10px;
    margin-top: 20px;
}

.acc-print-summary-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #DDD;
}

.acc-print-summary-item:last-child {
    border-bottom: none;
}

.acc-print-summary-item label {
    font-weight: bold;
}

.acc-print-summary-item span {
    font-family: 'Courier New', monospace;
    font-weight: bold;
}
</style>

<div class="acc-print-toolbar">
    <button type="button" class="acc-print-btn" onclick="window.print()">Print</button>
    <button type="button" class="acc-print-btn acc-print-btn-secondary" onclick="window.close()">Close</button>
</div>

<div class="acc-print-preview">
    <div class="acc-print-header">
        <div class="acc-print-title"><?php echo htmlspecialchars($reportType); ?></div>
        <div class="acc-print-subtitle">Generated on <?php echo date('Y-m-d H:i:s'); ?></div>
    </div>

    <div id="accPrintContent">
        <!-- Report content will be loaded here -->
        <div style="text-align: center; color: #999; padding: 50px;">
            Loading report content...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    accLoadReportContent();
});

function accLoadReportContent() {
    const reportType = '<?php echo htmlspecialchars($reportType); ?>';
    const reportData = <?php echo json_encode($reportData); ?>;
    
    // Load report content based on type
    switch(reportType) {
        case 'day_book':
            accLoadDayBook(reportData);
            break;
        case 'ledger':
            accLoadLedger(reportData);
            break;
        case 'trial_balance':
            accLoadTrialBalance(reportData);
            break;
        case 'profit_loss':
            accLoadProfitLoss(reportData);
            break;
        case 'balance_sheet':
            accLoadBalanceSheet(reportData);
            break;
        default:
            document.getElementById('accPrintContent').innerHTML = '<div style="text-align: center; color: #999;">Unknown report type</div>';
    }
}

function accLoadDayBook(data) {
    const entries = data.entries || [];
    let html = '<table class="acc-print-table"><thead><tr><th>Date</th><th>Voucher No</th><th>Type</th><th>Account</th><th>Reference</th><th>Narration</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th>Branch</th><th>Created By</th></tr></thead><tbody>';
    
    entries.forEach(entry => {
        html += `<tr>
            <td>${entry.entry_date || ''}</td>
            <td>${entry.voucher_number || ''}</td>
            <td>${entry.voucher_type || ''}</td>
            <td>${entry.account_name || ''}</td>
            <td>${entry.reference || ''}</td>
            <td>${entry.narration || ''}</td>
            <td class="text-right">${parseFloat(entry.debit_amount || 0).toFixed(2)}</td>
            <td class="text-right">${parseFloat(entry.credit_amount || 0).toFixed(2)}</td>
            <td>${entry.branch || ''}</td>
            <td>${entry.created_by || ''}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    html += `<div class="acc-print-summary"><div class="acc-print-summary-item"><label>Total Records:</label><span>${entries.length}</span></div></div>`;
    document.getElementById('accPrintContent').innerHTML = html;
}

function accLoadLedger(data) {
    const entries = data.entries || [];
    let html = `<div style="margin-bottom: 15px;"><strong>Opening Balance:</strong> ${data.opening_balance.toFixed(2)} ${data.opening_balance_type}</div>`;
    html += '<table class="acc-print-table"><thead><tr><th>Date</th><th>Voucher No</th><th>Type</th><th>Narration</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th></tr></thead><tbody>';
    
    entries.forEach(entry => {
        html += `<tr>
            <td>${entry.entry_date || entry.voucher_date || ''}</td>
            <td>${entry.voucher_number || ''}</td>
            <td>${entry.voucher_type || ''}</td>
            <td>${entry.narration || entry.voucher_narration || ''}</td>
            <td class="text-right">${parseFloat(entry.debit_amount || 0).toFixed(2)}</td>
            <td class="text-right">${parseFloat(entry.credit_amount || 0).toFixed(2)}</td>
            <td class="text-right">${parseFloat(entry.running_balance || 0).toFixed(2)} ${entry.balance_type || ''}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    html += `<div style="margin-top: 15px;"><strong>Closing Balance:</strong> ${data.closing_balance.toFixed(2)} ${data.closing_balance_type}</div>`;
    document.getElementById('accPrintContent').innerHTML = html;
}

function accLoadTrialBalance(data) {
    const accounts = data.accounts || [];
    let html = '<table class="acc-print-table"><thead><tr><th>Account Code</th><th>Account Name</th><th>Group</th><th>Group Type</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead><tbody>';
    
    accounts.forEach(acc => {
        html += `<tr>
            <td>${acc.account_code || ''}</td>
            <td>${acc.account_name || ''}</td>
            <td>${acc.group_name || ''}</td>
            <td>${acc.group_type || ''}</td>
            <td class="text-right">${parseFloat(acc.debit_amount || 0).toFixed(2)}</td>
            <td class="text-right">${parseFloat(acc.credit_amount || 0).toFixed(2)}</td>
        </tr>`;
    });
    
    html += '</tbody><tfoot><tr style="font-weight: bold; background-color: #E0E0E0;"><td colspan="4" class="text-right">Total:</td><td class="text-right">' + data.debit_total.toFixed(2) + '</td><td class="text-right">' + data.credit_total.toFixed(2) + '</td></tr></tfoot></table>';
    html += `<div class="acc-print-summary"><div class="acc-print-summary-item"><label>Difference:</label><span>${(data.debit_total - data.credit_total).toFixed(2)}</span></div></div>`;
    document.getElementById('accPrintContent').innerHTML = html;
}

function accLoadProfitLoss(data) {
    const incomeAccounts = data.income_accounts || [];
    const expenseAccounts = data.expense_accounts || [];
    
    let html = '<h3 style="background-color: #4A90E2; color: #FFF; padding: 8px; margin: 10px 0;">Income</h3>';
    html += '<table class="acc-print-table"><thead><tr><th>Account Code</th><th>Account Name</th><th>Group</th><th class="text-right">Amount</th></tr></thead><tbody>';
    
    incomeAccounts.forEach(acc => {
        html += `<tr>
            <td>${acc.account_code || ''}</td>
            <td>${acc.account_name || ''}</td>
            <td>${acc.group_name || ''}</td>
            <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
        </tr>`;
    });
    
    html += '</tbody><tfoot><tr style="font-weight: bold; background-color: #E0E0E0;"><td colspan="3" class="text-right">Total Income:</td><td class="text-right">' + data.total_income.toFixed(2) + '</td></tr></tfoot></table>';
    
    html += '<h3 style="background-color: #E74C3C; color: #FFF; padding: 8px; margin: 10px 0;">Expenses</h3>';
    html += '<table class="acc-print-table"><thead><tr><th>Account Code</th><th>Account Name</th><th>Group</th><th class="text-right">Amount</th></tr></thead><tbody>';
    
    expenseAccounts.forEach(acc => {
        html += `<tr>
            <td>${acc.account_code || ''}</td>
            <td>${acc.account_name || ''}</td>
            <td>${acc.group_name || ''}</td>
            <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
        </tr>`;
    });
    
    html += '</tbody><tfoot><tr style="font-weight: bold; background-color: #E0E0E0;"><td colspan="3" class="text-right">Total Expenses:</td><td class="text-right">' + data.total_expenses.toFixed(2) + '</td></tr></tfoot></table>';
    
    const netProfit = data.net_profit >= 0 ? 'Profit' : 'Loss';
    html += `<div class="acc-print-summary" style="background-color: #90EE90;"><div class="acc-print-summary-item"><label>Net ${netProfit}:</label><span>${Math.abs(data.net_profit).toFixed(2)}</span></div></div>`;
    
    document.getElementById('accPrintContent').innerHTML = html;
}

function accLoadBalanceSheet(data) {
    const assets = data.assets || [];
    const liabilities = data.liabilities || [];
    const capital = data.capital || [];
    
    let html = '<h3 style="background-color: #4A90E2; color: #FFF; padding: 8px; margin: 10px 0;">Assets</h3>';
    html += '<table class="acc-print-table"><thead><tr><th>Account Code</th><th>Account Name</th><th>Group</th><th class="text-right">Amount</th></tr></thead><tbody>';
    
    assets.forEach(acc => {
        html += `<tr>
            <td>${acc.account_code || ''}</td>
            <td>${acc.account_name || ''}</td>
            <td>${acc.group_name || ''}</td>
            <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
        </tr>`;
    });
    
    html += '</tbody><tfoot><tr style="font-weight: bold; background-color: #E0E0E0;"><td colspan="3" class="text-right">Total Assets:</td><td class="text-right">' + data.total_assets.toFixed(2) + '</td></tr></tfoot></table>';
    
    html += '<h3 style="background-color: #E74C3C; color: #FFF; padding: 8px; margin: 10px 0;">Liabilities</h3>';
    html += '<table class="acc-print-table"><thead><tr><th>Account Code</th><th>Account Name</th><th>Group</th><th class="text-right">Amount</th></tr></thead><tbody>';
    
    liabilities.forEach(acc => {
        html += `<tr>
            <td>${acc.account_code || ''}</td>
            <td>${acc.account_name || ''}</td>
            <td>${acc.group_name || ''}</td>
            <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
        </tr>`;
    });
    
    html += '</tbody><tfoot><tr style="font-weight: bold; background-color: #E0E0E0;"><td colspan="3" class="text-right">Total Liabilities:</td><td class="text-right">' + data.total_liabilities.toFixed(2) + '</td></tr></tfoot></table>';
    
    html += '<h3 style="background-color: #27AE60; color: #FFF; padding: 8px; margin: 10px 0;">Capital</h3>';
    html += '<table class="acc-print-table"><thead><tr><th>Account Code</th><th>Account Name</th><th>Group</th><th class="text-right">Amount</th></tr></thead><tbody>';
    
    capital.forEach(acc => {
        html += `<tr>
            <td>${acc.account_code || ''}</td>
            <td>${acc.account_name || ''}</td>
            <td>${acc.group_name || ''}</td>
            <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
        </tr>`;
    });
    
    html += '</tbody><tfoot><tr style="font-weight: bold; background-color: #E0E0E0;"><td colspan="3" class="text-right">Total Capital:</td><td class="text-right">' + data.total_capital.toFixed(2) + '</td></tr></tfoot></table>';
    
    const isBalanced = Math.abs(data.total_assets - (data.total_liabilities + data.total_capital)) < 0.01;
    html += `<div class="acc-print-summary" style="background-color: ${isBalanced ? '#90EE90' : '#FF6347'};"><div class="acc-print-summary-item"><label>Assets = Liabilities + Capital:</label><span>${isBalanced ? 'BALANCED' : 'NOT BALANCED'}</span></div></div>`;
    
    document.getElementById('accPrintContent').innerHTML = html;
}
</script>
