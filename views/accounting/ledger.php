<?php
/**
 * Desktop Accounting Style Ledger Report
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
$accountId = (int) ($_GET['account_id'] ?? 0);
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-t');
?>
<style>
.acc-ledger-module {
    background-color: #F5F5DC;
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

.acc-ledger-header {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
    padding: 4px 8px;
    border-bottom: 2px solid #1A1A1A;
}

.acc-ledger-header-title {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.acc-ledger-toolbar {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
    align-items: center;
}

.acc-ledger-form-group {
    display: flex;
    flex-direction: column;
}

.acc-ledger-form-group label {
    font-size: 10px;
    font-weight: bold;
    color: #333;
    margin-bottom: 2px;
}

.acc-ledger-form-group input,
.acc-ledger-form-group select {
    font-size: 11px;
    padding: 3px 5px;
    border: 1px solid #999;
    background-color: #FFF;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-ledger-btn {
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

.acc-ledger-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-ledger-table-section {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-ledger-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.acc-ledger-table thead {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
}

.acc-ledger-table th {
    padding: 4px 6px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-ledger-table td {
    padding: 4px 6px;
    border: 1px solid #999;
}

.acc-ledger-table .text-right {
    text-align: right;
}

.acc-ledger-table .text-center {
    text-align: center;
}

.acc-ledger-summary {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-ledger-summary-item {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    border-bottom: 1px solid #EEE;
}

.acc-ledger-summary-item:last-child {
    border-bottom: none;
}

.acc-ledger-summary-item label {
    font-size: 11px;
    font-weight: bold;
    color: #333;
}

.acc-ledger-summary-item span {
    font-size: 12px;
    font-weight: bold;
    font-family: 'Courier New', monospace;
}

.acc-ledger-summary-item span.debit {
    color: #CC0000;
}

.acc-ledger-summary-item span.credit {
    color: #006600;
}
</style>

<div class="acc-ledger-module">
    <!-- Header -->
    <div class="acc-ledger-header">
        <span class="acc-ledger-header-title">
            <i class="bi bi-journal-text"></i> Ledger Report
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-ledger-toolbar">
        <div class="acc-ledger-form-group">
            <label>Account</label>
            <select id="accAccountId">
                <option value="">Select Account</option>
            </select>
        </div>
        <div class="acc-ledger-form-group">
            <label>From Date</label>
            <input type="date" id="accFromDate" value="<?php echo htmlspecialchars($fromDate); ?>">
        </div>
        <div class="acc-ledger-form-group">
            <label>To Date</label>
            <input type="date" id="accToDate" value="<?php echo htmlspecialchars($toDate); ?>">
        </div>
        <button type="button" class="acc-ledger-btn" onclick="accLoadLedger()">
            Load
        </button>
        <button type="button" class="acc-ledger-btn" onclick="accExportExcel()">
            Export Excel
        </button>
        <button type="button" class="acc-ledger-btn" onclick="accPrintLedger()">
            Print
        </button>
    </div>

    <!-- Table Section -->
    <div class="acc-ledger-table-section">
        <table class="acc-ledger-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Date</th>
                    <th style="width: 100px;">Voucher No</th>
                    <th style="width: 80px;">Type</th>
                    <th style="width: 35%;">Narration</th>
                    <th class="text-right" style="width: 100px;">Debit</th>
                    <th class="text-right" style="width: 100px;">Credit</th>
                    <th class="text-right" style="width: 100px;">Balance</th>
                </tr>
            </thead>
            <tbody id="accLedgerBody">
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #999;">
                        Select account and click "Load" to view Ledger
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="acc-ledger-summary">
        <div class="acc-ledger-summary-item">
            <label>Opening Balance:</label>
            <span id="accOpeningBalance">0.00</span>
        </div>
        <div class="acc-ledger-summary-item">
            <label>Total Debit:</label>
            <span id="accTotalDebit" class="debit">0.00</span>
        </div>
        <div class="acc-ledger-summary-item">
            <label>Total Credit:</label>
            <span id="accTotalCredit" class="credit">0.00</span>
        </div>
        <div class="acc-ledger-summary-item">
            <label>Closing Balance:</label>
            <span id="accClosingBalance">0.00</span>
        </div>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';

document.addEventListener('DOMContentLoaded', function() {
    accLoadAccounts();
});

async function accLoadAccounts() {
    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=list_accounts');
        const data = await response.json();
        
        if (data.ok && data.data) {
            const select = document.getElementById('accAccountId');
            select.innerHTML = '<option value="">Select Account</option>';
            data.data.forEach(acc => {
                select.innerHTML += `<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`;
            });
            
            <?php if ($accountId > 0): ?>
            select.value = <?php echo $accountId; ?>;
            accLoadLedger();
            <?php endif; ?>
        }
    } catch (error) {
        console.error('Error loading accounts:', error);
    }
}

async function accLoadLedger() {
    const accountId = document.getElementById('accAccountId').value;
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;

    if (!accountId) {
        alert('Please select an account');
        return;
    }

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=ledger&account_id=' + encodeURIComponent(accountId) + '&from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate));
        const data = await response.json();
        
        if (data.ok && data.data) {
            accRenderLedger(data.data);
        } else {
            alert('Error loading Ledger: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error loading Ledger: ' + error.message);
    }
}

function accRenderLedger(ledger) {
    const tbody = document.getElementById('accLedgerBody');
    const entries = ledger.entries || [];

    // Display opening balance
    document.getElementById('accOpeningBalance').textContent = ledger.opening_balance.toFixed(2) + ' ' + ledger.opening_balance_type;
    
    if (entries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 20px; color: #999;">No entries found</td></tr>';
        document.getElementById('accTotalDebit').textContent = '0.00';
        document.getElementById('accTotalCredit').textContent = '0.00';
        document.getElementById('accClosingBalance').textContent = ledger.opening_balance.toFixed(2) + ' ' + ledger.opening_balance_type;
        return;
    }

    let totalDebit = 0;
    let totalCredit = 0;

    tbody.innerHTML = entries.map(entry => {
        totalDebit += parseFloat(entry.debit_amount || 0);
        totalCredit += parseFloat(entry.credit_amount || 0);
        const esc = (window.AccModule && AccModule.escapeHtml) ? AccModule.escapeHtml : (s => String(s ?? ''));
        return `
            <tr>
                <td>${esc(entry.entry_date || entry.voucher_date)}</td>
                <td>${esc(entry.voucher_number)}</td>
                <td>${esc(entry.voucher_type)}</td>
                <td>${esc(entry.narration || entry.voucher_narration)}</td>
                <td class="text-right">${parseFloat(entry.debit_amount || 0).toFixed(2)}</td>
                <td class="text-right">${parseFloat(entry.credit_amount || 0).toFixed(2)}</td>
                <td class="text-right">${parseFloat(entry.running_balance || 0).toFixed(2)} ${esc(entry.balance_type)}</td>
            </tr>
        `;
    }).join('');

    document.getElementById('accTotalDebit').textContent = totalDebit.toFixed(2);
    document.getElementById('accTotalCredit').textContent = totalCredit.toFixed(2);
    document.getElementById('accClosingBalance').textContent = ledger.closing_balance.toFixed(2) + ' ' + ledger.closing_balance_type;
}

function accExportExcel() {
    alert('Excel export functionality - to be implemented');
}

function accPrintLedger() {
    window.print();
}
</script>
