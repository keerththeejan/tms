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

/* Searchable account selector (Select2) — matches ledger toolbar */
.acc-ledger-form-group--account {
    min-width: min(100%, 280px);
    flex: 1 1 240px;
    max-width: 420px;
}
.acc-ledger-form-group--account select,
.acc-ledger-toolbar .select2-container {
    min-width: 220px;
    width: 100% !important;
}
.acc-ledger-toolbar .select2-container--bootstrap-5 .select2-selection {
    font-size: 11px;
    font-family: 'Tahoma', 'Arial', sans-serif;
    border: 1px solid #999;
    border-radius: 0;
    min-height: 28px;
}
.acc-ledger-toolbar .select2-container--bootstrap-5 .select2-selection--single {
    padding: 2px 5px;
}
.acc-ledger-toolbar .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    padding-left: 2px;
    line-height: 22px;
    color: #000;
}
.acc-ledger-toolbar .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    height: 26px;
}
.acc-ledger-toolbar .select2-container--bootstrap-5 .select2-selection__clear {
    margin-right: 18px;
    font-size: 14px;
}
.select2-container--bootstrap-5 .select2-dropdown {
    font-size: 12px;
    font-family: 'Tahoma', 'Arial', sans-serif;
    border-color: #999;
    z-index: 3000;
}
.select2-container--bootstrap-5 .select2-search--dropdown {
    display: block !important;
    padding: 6px;
}
.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    display: block !important;
    width: 100% !important;
    min-height: 28px;
    font-size: 12px;
    border: 1px solid #999;
    padding: 4px 6px;
}
.select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: #357ABD !important;
}
@media (max-width: 767.98px) {
  .acc-ledger-form-group--account {
    flex: 1 1 100%;
    max-width: none;
  }
}
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
        <div class="acc-ledger-form-group acc-ledger-form-group--account">
            <label for="accAccountId">Account</label>
            <select id="accAccountId"
                    class="acc-ledger-account-select"
                    data-enhance="false"
                    data-placeholder="Search Account..."
                    aria-label="Search Account">
                <option value="">Search Account...</option>
                <?php
                $preselectedAccount = null;
                if ($accountId > 0) {
                    try {
                        $preselectedAccount = AccountRepository::getById(Database::pdo(), $accountId);
                    } catch (Throwable $e) {
                        $preselectedAccount = null;
                    }
                }
                if (is_array($preselectedAccount)):
                    $preLabel = trim(
                        (string) ($preselectedAccount['account_code'] ?? '')
                        . ' - '
                        . (string) ($preselectedAccount['account_name'] ?? ''),
                        ' -'
                    );
                ?>
                <option value="<?php echo (int) $preselectedAccount['id']; ?>" selected>
                    <?php echo htmlspecialchars($preLabel !== '' ? $preLabel : ('Account #' . $accountId)); ?>
                </option>
                <?php endif; ?>
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
const accPreselectedAccountId = <?php echo (int) $accountId; ?>;
let accLastLedger = null;
let accAccountSelectInitializing = false;

document.addEventListener('DOMContentLoaded', function () {
    accInitAccountSelect();
    accLoadBranches();
});

function accLedgerQuery() {
    const accountId = accGetSelectedAccountId();
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

function accGetSelectedAccountId() {
    const select = document.getElementById('accAccountId');
    if (!select) return '';
    let value = '';
    try {
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery(select).data('select2')) {
            value = jQuery(select).val();
        }
    } catch (e) { /* ignore */ }
    if (value == null || value === '') {
        value = select.value;
    }
    return String(value || '');
}

function accAccountLabel(acc) {
    const code = String((acc && acc.account_code) || '');
    const name = String((acc && acc.account_name) || '');
    const label = (code + ' - ' + name).replace(/^\s*-\s*|\s*-\s*$/g, '').trim();
    return label || ('Account #' + String((acc && acc.id) || ''));
}

function accSyncAccountUrl(accountId) {
    try {
        const url = new URL(window.location.href);
        if (accountId) {
            url.searchParams.set('account_id', String(accountId));
        } else {
            url.searchParams.delete('account_id');
        }
        const fromDate = document.getElementById('accFromDate')?.value;
        const toDate = document.getElementById('accToDate')?.value;
        if (fromDate) url.searchParams.set('from_date', fromDate);
        if (toDate) url.searchParams.set('to_date', toDate);
        // Preserve page/action and any other existing query params
        window.history.replaceState({}, '', url.pathname + '?' + url.searchParams.toString());
    } catch (e) { /* ignore */ }
}

function accBindAccountChange(select) {
    if (!select || select.dataset.accChangeBound === '1') return;
    select.dataset.accChangeBound = '1';

    const onChange = function () {
        if (accAccountSelectInitializing) return;
        const value = accGetSelectedAccountId();
        accSyncAccountUrl(value);
        if (value) {
            accLoadLedger();
        }
    };

    // Prefer jQuery event when Select2 is present (Select2 triggers jQuery change).
    // Otherwise use native change for the HTML select fallback.
    try {
        if (typeof jQuery !== 'undefined') {
            jQuery(select).off('change.accLedgerSelect').on('change.accLedgerSelect', onChange);
            return;
        }
    } catch (e) { /* fall through */ }

    select.addEventListener('change', onChange);
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

/**
 * Always populate the native <select> so Ledger works even if Select2 fails.
 * Returns number of accounts loaded.
 */
async function accLoadAccountsNative() {
    const select = document.getElementById('accAccountId');
    if (!select) return 0;

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=list_accounts');
        const data = await response.json();
        if (!data.ok || !Array.isArray(data.data)) {
            console.error('Ledger: list_accounts failed', data);
            return 0;
        }

        const preferred = String(
            accGetSelectedAccountId() || accPreselectedAccountId || select.value || ''
        );

        select.innerHTML = '<option value="">Search Account...</option>';
        data.data.forEach(function (acc) {
            const id = String(acc.id || '');
            if (!id) return;
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = accAccountLabel(acc);
            if (preferred && id === preferred) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });

        if (preferred) {
            select.value = preferred;
        }
        return data.data.length;
    } catch (error) {
        console.error('Error loading accounts:', error);
        return 0;
    }
}

