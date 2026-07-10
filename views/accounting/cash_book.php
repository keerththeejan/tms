<?php
/**
 * Desktop Accounting Style Cash Book
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-t');
?>
<style>
.acc-cashbook-module {
    background-color: #F5F5DC;
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

.acc-cashbook-header {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
    padding: 4px 8px;
    border-bottom: 2px solid #1A1A1A;
}

.acc-cashbook-header-title {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.acc-cashbook-toolbar {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
    align-items: center;
}

.acc-cashbook-form-group {
    display: flex;
    flex-direction: column;
}

.acc-cashbook-form-group label {
    font-size: 10px;
    font-weight: bold;
    color: #333;
    margin-bottom: 2px;
}

.acc-cashbook-form-group input,
.acc-cashbook-form-group select {
    font-size: 11px;
    padding: 3px 5px;
    border: 1px solid #999;
    background-color: #FFF;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-cashbook-btn {
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

.acc-cashbook-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-cashbook-table-section {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-cashbook-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.acc-cashbook-table thead {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
}

.acc-cashbook-table th {
    padding: 4px 6px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-cashbook-table td {
    padding: 4px 6px;
    border: 1px solid #999;
}

.acc-cashbook-table .text-right {
    text-align: right;
}

.acc-cashbook-table .text-center {
    text-align: center;
}

.acc-cashbook-summary {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 20px;
    align-items: center;
}

.acc-cashbook-summary-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.acc-cashbook-summary-item label {
    font-size: 11px;
    font-weight: bold;
    color: #333;
}

.acc-cashbook-summary-item span {
    font-size: 12px;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    min-width: 120px;
    text-align: right;
}

.acc-cashbook-summary-item span.debit {
    color: #CC0000;
}

.acc-cashbook-summary-item span.credit {
    color: #006600;
}
</style>

<div class="acc-cashbook-module">
    <!-- Header -->
    <div class="acc-cashbook-header">
        <span class="acc-cashbook-header-title">
            <i class="bi bi-cash"></i> Cash Book
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-cashbook-toolbar">
        <div class="acc-cashbook-form-group">
            <label>Cash Account</label>
            <select id="accCashAccountId">
                <option value="">Select Cash Account</option>
            </select>
        </div>
        <div class="acc-cashbook-form-group">
            <label>From Date</label>
            <input type="date" id="accFromDate" value="<?php echo htmlspecialchars($fromDate); ?>">
        </div>
        <div class="acc-cashbook-form-group">
            <label>To Date</label>
            <input type="date" id="accToDate" value="<?php echo htmlspecialchars($toDate); ?>">
        </div>
        <button type="button" class="acc-cashbook-btn" onclick="accLoadCashBook()">
            Load
        </button>
        <button type="button" class="acc-cashbook-btn" onclick="accExportExcel()">
            Export Excel
        </button>
        <button type="button" class="acc-cashbook-btn" onclick="accPrintCashBook()">
            Print
        </button>
    </div>

    <!-- Table Section -->
    <div class="acc-cashbook-table-section">
        <table class="acc-cashbook-table">
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
            <tbody id="accCashBookBody">
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #999;">
                        Select cash account and click "Load" to view Cash Book
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="acc-cashbook-summary">
        <div class="acc-cashbook-summary-item">
            <label>Total Debit:</label>
            <span id="accTotalDebit" class="debit">0.00</span>
        </div>
        <div class="acc-cashbook-summary-item">
            <label>Total Credit:</label>
            <span id="accTotalCredit" class="credit">0.00</span>
        </div>
        <div class="acc-cashbook-summary-item">
            <label>Closing Balance:</label>
            <span id="accClosingBalance">0.00</span>
        </div>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';

document.addEventListener('DOMContentLoaded', function() {
    accLoadCashAccounts();
});

async function accLoadCashAccounts() {
    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=list_accounts');
        const data = await response.json();
        
        if (data.ok && data.data) {
            const select = document.getElementById('accCashAccountId');
            select.innerHTML = '<option value="">Select Cash Account</option>';
            data.data.forEach(acc => {
                if (acc.group_name === 'Cash') {
                    select.innerHTML += `<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`;
                }
            });
        }
    } catch (error) {
        console.error('Error loading cash accounts:', error);
    }
}

async function accLoadCashBook() {
    const cashAccountId = document.getElementById('accCashAccountId').value;
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;

    if (!cashAccountId) {
        alert('Please select a cash account');
        return;
    }

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=ledger&account_id=' + encodeURIComponent(cashAccountId) + '&from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate));
        const data = await response.json();
        
        if (data.ok && data.data) {
            accRenderCashBook(data.data);
        } else {
            alert('Error loading Cash Book: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error loading Cash Book: ' + error.message);
    }
}

function accRenderCashBook(ledger) {
    const tbody = document.getElementById('accCashBookBody');
    const entries = ledger.entries || [];

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
                <td class="text-right">${parseFloat(entry.running_balance || 0).toFixed(2)}</td>
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

function accPrintCashBook() {
    window.print();
}
</script>
