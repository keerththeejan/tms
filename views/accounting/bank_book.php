<?php
/**
 * Desktop Accounting Style Bank Book
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-t');
?>
<style>
.acc-bankbook-module {
    background-color: #F5F5DC;
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

.acc-bankbook-header {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
    padding: 4px 8px;
    border-bottom: 2px solid #1A1A1A;
}

.acc-bankbook-header-title {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.acc-bankbook-toolbar {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
    align-items: center;
}

.acc-bankbook-form-group {
    display: flex;
    flex-direction: column;
}

.acc-bankbook-form-group label {
    font-size: 10px;
    font-weight: bold;
    color: #333;
    margin-bottom: 2px;
}

.acc-bankbook-form-group input,
.acc-bankbook-form-group select {
    font-size: 11px;
    padding: 3px 5px;
    border: 1px solid #999;
    background-color: #FFF;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-bankbook-btn {
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

.acc-bankbook-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-bankbook-table-section {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-bankbook-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.acc-bankbook-table thead {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
}

.acc-bankbook-table th {
    padding: 4px 6px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-bankbook-table td {
    padding: 4px 6px;
    border: 1px solid #999;
}

.acc-bankbook-table .text-right {
    text-align: right;
}

.acc-bankbook-table .text-center {
    text-align: center;
}

.acc-bankbook-summary {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 20px;
    align-items: center;
}

.acc-bankbook-summary-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.acc-bankbook-summary-item label {
    font-size: 11px;
    font-weight: bold;
    color: #333;
}

.acc-bankbook-summary-item span {
    font-size: 12px;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    min-width: 120px;
    text-align: right;
}

.acc-bankbook-summary-item span.debit {
    color: #CC0000;
}

.acc-bankbook-summary-item span.credit {
    color: #006600;
}
</style>

<div class="acc-bankbook-module">
    <!-- Header -->
    <div class="acc-bankbook-header">
        <span class="acc-bankbook-header-title">
            <i class="bi bi-bank"></i> Bank Book
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-bankbook-toolbar">
        <div class="acc-bankbook-form-group">
            <label>Bank Account</label>
            <select id="accBankAccountId">
                <option value="">Select Bank Account</option>
            </select>
        </div>
        <div class="acc-bankbook-form-group">
            <label>From Date</label>
            <input type="date" id="accFromDate" value="<?php echo htmlspecialchars($fromDate); ?>">
        </div>
        <div class="acc-bankbook-form-group">
            <label>To Date</label>
            <input type="date" id="accToDate" value="<?php echo htmlspecialchars($toDate); ?>">
        </div>
        <button type="button" class="acc-bankbook-btn" onclick="accLoadBankBook()">
            Load
        </button>
        <button type="button" class="acc-bankbook-btn" onclick="accExportExcel()">
            Export Excel
        </button>
        <button type="button" class="acc-bankbook-btn" onclick="accPrintBankBook()">
            Print
        </button>
    </div>

    <!-- Table Section -->
    <div class="acc-bankbook-table-section">
        <table class="acc-bankbook-table">
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
            <tbody id="accBankBookBody">
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #999;">
                        Select bank account and click "Load" to view Bank Book
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="acc-bankbook-summary">
        <div class="acc-bankbook-summary-item">
            <label>Total Debit:</label>
            <span id="accTotalDebit" class="debit">0.00</span>
        </div>
        <div class="acc-bankbook-summary-item">
            <label>Total Credit:</label>
            <span id="accTotalCredit" class="credit">0.00</span>
        </div>
        <div class="acc-bankbook-summary-item">
            <label>Closing Balance:</label>
            <span id="accClosingBalance">0.00</span>
        </div>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';

document.addEventListener('DOMContentLoaded', function() {
    accLoadBankAccounts();
});

async function accLoadBankAccounts() {
    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=list_accounts');
        const data = await response.json();
        
        if (data.ok && data.data) {
            const select = document.getElementById('accBankAccountId');
            select.innerHTML = '<option value="">Select Bank Account</option>';
            data.data.forEach(acc => {
                if (acc.group_name === 'Bank') {
                    select.innerHTML += `<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`;
                }
            });
        }
    } catch (error) {
        console.error('Error loading bank accounts:', error);
    }
}

async function accLoadBankBook() {
    const bankAccountId = document.getElementById('accBankAccountId').value;
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;

    if (!bankAccountId) {
        alert('Please select a bank account');
        return;
    }

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=ledger&account_id=' + encodeURIComponent(bankAccountId) + '&from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate));
        const data = await response.json();
        
        if (data.ok && data.data) {
            accRenderBankBook(data.data);
        } else {
            alert('Error loading Bank Book: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error loading Bank Book: ' + error.message);
    }
}

function accRenderBankBook(ledger) {
    const tbody = document.getElementById('accBankBookBody');
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

function accPrintBankBook() {
    window.print();
}
</script>
