<?php
/**
 * Desktop Accounting Style Ledger Report
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
$accountId = (int) ($_GET['account_id'] ?? 0);
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-t');
$company = Helpers::company();
$companyName = (string) ($company['name'] ?? 'TS Transport');
$logoDisplay = (string) ($company['logo_display'] ?? 'builtin');
$logoUrl = (string) ($company['logo_url'] ?? '');
$logoInitials = (string) ($company['logo_initials'] ?? 'TS');
if ($logoUrl !== '' && strpos($logoUrl, 'http') !== 0 && strpos($logoUrl, '//') !== 0) {
    $logoUrl = Helpers::baseUrl($logoUrl);
}
$printedBy = '';
try {
    $u = Auth::user();
    $printedBy = (string) ($u['full_name'] ?? $u['username'] ?? '');
} catch (Throwable $e) {
    $printedBy = '';
}
$printedDate = date('Y-m-d H:i');
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
    flex-wrap: wrap;
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
    font-family: 'Courier New', monospace;
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

.acc-ledger-print-letterhead {
    display: none;
}

@media print {
  @page { size: A4 portrait; margin: 12mm; }
  .acc-ledger-header,
  .acc-ledger-toolbar,
  .no-print { display: none !important; }
  .acc-ledger-module { background: #fff !important; min-height: auto; }
  .acc-ledger-table-section,
  .acc-ledger-summary { border: 1px solid #000 !important; margin: 0 0 8px 0 !important; }
  .acc-ledger-print-letterhead {
    display: block !important;
    margin-bottom: 12px;
    border-bottom: 2px solid #000;
    padding-bottom: 8px;
  }
  .acc-ledger-print-letterhead-row {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .acc-ledger-print-logo img {
    max-height: 48px;
    max-width: 90px;
  }
  .acc-ledger-print-logo-mark {
    width: 48px;
    height: 48px;
    background: #c00;
    color: #fff;
    font-weight: bold;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .acc-ledger-print-company {
    font-size: 16px;
    font-weight: bold;
    margin: 0;
  }
  .acc-ledger-print-report {
    font-size: 13px;
    font-weight: bold;
    text-transform: uppercase;
    margin: 2px 0 0 0;
  }
  .acc-ledger-print-meta {
    display: block !important;
    margin: 8px 0;
    font-size: 11px;
    line-height: 1.5;
  }
  .acc-ledger-table th,
  .acc-ledger-table td { border: 1px solid #000 !important; }
}
.acc-ledger-print-meta { display: none; }
</style>

<div class="acc-ledger-module">
    <!-- Header -->
    <div class="acc-ledger-header no-print">
        <span class="acc-ledger-header-title">
            <i class="bi bi-journal-text"></i> Ledger Report
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-ledger-toolbar no-print">
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
        <div class="acc-ledger-form-group">
            <label>Voucher Type</label>
            <select id="accVoucherType">
                <option value="">All</option>
                <option value="PAYMENT">Payment</option>
                <option value="RECEIPT">Receipt</option>
                <option value="JOURNAL">Journal</option>
                <option value="CONTRA">Contra</option>
                <option value="TRANSFER">Transfer</option>
            </select>
        </div>
        <div class="acc-ledger-form-group">
            <label>Branch</label>
            <select id="accBranchId">
                <option value="">All</option>
            </select>
        </div>
        <div class="acc-ledger-form-group">
            <label>Status</label>
            <select id="accStatus">
                <option value="">Posted</option>
                <option value="POSTED">Posted</option>
                <option value="DRAFT">Draft</option>
                <option value="CANCELLED">Cancelled</option>
            </select>
        </div>
        <button type="button" class="acc-ledger-btn" onclick="accLoadLedger()">
            Load
        </button>
        <button type="button" class="acc-ledger-btn" onclick="accExportExcel()">
            Export Excel
        </button>
        <button type="button" class="acc-ledger-btn" onclick="accExportPdf()">
            PDF
        </button>
        <button type="button" class="acc-ledger-btn" onclick="accPrintLedger()">
            Print
        </button>
    </div>

    <div class="acc-ledger-print-letterhead" id="accLedgerPrintLetterhead">
        <div class="acc-ledger-print-letterhead-row">
            <div class="acc-ledger-print-logo">
                <?php if ($logoDisplay === 'image' && $logoUrl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="">
                <?php else: ?>
                    <div class="acc-ledger-print-logo-mark"><?php echo htmlspecialchars(strtoupper($logoInitials)); ?></div>
                <?php endif; ?>
            </div>
            <div>
                <p class="acc-ledger-print-company"><?php echo htmlspecialchars($companyName); ?></p>
                <p class="acc-ledger-print-report">General Ledger Report</p>
            </div>
        </div>
    </div>

    <div class="acc-ledger-print-meta" id="accLedgerPrintMeta"></div>

    <!-- Table Section -->
    <div class="acc-ledger-table-section">
        <table class="acc-ledger-table">
            <thead>
                <tr>
                    <th style="width: 90px;">Date</th>
                    <th style="width: 110px;">Voucher No</th>
                    <th style="width: 90px;">Voucher Type</th>
                    <th>Narration</th>
                    <th class="text-right" style="width: 100px;">Debit</th>
                    <th class="text-right" style="width: 100px;">Credit</th>
                    <th class="text-right" style="width: 130px;">Running Balance</th>
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
            <label>Account Type:</label>
            <span id="accAccountTypeLabel">—</span>
        </div>
        <div class="acc-ledger-summary-item">
            <label>Normal Balance:</label>
            <span id="accNormalBalance">—</span>
        </div>
        <div class="acc-ledger-summary-item">
            <label>Opening Balance:</label>
            <span id="accOpeningBalance">0.00 DR</span>
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
            <span id="accClosingBalance">0.00 DR</span>
        </div>
        <div class="acc-ledger-summary-item">
            <label>Total Transactions:</label>
            <span id="accTotalTransactions">0</span>
        </div>
    </div>
</div>

<script>
const accBaseUrl = <?php echo json_encode($baseUrl, JSON_UNESCAPED_SLASHES); ?>;
const accPrintCompany = <?php echo json_encode($companyName, JSON_UNESCAPED_UNICODE); ?>;
const accPrintedBy = <?php echo json_encode($printedBy, JSON_UNESCAPED_UNICODE); ?>;
const accPrintedDate = <?php echo json_encode($printedDate); ?>;
let accLastLedger = null;

document.addEventListener('DOMContentLoaded', function () {
    accLoadAccounts();
    accLoadBranches();
});

function accLedgerQuery() {
    const accountId = document.getElementById('accAccountId').value;
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;
    const voucherType = document.getElementById('accVoucherType').value;
    const branchId = document.getElementById('accBranchId').value;
    const status = document.getElementById('accStatus').value;
    return 'account_id=' + encodeURIComponent(accountId)
        + '&from_date=' + encodeURIComponent(fromDate)
        + '&to_date=' + encodeURIComponent(toDate)
        + '&voucher_type=' + encodeURIComponent(voucherType || '')
        + '&branch_id=' + encodeURIComponent(branchId || '')
        + '&status=' + encodeURIComponent(status || '');
}

async function accLoadBranches() {
    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api&action=branches');
        const data = await response.json();
        const select = document.getElementById('accBranchId');
        if (!select || !data) return;
        const rows = data.data || data.branches || data;
        if (!Array.isArray(rows)) return;
        rows.forEach(function (b) {
            const id = b.id || b.branch_id;
            const name = b.name || b.branch_name || ('Branch ' + id);
            if (!id) return;
            select.innerHTML += '<option value="' + id + '">' + String(name).replace(/</g, '&lt;') + '</option>';
        });
    } catch (e) { /* optional */ }
}

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
    if (!accountId) {
        alert('Please select an account');
        return;
    }

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=ledger&' + accLedgerQuery());
        const data = await response.json();

        if (data.ok && data.data) {
            accLastLedger = data.data;
            accRenderLedger(data.data);
        } else {
            alert('Error loading Ledger: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error loading Ledger: ' + error.message);
    }
}

