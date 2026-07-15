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

/* Premium Summary Bar */
.acc-daybook-summary-bar {
    margin: 8px 4px 0;
    padding: 0 2px;
}

.acc-db-summary-row {
    --bs-gutter-x: 0.75rem;
}

.acc-db-summary-card {
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04);
    padding: 14px 16px;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 6px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.acc-db-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.1), 0 2px 6px rgba(15, 23, 42, 0.06);
}

.acc-db-summary-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: 'Inter', 'Tahoma', sans-serif;
}

.acc-db-summary-label .acc-db-icon {
    font-size: 14px;
    line-height: 1;
}

.acc-db-summary-value {
    font-size: 15px;
    font-weight: 700;
    font-family: 'Inter', 'Courier New', monospace;
    text-align: right;
    margin-top: auto;
    letter-spacing: -0.01em;
}

.acc-db-summary-value.is-count {
    font-family: 'Inter', 'Tahoma', sans-serif;
    color: #334155;
}

.acc-db-summary-value.is-opening {
    color: #2563eb;
}

.acc-db-summary-value.is-debit {
    color: #16a34a;
}

.acc-db-summary-value.is-credit {
    color: #dc2626;
}

.acc-db-summary-value.is-closing {
    color: #14532d;
}

.acc-db-summary-card.is-loading .acc-db-summary-value {
    color: #94a3b8;
}

