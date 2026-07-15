<?php
/**
 * BUSY 17 style voucher entry — Payment / Receipt
 */
$csrf = Helpers::csrfToken();
$baseUrl = Helpers::baseUrl('');
$voucherType = strtoupper((string) ($voucherType ?? 'PAYMENT'));
$paymentMode = strtoupper((string) ($paymentMode ?? ($_GET['payment_mode'] ?? 'CASH')));
$voucherId = (int) ($_GET['vid'] ?? 0);

$titles = [
    'PAYMENT' => 'Add Payment Voucher',
    'RECEIPT' => 'Add Receipt Voucher',
    'JOURNAL' => 'Add Journal Voucher',
    'CONTRA' => 'Add Contra Voucher',
    'TRANSFER' => 'Add Transfer Voucher',
];
$pageTitle = $titles[$voucherType] ?? 'Add Voucher';

$cssPath = __DIR__ . '/../../public/assets/css/busy-voucher.css';
$jsPath = __DIR__ . '/../../public/assets/js/busy-voucher.js';
$cssVer = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
$jsVer = is_file($jsPath) ? (string) filemtime($jsPath) : '1';
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/busy-voucher.css?v=' . rawurlencode($cssVer)); ?>">

<div class="busy-voucher-wrap">
    <div class="busy-voucher">
        <div class="busy-title-bar"><?php echo htmlspecialchars($pageTitle); ?></div>

        <div class="busy-header-panel">
            <div class="busy-header-row">
                <div class="busy-field">
                    <label for="busyVoucherSeries">Voucher Series</label>
                    <select id="busyVoucherSeries">
                        <option value="MAIN" selected>Main</option>
                    </select>
                </div>
                <div class="busy-field">
                    <label for="busyVoucherDate">Date</label>
                    <input type="date" id="busyVoucherDate">
                    <span class="busy-date-display" id="busyDateDisplay"></span>
                </div>
                <div class="busy-field">
                    <label for="busyVoucherNo">Vch No.</label>
                    <input type="text" id="busyVoucherNo" readonly placeholder="Auto">
                </div>
                <div class="busy-field">
                    <label for="busyVoucherTypeField">Type</label>
                    <input type="text" id="busyVoucherTypeField" placeholder="">
                </div>
                <div class="busy-field">
                    <label for="busyPdcDate">PDC Date</label>
                    <input type="date" id="busyPdcDate">
                </div>
                <div class="busy-field">
                    <label for="busyPaymentMode">Payment Mode</label>
                    <select id="busyPaymentMode">
                        <option value="CASH">Cash</option>
                        <option value="BANK">Bank</option>
                        <option value="CHEQUE">Cheque</option>
                        <option value="ONLINE">Online</option>
                        <option value="PETTY_CASH">Petty Cash</option>
                    </select>
                </div>
                <div class="busy-field busy-field-wide">
                    <label for="busyHeaderNarration">Narration</label>
                    <input type="text" id="busyHeaderNarration" placeholder="">
                </div>
            </div>
        </div>

        <div class="busy-grid-panel">
            <div id="busyAlertHost" class="mb-2"></div>
            <table class="busy-grid" id="busyGridTable">
                <thead>
                    <tr>
                        <th class="col-sno">S.No</th>
                        <th class="col-account">Account</th>
                        <th class="col-narr">Description</th>
                        <th class="col-ref">Reference</th>
                        <th class="col-amount">Debit</th>
                        <th class="col-amount">Credit</th>
                        <th class="col-branch">Branch</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="busyGridBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="total-label">Totals</td>
                        <td class="total-amount" id="busyTotalDebit">0.00</td>
                        <td class="total-amount" id="busyTotalCredit">0.00</td>
                        <td class="total-amount" id="busyTotalDiff">0.00</td>
                        <td class="total-status-cell"><span id="busyBalanceStatus" class="busy-status-badge">Entry Total</span></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="busy-status-bar">
            <span class="busy-cur-bal" id="busyCurBal">( Cur. Bal. : <?php echo Helpers::formatMoney(0); ?> )</span>
            <span class="busy-auto-line-panel" id="busyAutoLinePanel">
                <span class="busy-auto-label">Status:</span>
                <span class="busy-auto-text" id="busyAutoLineText">Manual voucher entry — only the lines you enter are saved</span>
            </span>
        </div>

        <div class="busy-bottom-panel">
            <div class="busy-long-narration">
                <label for="busyLongNarration">Long Narration</label>
                <textarea id="busyLongNarration" rows="3"></textarea>
            </div>

            <div class="busy-actions">
                <div class="busy-actions-left">
                    <button type="button" class="busy-btn" disabled title="Coming soon">Vch. Other Detail</button>
                    <button type="button" class="busy-btn" disabled title="Coming soon">Master Other Detail</button>
                    <button type="button" class="busy-btn" disabled title="Coming soon">Party Dash Board</button>
                </div>
                <div class="busy-actions-right">
                    <button type="button" class="busy-btn busy-btn-primary" id="busySaveBtn">Save (F2)</button>
                    <button type="button" class="busy-btn" id="busyQuitBtn">Quit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="busy-search-modal" id="busySearchModal" aria-hidden="true">
    <div class="busy-search-box">
        <div class="busy-search-head">
            <span>Select Account</span>
            <button type="button" id="busySearchClose" aria-label="Close">&times;</button>
        </div>
        <div class="busy-search-input-wrap">
            <input type="text" id="busySearchInput" placeholder="Search account name or code…" autocomplete="off">
        </div>
        <div class="busy-search-results" id="busySearchResults"></div>
    </div>
</div>

<input type="hidden" id="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
<input type="hidden" id="busyVoucherId" value="<?php echo $voucherId; ?>">

<script>
window.BUSY_VOUCHER = {
    baseUrl: <?php echo json_encode($baseUrl, JSON_UNESCAPED_SLASHES); ?>,
    voucherType: <?php echo json_encode($voucherType); ?>,
    paymentMode: <?php echo json_encode($paymentMode); ?>,
    uiVersion: 'simple-single-entry-v1'
};
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/busy-voucher.js?v=' . rawurlencode($jsVer) . '-simple-v2'); ?>"></script>