function accFmtQty(n) {
    const v = parseFloat(n) || 0;
    return v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function accRenderLedger(ledger) {
    const tbody = document.getElementById('accLedgerBody');
    const entries = ledger.entries || [];
    const acc = ledger.account || {};
    const esc = (window.AccModule && AccModule.escapeHtml) ? AccModule.escapeHtml : (s => String(s ?? ''));

    document.getElementById('accAccountTypeLabel').textContent = ledger.account_type || acc.account_type || '—';
    document.getElementById('accNormalBalance').textContent = ledger.normal_balance || '—';
    document.getElementById('accOpeningBalance').textContent = ledger.opening_balance_display
        || (accFmtQty(ledger.opening_balance) + ' ' + ((ledger.opening_balance_type === 'CREDIT') ? 'CR' : 'DR'));
    document.getElementById('accTotalDebit').textContent = ledger.total_debit_display || accFmtQty(ledger.total_debit);
    document.getElementById('accTotalCredit').textContent = ledger.total_credit_display || accFmtQty(ledger.total_credit);
    document.getElementById('accClosingBalance').textContent = ledger.closing_balance_display
        || (accFmtQty(ledger.closing_balance) + ' ' + ((ledger.closing_balance_type === 'CREDIT') ? 'CR' : 'DR'));
    document.getElementById('accTotalTransactions').textContent = String(ledger.total_transactions ?? entries.length);

    const meta = document.getElementById('accLedgerPrintMeta');
    if (meta) {
        meta.innerHTML =
            '<div><strong>Company:</strong> ' + esc(accPrintCompany) + '</div>'
            + '<div><strong>Report:</strong> General Ledger</div>'
            + '<div><strong>Account Name:</strong> ' + esc(acc.account_name || '') + '</div>'
            + '<div><strong>Account Code:</strong> ' + esc(acc.account_code || '') + '</div>'
            + '<div><strong>Account Type:</strong> ' + esc(ledger.account_type || acc.account_type || '') + '</div>'
            + '<div><strong>Normal Balance:</strong> ' + esc(ledger.normal_balance || '') + '</div>'
            + '<div><strong>Date Range:</strong> '
            + esc(document.getElementById('accFromDate').value) + ' to ' + esc(document.getElementById('accToDate').value) + '</div>'
            + '<div><strong>Printed By:</strong> ' + esc(accPrintedBy || '—') + '</div>'
            + '<div><strong>Printed Date:</strong> ' + esc(accPrintedDate) + '</div>';
    }

    if (entries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 20px; color: #999;">No entries found for this ledger</td></tr>';
        return;
    }

    tbody.innerHTML = entries.map(entry => {
        const debitCell = entry.debit_display !== undefined
            ? esc(entry.debit_display)
            : ((parseFloat(entry.debit_amount) || 0) > 0 ? accFmtQty(entry.debit_amount) : '');
        const creditCell = entry.credit_display !== undefined
            ? esc(entry.credit_display)
            : ((parseFloat(entry.credit_amount) || 0) > 0 ? accFmtQty(entry.credit_amount) : '');
        const runCell = entry.running_balance_display
            || (accFmtQty(entry.running_balance) + ' ' + ((entry.balance_type === 'CREDIT') ? 'CR' : 'DR'));
        return `
            <tr>
                <td>${esc(entry.entry_date || entry.voucher_date)}</td>
                <td>${esc(entry.voucher_number)}</td>
                <td>${esc(entry.voucher_type)}</td>
                <td>${esc(entry.narration || entry.voucher_narration || '')}</td>
                <td class="text-right">${debitCell}</td>
                <td class="text-right">${creditCell}</td>
                <td class="text-right">${esc(runCell)}</td>
            </tr>
        `;
    }).join('');
}

function accExportExcel() {
    const accountId = document.getElementById('accAccountId').value;
    if (!accountId) { alert('Please select an account'); return; }
    window.location.href = accBaseUrl + 'index.php?page=api_accounting&acc_action=export_ledger&format=csv&' + accLedgerQuery();
}

function accExportPdf() {
    const accountId = document.getElementById('accAccountId').value;
    if (!accountId) { alert('Please select an account'); return; }
    window.location.href = accBaseUrl + 'index.php?page=api_accounting&acc_action=export_ledger&format=pdf&' + accLedgerQuery();
}

function accPrintLedger() {
    if (!accLastLedger) {
        alert('Please load a ledger first');
        return;
    }
    window.print();
}
</script>