@media (max-width: 575.98px) {
    .acc-db-summary-value {
        font-size: 14px;
    }
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

    <!-- Summary Bar -->
    <div class="acc-daybook-summary-bar no-print" id="accDayBookSummaryBar" aria-live="polite">
        <div class="container-fluid px-0">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 acc-db-summary-row">
                <div class="col">
                    <div class="acc-db-summary-card" id="accSummaryCardOpening">
                        <div class="acc-db-summary-label">
                            <span class="acc-db-icon" aria-hidden="true">💰</span>
                            Opening Balance
                        </div>
                        <div class="acc-db-summary-value is-opening" id="accSummaryOpeningBalance">—</div>
                    </div>
                </div>
                <div class="col">
                    <div class="acc-db-summary-card" id="accSummaryCardDebit">
                        <div class="acc-db-summary-label">
                            <span class="acc-db-icon" aria-hidden="true">⬆</span>
                            Total Debit
                        </div>
                        <div class="acc-db-summary-value is-debit" id="accSummaryTotalDebit">—</div>
                    </div>
                </div>
                <div class="col">
                    <div class="acc-db-summary-card" id="accSummaryCardCredit">
                        <div class="acc-db-summary-label">
                            <span class="acc-db-icon" aria-hidden="true">⬇</span>
                            Total Credit
                        </div>
                        <div class="acc-db-summary-value is-credit" id="accSummaryTotalCredit">—</div>
                    </div>
                </div>
                <div class="col">
                    <div class="acc-db-summary-card" id="accSummaryCardClosing">
                        <div class="acc-db-summary-label">
                            <span class="acc-db-icon" aria-hidden="true">🏦</span>
                            Closing Balance
                        </div>
                        <div class="acc-db-summary-value is-closing" id="accSummaryClosingBalance">—</div>
                    </div>
                </div>
                <div class="col">
                    <div class="acc-db-summary-card" id="accSummaryCardRecords">
                        <div class="acc-db-summary-label">
                            <span class="acc-db-icon" aria-hidden="true">📄</span>
                            Total Records
                        </div>
                        <div class="acc-db-summary-value is-count" id="accSummaryTotalRecords">—</div>
                    </div>
                </div>
            </div>
        </div>
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

        <!-- Print-only summary (same AccountingBalanceService totals as screen) -->
        <div class="db-print-only db-print-summary" id="accDayBookPrintSummary" aria-label="Day Book Summary">
            <div class="db-print-summary-title">DAY BOOK SUMMARY</div>
            <table class="db-print-summary-table">
                <tbody>
                    <tr>
                        <th scope="row">Opening Balance</th>
                        <td id="accPrintOpeningBalance">LKR 0.00</td>
                    </tr>
                    <tr>
                        <th scope="row">Total Credit</th>
                        <td id="accPrintTotalCredit">LKR 0.00</td>
                    </tr>
                    <tr>
                        <th scope="row">Total Debit</th>
                        <td id="accPrintTotalDebit">LKR 0.00</td>
                    </tr>
                    <tr class="is-closing">
                        <th scope="row">Closing Balance</th>
                        <td id="accPrintClosingBalance">LKR 0.00</td>
                    </tr>
                    <tr>
                        <th scope="row">Total Records</th>
                        <td id="accPrintTotalRecords" class="is-count">0</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';
/** Last server summary — shared by screen cards and print report */
let accLastDayBookSummary = {
    total_records: 0,
    opening_balance: 0,
    total_debit: 0,
    total_credit: 0,
    closing_balance: 0,
};

document.addEventListener('DOMContentLoaded', function () {
    accLoadDayBook();
});

function accDayBookFetchUrl(fromDate, toDate, voucherType) {
    return accBaseUrl + 'index.php?page=api_accounting&acc_action=day_book'
        + '&from_date=' + encodeURIComponent(fromDate)
        + '&to_date=' + encodeURIComponent(toDate)
        + '&voucher_type=' + encodeURIComponent(voucherType || '');
}

function accFormatAmount(n) {
    return (parseFloat(n) || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function accFormatCurrency(n) {
    return 'Rs. ' + accFormatAmount(n);
}

/**
 * Display-only balance formatter (matches Helpers::formatBalance / AccountingBalanceService::formatBalance).
 * Never shows a minus sign. Optional DR/CR from the signed value.
 * Does not alter the underlying numeric amount used for calculations.
 */
function accFormatBalance(n, withSide) {
    const value = parseFloat(n);
    const amount = isFinite(value) ? value : 0;
    const absFormatted = Math.abs(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    let display = 'Rs. ' + absFormatted;
    if (withSide !== false && Math.abs(amount) >= 0.005) {
        display += amount < 0 ? ' DR' : ' CR';
    }
    return display;
}

/**
 * Print / report money format: LKR 1,234.56 (unsigned absolute for Closing Balance via accFormatBalancePrint)
 */
function accFormatPrintLkr(n) {
    const value = parseFloat(n);
    const amount = isFinite(value) ? value : 0;
    const absFormatted = Math.abs(amount).toLocaleString('en-LK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    return amount < 0 ? ('-LKR ' + absFormatted) : ('LKR ' + absFormatted);
}

/** Closing Balance print: absolute amount + DR/CR, never a leading minus. */
function accFormatBalancePrint(n) {
    const value = parseFloat(n);
    const amount = isFinite(value) ? value : 0;
    const absFormatted = Math.abs(amount).toLocaleString('en-LK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    let display = 'LKR ' + absFormatted;
    if (Math.abs(amount) >= 0.005) {
        display += amount < 0 ? ' DR' : ' CR';
    }
    return display;
}

function accEmptySummary() {
    return {
        total_records: 0,
        opening_balance: 0,
        total_debit: 0,
        total_credit: 0,
        closing_balance: 0,
    };
}

function accSetSummaryLoading(isLoading) {
    document.querySelectorAll('.acc-db-summary-card').forEach(function (card) {
        card.classList.toggle('is-loading', isLoading);
    });
}

function accUpdatePrintSummary(summary) {
    const s = summary || accEmptySummary();
    const openingEl = document.getElementById('accPrintOpeningBalance');
    const creditEl = document.getElementById('accPrintTotalCredit');
    const debitEl = document.getElementById('accPrintTotalDebit');
    const closingEl = document.getElementById('accPrintClosingBalance');
    const recordsEl = document.getElementById('accPrintTotalRecords');

    if (openingEl) openingEl.textContent = accFormatPrintLkr(s.opening_balance ?? 0);
    if (creditEl) creditEl.textContent = accFormatPrintLkr(s.total_credit ?? 0);
    if (debitEl) debitEl.textContent = accFormatPrintLkr(s.total_debit ?? 0);
    if (closingEl) closingEl.textContent = accFormatBalancePrint(s.closing_balance ?? 0);
    if (recordsEl) recordsEl.textContent = String(s.total_records ?? 0);
}

function accUpdateSummaryBar(summary) {
    const resolved = summary || accEmptySummary();
    accLastDayBookSummary = resolved;

    const totalRecordsEl = document.getElementById('accSummaryTotalRecords');
    const openingEl = document.getElementById('accSummaryOpeningBalance');
    const debitEl = document.getElementById('accSummaryTotalDebit');
    const creditEl = document.getElementById('accSummaryTotalCredit');
    const closingEl = document.getElementById('accSummaryClosingBalance');

    if (!summary) {
        if (totalRecordsEl) totalRecordsEl.textContent = '—';
        if (openingEl) openingEl.textContent = '—';
        if (debitEl) debitEl.textContent = '—';
        if (creditEl) creditEl.textContent = '—';
        if (closingEl) closingEl.textContent = '—';
        accUpdatePrintSummary(accEmptySummary());
        return;
    }

    if (totalRecordsEl) totalRecordsEl.textContent = String(summary.total_records ?? 0);
    if (openingEl) openingEl.textContent = accFormatCurrency(summary.opening_balance ?? 0);
    if (debitEl) debitEl.textContent = accFormatCurrency(summary.total_debit ?? 0);
    if (creditEl) creditEl.textContent = accFormatCurrency(summary.total_credit ?? 0);
    if (closingEl) closingEl.textContent = accFormatBalance(summary.closing_balance ?? 0, true);

    // Keep print summary in sync with the same API totals (Credit = Cash In)
    accUpdatePrintSummary(summary);
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

/**
 * Load Day Book rows + summary from the server.
 * Summary uses AccountingBalanceService:
 *   Closing = Opening + Credit − Debit  (Credit = Cash In, Debit = Cash Out)
 */
async function accLoadDayBook() {
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;
    const voucherType = document.getElementById('accVoucherTypeFilter').value;

    accSetSummaryLoading(true);

    try {
        const periodResponse = await fetch(accDayBookFetchUrl(fromDate, toDate, voucherType));
        const periodData = await periodResponse.json();

        if (!periodData.ok || !periodData.data) {
            accUpdateSummaryBar(null);
            alert('Error loading Day Book: ' + (periodData.error || 'Unknown error'));
            return;
        }

        accRenderDayBook(periodData.data, periodData.summary || null);
    } catch (error) {
        accUpdateSummaryBar(null);
        alert('Error loading Day Book: ' + error.message);
    } finally {
        accSetSummaryLoading(false);
    }
}

function accRenderDayBook(entries, summary) {
    const tbody = document.getElementById('accDayBookBody');
    const count = entries.length;
    const resolvedSummary = summary || accEmptySummary();

    accUpdateSummaryBar(resolvedSummary);

    if (count === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="padding: 20px; color: #999;">No entries found</td></tr>';
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
}

function accExportExcel() {
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;
    const voucherType = document.getElementById('accVoucherTypeFilter').value;
    window.location.href = accBaseUrl + 'index.php?page=api_accounting&acc_action=export_day_book'
        + '&from_date=' + encodeURIComponent(fromDate)
        + '&to_date=' + encodeURIComponent(toDate)
        + '&voucher_type=' + encodeURIComponent(voucherType || '');
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
    // Ensure print totals match the loaded Day Book summary before printing
    accUpdatePrintSummary(accLastDayBookSummary || accEmptySummary());
    window.print();
}
</script>
