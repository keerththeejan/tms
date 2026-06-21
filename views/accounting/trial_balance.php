<?php
/**
 * Desktop Accounting Style Trial Balance
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
$asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
?>
<style>
.acc-tb-module {
    background-color: #F5F5DC;
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

.acc-tb-header {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
    padding: 4px 8px;
    border-bottom: 2px solid #1A1A1A;
}

.acc-tb-header-title {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.acc-tb-toolbar {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
    align-items: center;
}

.acc-tb-form-group {
    display: flex;
    flex-direction: column;
}

.acc-tb-form-group label {
    font-size: 10px;
    font-weight: bold;
    color: #333;
    margin-bottom: 2px;
}

.acc-tb-form-group input {
    font-size: 11px;
    padding: 3px 5px;
    border: 1px solid #999;
    background-color: #FFF;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-tb-btn {
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

.acc-tb-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-tb-table-section {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-tb-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.acc-tb-table thead {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
}

.acc-tb-table th {
    padding: 4px 6px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-tb-table td {
    padding: 4px 6px;
    border: 1px solid #999;
}

.acc-tb-table .text-right {
    text-align: right;
}

.acc-tb-table .text-center {
    text-align: center;
}

.acc-tb-summary {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 20px;
    align-items: center;
    justify-content: flex-end;
}

.acc-tb-summary-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.acc-tb-summary-item label {
    font-size: 11px;
    font-weight: bold;
    color: #333;
}

.acc-tb-summary-item span {
    font-size: 12px;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    min-width: 120px;
    text-align: right;
}

.acc-tb-summary-item span.debit {
    color: #CC0000;
}

.acc-tb-summary-item span.credit {
    color: #006600;
}

.acc-tb-summary-item span.balanced {
    color: #006600;
}

.acc-tb-summary-item span.not-balanced {
    color: #CC0000;
}
</style>

<div class="acc-tb-module">
    <!-- Header -->
    <div class="acc-tb-header">
        <span class="acc-tb-header-title">
            <i class="bi bi-balance-scale"></i> Trial Balance
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-tb-toolbar">
        <div class="acc-tb-form-group">
            <label>As Of Date</label>
            <input type="date" id="accAsOfDate" value="<?php echo htmlspecialchars($asOfDate); ?>">
        </div>
        <button type="button" class="acc-tb-btn" onclick="accLoadTrialBalance()">
            Load
        </button>
        <button type="button" class="acc-tb-btn" onclick="accExportExcel()">
            Export Excel
        </button>
        <button type="button" class="acc-tb-btn" onclick="accPrintTrialBalance()">
            Print
        </button>
    </div>

    <!-- Table Section -->
    <div class="acc-tb-table-section">
        <table class="acc-tb-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Account Code</th>
                    <th style="width: 30%;">Account Name</th>
                    <th style="width: 20%;">Group</th>
                    <th style="width: 15%;">Group Type</th>
                    <th class="text-right" style="width: 15%;">Debit</th>
                    <th class="text-right" style="width: 15%;">Credit</th>
                </tr>
            </thead>
            <tbody id="accTrialBalanceBody">
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px; color: #999;">
                        Click "Load" to view Trial Balance
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="acc-tb-summary">
        <div class="acc-tb-summary-item">
            <label>Total Debit:</label>
            <span id="accTotalDebit" class="debit">0.00</span>
        </div>
        <div class="acc-tb-summary-item">
            <label>Total Credit:</label>
            <span id="accTotalCredit" class="credit">0.00</span>
        </div>
        <div class="acc-tb-summary-item">
            <label>Difference:</label>
            <span id="accDifference" class="balanced">0.00</span>
        </div>
        <div class="acc-tb-summary-item">
            <label>Status:</label>
            <span id="accBalanceStatus" class="balanced">BALANCED</span>
        </div>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';

async function accLoadTrialBalance() {
    const asOfDate = document.getElementById('accAsOfDate').value;

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=trial_balance&as_of_date=' + encodeURIComponent(asOfDate));
        const data = await response.json();
        
        if (data.ok && data.data) {
            accRenderTrialBalance(data.data);
        } else {
            alert('Error loading Trial Balance: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error loading Trial Balance: ' + error.message);
    }
}

function accRenderTrialBalance(trialBalance) {
    const tbody = document.getElementById('accTrialBalanceBody');
    const accounts = trialBalance.accounts || [];

    if (accounts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="padding: 20px; color: #999;">No accounts found</td></tr>';
        document.getElementById('accTotalDebit').textContent = '0.00';
        document.getElementById('accTotalCredit').textContent = '0.00';
        document.getElementById('accDifference').textContent = '0.00';
        document.getElementById('accBalanceStatus').textContent = 'BALANCED';
        return;
    }

    tbody.innerHTML = accounts.map(acc => `
        <tr>
            <td>${acc.account_code || ''}</td>
            <td>${acc.account_name || ''}</td>
            <td>${acc.group_name || ''}</td>
            <td>${acc.group_type || ''}</td>
            <td class="text-right">${parseFloat(acc.debit_amount || 0).toFixed(2)}</td>
            <td class="text-right">${parseFloat(acc.credit_amount || 0).toFixed(2)}</td>
        </tr>
    `).join('');

    const totalDebit = parseFloat(trialBalance.debit_total || 0);
    const totalCredit = parseFloat(trialBalance.credit_total || 0);
    const difference = totalDebit - totalCredit;
    const isBalanced = Math.abs(difference) < 0.01;

    document.getElementById('accTotalDebit').textContent = totalDebit.toFixed(2);
    document.getElementById('accTotalCredit').textContent = totalCredit.toFixed(2);
    document.getElementById('accDifference').textContent = difference.toFixed(2);

    const balanceStatus = document.getElementById('accBalanceStatus');
    const differenceSpan = document.getElementById('accDifference');

    if (isBalanced) {
        differenceSpan.className = 'balanced';
        balanceStatus.textContent = 'BALANCED';
        balanceStatus.className = 'balanced';
    } else {
        differenceSpan.className = 'not-balanced';
        balanceStatus.textContent = 'NOT BALANCED';
        balanceStatus.className = 'not-balanced';
    }
}

function accExportExcel() {
    alert('Excel export functionality - to be implemented');
}

function accPrintTrialBalance() {
    window.print();
}
</script>
