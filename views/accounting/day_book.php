<?php
/**
 * Desktop Accounting Style Day Book
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-t');
$voucherType = $_GET['voucher_type'] ?? '';
$dbPrintCssPath = dirname(__DIR__, 2) . '/public/assets/css/day-book-print.css';
$dbPrintCssVer = is_file($dbPrintCssPath) ? (string)filemtime($dbPrintCssPath) : '1';
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/day-book-print.css?v=' . rawurlencode($dbPrintCssVer)); ?>">
<style>
.acc-daybook-module {
    background-color: #F5F5DC;
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

.acc-daybook-header {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
    padding: 4px 8px;
    border-bottom: 2px solid #1A1A1A;
}

.acc-daybook-header-title {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.acc-daybook-toolbar {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
    align-items: center;
}

.acc-daybook-form-group {
    display: flex;
    flex-direction: column;
}

.acc-daybook-form-group label {
    font-size: 10px;
    font-weight: bold;
    color: #333;
    margin-bottom: 2px;
}

.acc-daybook-form-group input,
.acc-daybook-form-group select {
    font-size: 11px;
    padding: 3px 5px;
    border: 1px solid #999;
    background-color: #FFF;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-daybook-btn {
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

.acc-daybook-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-daybook-table-section {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-daybook-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.acc-daybook-table thead {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
}

.acc-daybook-table th {
    padding: 4px 6px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-daybook-table td {
    padding: 4px 6px;
    border: 1px solid #999;
}

.acc-daybook-table .text-right {
    text-align: right;
}

.acc-daybook-table .text-center {
    text-align: center;
}

.acc-daybook-summary {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 20px;
    align-items: center;
}

.acc-daybook-summary-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.acc-daybook-summary-item label {
    font-size: 11px;
    font-weight: bold;
    color: #333;
}

.acc-daybook-summary-item span {
    font-size: 12px;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    min-width: 120px;
    text-align: right;
}

.acc-daybook-summary-item span.debit {
    color: #CC0000;
}

.acc-daybook-summary-item span.credit {
    color: #006600;
}
</style>

<div class="acc-daybook-module">
    <!-- Header -->
    <div class="acc-daybook-header no-print">
        <span class="acc-daybook-header-title">
            <i class="bi bi-book"></i> Day Book
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-daybook-toolbar no-print">
        <div class="acc-daybook-form-group">
            <label>From Date</label>
            <input type="date" id="accFromDate" value="<?php echo htmlspecialchars($fromDate); ?>">
        </div>
        <div class="acc-daybook-form-group">
            <label>To Date</label>
            <input type="date" id="accToDate" value="<?php echo htmlspecialchars($toDate); ?>">
        </div>
        <div class="acc-daybook-form-group">
            <label>Voucher Type</label>
            <select id="accVoucherTypeFilter">
                <option value="">All Types</option>
                <option value="PAYMENT" <?php echo $voucherType === 'PAYMENT' ? 'selected' : ''; ?>>Payment</option>
                <option value="RECEIPT" <?php echo $voucherType === 'RECEIPT' ? 'selected' : ''; ?>>Receipt</option>
                <option value="JOURNAL" <?php echo $voucherType === 'JOURNAL' ? 'selected' : ''; ?>>Journal</option>
                <option value="CONTRA" <?php echo $voucherType === 'CONTRA' ? 'selected' : ''; ?>>Contra</option>
                <option value="TRANSFER" <?php echo $voucherType === 'TRANSFER' ? 'selected' : ''; ?>>Transfer</option>
            </select>
        </div>
        <button type="button" class="acc-daybook-btn" onclick="accLoadDayBook()">
            Load
        </button>
        <button type="button" class="acc-daybook-btn" onclick="accExportExcel()">
            Export Excel
        </button>
        <button type="button" class="acc-daybook-btn" onclick="printReport()">
            Print Report
        </button>
    </div>

    <div id="report-print-area" class="report-print-area">
        <?php include __DIR__ . '/../partials/report/day_book_print_header.php'; ?>

    <!-- Table Section -->
    <div class="acc-daybook-table-section">
        <table class="acc-daybook-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Date</th>
                    <th style="width: 100px;">Voucher No</th>
                    <th style="width: 80px;">Type</th>
                    <th style="width: 16%;">Account Name</th>
                    <th style="width: 10%;">Reference</th>
                    <th style="width: 18%;">Narration</th>
                    <th class="text-right" style="width: 90px;">Debit</th>
                    <th class="text-right" style="width: 90px;">Credit</th>
                    <th style="width: 10%;">Branch</th>
                    <th class="db-col-created-by" style="width: 10%;">Created By</th>
                </tr>
            </thead>
            <tbody id="accDayBookBody">
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #999;">
                        Loading Day Book entries…
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>

    <!-- Summary -->
    <div class="acc-daybook-summary no-print">
        <div class="acc-daybook-summary-item">
            <label>Total Records:</label>
            <span id="accTotalRecords">0</span>
        </div>
        <div class="acc-daybook-summary-item">
            <span id="accRecordSummary" style="min-width: auto; font-family: Tahoma, Arial, sans-serif; font-weight: normal;">Showing 0 Voucher Entries</span>
        </div>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';

document.addEventListener('DOMContentLoaded', function () {
    accLoadDayBook();
});

function accFormatAmount(n) {
    return (parseFloat(n) || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function accEscapeHtml(s) {
    if (window.AccModule && AccModule.escapeHtml) {
        return AccModule.escapeHtml(s);
    }
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function accLoadDayBook() {
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;
    const voucherType = document.getElementById('accVoucherTypeFilter').value;

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=day_book&from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate) + '&voucher_type=' + encodeURIComponent(voucherType));
        const data = await response.json();
        
        if (data.ok && data.data) {
            accRenderDayBook(data.data);
        } else {
            alert('Error loading Day Book: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error loading Day Book: ' + error.message);
    }
}

function accRenderDayBook(entries) {
    const tbody = document.getElementById('accDayBookBody');
    const totalEl = document.getElementById('accTotalRecords');
    const summaryEl = document.getElementById('accRecordSummary');
    const count = entries.length;

    if (count === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="padding: 20px; color: #999;">No entries found</td></tr>';
        if (totalEl) totalEl.textContent = '0';
        if (summaryEl) summaryEl.textContent = 'Showing 0 Voucher Entries';
        return;
    }

    tbody.innerHTML = entries.map(function (entry) {
        return '<tr>' +
            '<td>' + accEscapeHtml(entry.entry_date) + '</td>' +
            '<td>' + accEscapeHtml(entry.voucher_number) + '</td>' +
            '<td>' + accEscapeHtml(entry.voucher_type) + '</td>' +
            '<td>' + accEscapeHtml(entry.account_name) + '</td>' +
            '<td>' + accEscapeHtml(entry.reference || '') + '</td>' +
            '<td>' + accEscapeHtml(entry.narration || '') + '</td>' +
            '<td class="text-right">' + accFormatAmount(entry.debit_amount || 0) + '</td>' +
            '<td class="text-right">' + accFormatAmount(entry.credit_amount || 0) + '</td>' +
            '<td>' + accEscapeHtml(entry.branch || '') + '</td>' +
            '<td class="db-col-created-by">' + accEscapeHtml(entry.created_by || '') + '</td>' +
            '</tr>';
    }).join('');

    if (totalEl) totalEl.textContent = String(count);
    if (summaryEl) {
        summaryEl.textContent = 'Showing ' + count + ' Voucher Entr' + (count === 1 ? 'y' : 'ies');
    }
}

function accExportExcel() {
    alert('Excel export functionality - to be implemented');
}

function accFormatPrintDateTime() {
    const now = new Date();
    const pad = function (n) { return String(n).padStart(2, '0'); };
    return pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear() + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes());
}

function printReport() {
    const printed = accFormatPrintDateTime();
    const printDateEl = document.getElementById('dbPrintDate');
    const infoPrintDateEl = document.getElementById('dbInfoPrintDate');
    if (printDateEl) printDateEl.textContent = printed;
    if (infoPrintDateEl) infoPrintDateEl.textContent = printed;
    window.print();
}
</script>
