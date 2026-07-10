<?php
/**
 * Desktop Accounting Style Profit & Loss Statement
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-t');
?>
<style>
.acc-pl-module {
    background-color: #F5F5DC;
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

.acc-pl-header {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
    padding: 4px 8px;
    border-bottom: 2px solid #1A1A1A;
}

.acc-pl-header-title {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.acc-pl-toolbar {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
    align-items: center;
}

.acc-pl-form-group {
    display: flex;
    flex-direction: column;
}

.acc-pl-form-group label {
    font-size: 10px;
    font-weight: bold;
    color: #333;
    margin-bottom: 2px;
}

.acc-pl-form-group input {
    font-size: 11px;
    padding: 3px 5px;
    border: 1px solid #999;
    background-color: #FFF;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-pl-btn {
    font-size: 11px;
    font-weight: bold;
    padding: 4px 12px;
    border: 1px solid #000;
    background: linear-gradient(180deg, #4A90E2 0%, #357ABD 100%);
    color: #FFF;
    cursor: pointer;
    font-family: 'Tahoma', 'Arial', sans-serif;
    text-transform: uppercase;
}

.acc-pl-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-pl-section {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-pl-section-title {
    font-size: 12px;
    font-weight: bold;
    color: #333;
    margin-bottom: 6px;
    text-transform: uppercase;
    border-bottom: 1px solid #999;
    padding-bottom: 4px;
}

.acc-pl-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.acc-pl-table thead {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
}

.acc-pl-table th {
    padding: 4px 6px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-pl-table td {
    padding: 4px 6px;
    border: 1px solid #999;
}

.acc-pl-table .text-right {
    text-align: right;
}

.acc-pl-summary {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 20px;
    align-items: center;
    justify-content: flex-end;
}

.acc-pl-summary-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.acc-pl-summary-item label {
    font-size: 11px;
    font-weight: bold;
    color: #333;
}

.acc-pl-summary-item span {
    font-size: 12px;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    min-width: 120px;
    text-align: right;
}

.acc-pl-summary-item span.profit {
    color: #006600;
}

.acc-pl-summary-item span.loss {
    color: #CC0000;
}
</style>

<div class="acc-pl-module">
    <!-- Header -->
    <div class="acc-pl-header">
        <span class="acc-pl-header-title">
            <i class="bi bi-graph-up"></i> Profit & Loss Statement
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-pl-toolbar">
        <div class="acc-pl-form-group">
            <label>From Date</label>
            <input type="date" id="accFromDate" value="<?php echo htmlspecialchars($fromDate); ?>">
        </div>
        <div class="acc-pl-form-group">
            <label>To Date</label>
            <input type="date" id="accToDate" value="<?php echo htmlspecialchars($toDate); ?>">
        </div>
        <button type="button" class="acc-pl-btn" onclick="accLoadProfitLoss()">
            Load
        </button>
        <button type="button" class="acc-pl-btn" onclick="accExportExcel()">
            Export Excel
        </button>
        <button type="button" class="acc-pl-btn" onclick="accPrintProfitLoss()">
            Print
        </button>
    </div>

    <!-- Income Section -->
    <div class="acc-pl-section">
        <div class="acc-pl-section-title">Income</div>
        <table class="acc-pl-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Account Code</th>
                    <th style="width: 40%;">Account Name</th>
                    <th style="width: 20%;">Group</th>
                    <th class="text-right" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody id="accIncomeBody">
                <tr>
                    <td colspan="4" class="text-center" style="padding: 20px; color: #999;">
                        Click "Load" to view Profit & Loss
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Total Income:</strong></td>
                    <td class="text-right"><strong id="accTotalIncome">0.00</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Expenses Section -->
    <div class="acc-pl-section">
        <div class="acc-pl-section-title">Expenses</div>
        <table class="acc-pl-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Account Code</th>
                    <th style="width: 40%;">Account Name</th>
                    <th style="width: 20%;">Group</th>
                    <th class="text-right" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody id="accExpensesBody">
                <tr>
                    <td colspan="4" class="text-center" style="padding: 20px; color: #999;">
                        Click "Load" to view Profit & Loss
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Total Expenses:</strong></td>
                    <td class="text-right"><strong id="accTotalExpenses">0.00</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Summary -->
    <div class="acc-pl-summary">
        <div class="acc-pl-summary-item">
            <label>Net Profit/Loss:</label>
            <span id="accNetProfit">0.00</span>
        </div>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';

async function accLoadProfitLoss() {
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=profit_loss&from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate));
        const data = await response.json();
        
        if (data.ok && data.data) {
            accRenderProfitLoss(data.data);
        } else {
            alert('Error loading Profit & Loss: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error loading Profit & Loss: ' + error.message);
    }
}

function accRenderProfitLoss(profitLoss) {
    const incomeBody = document.getElementById('accIncomeBody');
    const expensesBody = document.getElementById('accExpensesBody');
    
    const incomeAccounts = profitLoss.income_accounts || [];
    const expenseAccounts = profitLoss.expense_accounts || [];

    if (incomeAccounts.length === 0) {
        incomeBody.innerHTML = '<tr><td colspan="4" class="text-center" style="padding: 20px; color: #999;">No income accounts found</td></tr>';
    } else {
        incomeBody.innerHTML = incomeAccounts.map(acc => `
            <tr>
                <td>${acc.account_code || ''}</td>
                <td>${acc.account_name || ''}</td>
                <td>${acc.group_name || ''}</td>
                <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
            </tr>
        `).join('');
    }

    if (expenseAccounts.length === 0) {
        expensesBody.innerHTML = '<tr><td colspan="4" class="text-center" style="padding: 20px; color: #999;">No expense accounts found</td></tr>';
    } else {
        expensesBody.innerHTML = expenseAccounts.map(acc => `
            <tr>
                <td>${acc.account_code || ''}</td>
                <td>${acc.account_name || ''}</td>
                <td>${acc.group_name || ''}</td>
                <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
            </tr>
        `).join('');
    }

    const totalIncome = parseFloat(profitLoss.total_income || 0);
    const totalExpenses = parseFloat(profitLoss.total_expenses || 0);
    const netProfit = parseFloat(profitLoss.net_profit || 0);

    document.getElementById('accTotalIncome').textContent = totalIncome.toFixed(2);
    document.getElementById('accTotalExpenses').textContent = totalExpenses.toFixed(2);

    const netProfitSpan = document.getElementById('accNetProfit');
    netProfitSpan.textContent = (netProfit >= 0 ? 'Profit: ' : 'Loss: ') + Math.abs(netProfit).toFixed(2);
    netProfitSpan.className = netProfit >= 0 ? 'profit' : 'loss';
}

function accExportExcel() {
    alert('Excel export functionality - to be implemented');
}

function accPrintProfitLoss() {
    window.print();
}
</script>