function accInitSelect2Search(select, useAjax) {
    if (typeof jQuery === 'undefined' || !jQuery.fn.select2 || !select) {
        return false;
    }

    const $el = jQuery(select);
    try {
        if ($el.data('select2')) {
            $el.select2('destroy');
        }
    } catch (e) { /* ignore */ }

    const opts = {
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search Account...',
        allowClear: true,
        dropdownAutoWidth: false,
        // Keep search box always visible
        minimumResultsForSearch: 0,
        // Render outside .acc-module (overflow:hidden) so search/results are visible
        dropdownParent: jQuery(document.body),
    };

    if (useAjax) {
        opts.minimumInputLength = 0;
        opts.ajax = {
            delay: 200,
            cache: true,
            dataType: 'json',
            data: function (params) {
                return {
                    page: 'api_accounting',
                    acc_action: 'search_accounts',
                    q: params.term || '',
                    limit: 50,
                };
            },
            transport: function (params, success, failure) {
                const q = (params.data && params.data.q) || '';
                const url = accBaseUrl
                    + 'index.php?page=api_accounting&acc_action=search_accounts'
                    + '&q=' + encodeURIComponent(q)
                    + '&limit=50';
                fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                    .then(function (res) {
                        if (!res.ok) throw new Error('Account search failed');
                        return res.json();
                    })
                    .then(success)
                    .catch(failure);
            },
            processResults: function (data) {
                const rows = (data && Array.isArray(data.results) && data.results.length)
                    ? data.results
                    : ((data && data.data) || []).map(function (acc) {
                        return { id: String(acc.id), text: accAccountLabel(acc) };
                    });
                return {
                    results: rows.map(function (row) {
                        return { id: String(row.id), text: String(row.text || '') };
                    }),
                };
            },
        };
    }

    try {
        $el.select2(opts);
        // Keep native option in sync when an AJAX result is chosen
        $el.on('select2:select.accLedgerSelect', function (e) {
            const item = e.params && e.params.data ? e.params.data : null;
            if (!item || item.id == null) return;
            const id = String(item.id);
            let opt = select.querySelector('option[value="' + id.replace(/"/g, '\\"') + '"]');
            if (!opt) {
                opt = document.createElement('option');
                opt.value = id;
                opt.textContent = String(item.text || id);
                select.appendChild(opt);
            }
            opt.selected = true;
            select.value = id;
        });
        $el.on('select2:clear.accLedgerSelect', function () {
            select.value = '';
        });
        return true;
    } catch (err) {
        console.warn('Select2 init failed; using native account dropdown.', err);
        try {
            if ($el.data('select2')) $el.select2('destroy');
        } catch (e2) { /* ignore */ }
        return false;
    }
}

function accWhenSelect2Ready(callback) {
    let tries = 0;
    (function wait() {
        if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) {
            callback(true);
            return;
        }
        tries += 1;
        if (tries >= 40) {
            callback(false);
            return;
        }
        setTimeout(wait, 50);
    })();
}

async function accInitAccountSelect() {
    const select = document.getElementById('accAccountId');
    if (!select) return;
    if (select.dataset.accSelectInit === '1') return;
    select.dataset.accSelectInit = '1';
    accAccountSelectInitializing = true;

    // 1) Always fill native <select> first (ledger works even without Select2)
    const count = await accLoadAccountsNative();

    // 2) Wait for Select2 (loaded in footer) then enhance
    await new Promise(function (resolve) {
        accWhenSelect2Ready(function (ready) {
            const useAjax = count > 500;
            const select2Ok = ready ? accInitSelect2Search(select, useAjax) : false;
            accBindAccountChange(select);
            accAccountSelectInitializing = false;

            const selected = accGetSelectedAccountId();
            if (selected) {
                if (select2Ok && typeof jQuery !== 'undefined') {
                    try {
                        jQuery(select).val(selected).trigger('change.select2');
                    } catch (e) {
                        select.value = selected;
                    }
                }
                accLoadLedger();
            }

            if (!select2Ok && count === 0) {
                console.warn('Ledger account dropdown has no options and Select2 is unavailable.');
            }
            resolve();
        });
    });
}

async function accLoadLedger() {
    const accountId = accGetSelectedAccountId();
    if (!accountId) {
        alert('Please select an account');
        return;
    }

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=ledger&' + accLedgerQuery());
        const data = await response.json();

        if (data.ok && data.data) {
            accLastLedger = data.data;
            accSyncAccountUrl(accountId);
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
    const accountId = accGetSelectedAccountId();
    if (!accountId) { alert('Please select an account'); return; }
    window.location.href = accBaseUrl + 'index.php?page=api_accounting&acc_action=export_ledger&format=csv&' + accLedgerQuery();
}

function accExportPdf() {
    const accountId = accGetSelectedAccountId();
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
