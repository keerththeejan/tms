<?php
/**
 * Desktop Accounting Style Voucher List
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
?>
<style>
.acc-list-module {
    background-color: #F5F5DC;
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

.acc-list-header {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
    padding: 4px 8px;
    border-bottom: 2px solid #1A1A1A;
}

.acc-list-header-title {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.acc-list-toolbar {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
    align-items: center;
}

.acc-list-form-group {
    display: flex;
    flex-direction: column;
}

.acc-list-form-group label {
    font-size: 10px;
    font-weight: bold;
    color: #333;
    margin-bottom: 2px;
}

.acc-list-form-group input,
.acc-list-form-group select {
    font-size: 11px;
    padding: 3px 5px;
    border: 1px solid #999;
    background-color: #FFF;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-list-btn {
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

.acc-list-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-list-table-section {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-list-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.acc-list-table thead {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
}

.acc-list-table th {
    padding: 4px 6px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-list-table td {
    padding: 4px 6px;
    border: 1px solid #999;
}

.acc-list-table .text-right {
    text-align: right;
}

.acc-list-table .text-center {
    text-align: center;
}

.acc-list-pagination {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.acc-list-pagination span {
    font-size: 11px;
    font-weight: bold;
}

.acc-list-pagination button {
    font-size: 11px;
    padding: 2px 8px;
    border: 1px solid #999;
    background: #FFF;
    cursor: pointer;
}

.acc-list-pagination button:disabled {
    background: #E0E0E0;
    cursor: not-allowed;
}

.acc-status-draft {
    background-color: #FFA500;
    color: #000;
    padding: 2px 6px;
    font-weight: bold;
    font-size: 10px;
}

.acc-status-posted {
    background-color: #90EE90;
    color: #000;
    padding: 2px 6px;
    font-weight: bold;
    font-size: 10px;
}

.acc-status-cancelled {
    background-color: #FF6347;
    color: #FFF;
    padding: 2px 6px;
    font-weight: bold;
    font-size: 10px;
}
</style>

<div class="acc-list-module">
    <!-- Header -->
    <div class="acc-list-header">
        <span class="acc-list-header-title">
            <i class="bi bi-file-earmark-text"></i> 
            <?php echo htmlspecialchars($voucherType); ?> Vouchers
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-list-toolbar">
        <div class="acc-list-form-group">
            <label>Voucher Type</label>
            <select id="accVoucherTypeFilter" onchange="accChangeVoucherType()">
                <option value="PAYMENT" <?php echo $voucherType === 'PAYMENT' ? 'selected' : ''; ?>>Payment</option>
                <option value="RECEIPT" <?php echo $voucherType === 'RECEIPT' ? 'selected' : ''; ?>>Receipt</option>
                <option value="JOURNAL" <?php echo $voucherType === 'JOURNAL' ? 'selected' : ''; ?>>Journal</option>
                <option value="CONTRA" <?php echo $voucherType === 'CONTRA' ? 'selected' : ''; ?>>Contra</option>
                <option value="TRANSFER" <?php echo $voucherType === 'TRANSFER' ? 'selected' : ''; ?>>Transfer</option>
            </select>
        </div>
        <div class="acc-list-form-group">
            <label>From Date</label>
            <input type="date" id="accFromDate" value="<?php echo htmlspecialchars($accFrom); ?>">
        </div>
        <div class="acc-list-form-group">
            <label>To Date</label>
            <input type="date" id="accToDate" value="<?php echo htmlspecialchars($accTo); ?>">
        </div>
        <div class="acc-list-form-group">
            <label>Status</label>
            <select id="accStatusFilter">
                <option value="">All</option>
                <option value="DRAFT" <?php echo $accStatus === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                <option value="POSTED" <?php echo $accStatus === 'POSTED' ? 'selected' : ''; ?>>Posted</option>
                <option value="CANCELLED" <?php echo $accStatus === 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div class="acc-list-form-group">
            <label>Search</label>
            <input type="text" id="accSearch" value="<?php echo htmlspecialchars($accQuery); ?>" placeholder="Voucher No or Narration">
        </div>
        <button type="button" class="acc-list-btn" onclick="accApplyFilters()">
            Filter
        </button>
        <button type="button" class="acc-list-btn" onclick="accNewVoucher()">
            New Voucher
        </button>
    </div>

    <!-- Table Section -->
    <div class="acc-list-table-section">
        <table class="acc-list-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="width: 100px;">Voucher No</th>
                    <th style="width: 80px;">Date</th>
                    <th style="width: 30%;">Account Summary</th>
                    <th class="text-right" style="width: 100px;">Debit</th>
                    <th class="text-right" style="width: 100px;">Credit</th>
                    <th style="width: 60px;">Status</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accList['rows'])): ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #999;">No vouchers found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($accList['rows'] as $index => $row): ?>
                <tr>
                    <td class="text-center"><?php echo ($accPage - 1) * $accLimit + $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($row['voucher_number'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['voucher_date'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['accounts_summary'] ?? ''); ?></td>
                    <td class="text-right"><?php echo Helpers::formatMoney((float) ($row['total_debit'] ?? 0)); ?></td>
                    <td class="text-right"><?php echo Helpers::formatMoney((float) ($row['total_credit'] ?? 0)); ?></td>
                    <td class="text-center">
                        <span class="acc-status-<?php echo strtolower($row['status'] ?? 'draft'); ?>">
                            <?php echo htmlspecialchars($row['status'] ?? 'DRAFT'); ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="acc-list-btn" style="padding: 2px 6px; font-size: 10px;" onclick="accEditVoucher(<?php echo (int) ($row['id'] ?? 0); ?>)">
                            Edit
                        </button>
                        <button type="button" class="acc-list-btn" style="padding: 2px 6px; font-size: 10px;" onclick="accPrintVoucher(<?php echo (int) ($row['id'] ?? 0); ?>)">
                            Print
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="acc-list-pagination">
        <span>
            Showing <?php echo count($accList['rows']); ?> of <?php echo (int) $accList['total']; ?> vouchers
            (Page <?php echo $accPage; ?> of <?php echo $accList['pages']; ?>)
        </span>
        <div>
            <button type="button" <?php echo $accPage <= 1 ? 'disabled' : ''; ?> onclick="accGoToPage(<?php echo max(1, $accPage - 1); ?>)">
                Previous
            </button>
            <button type="button" <?php echo $accPage >= $accList['pages'] ? 'disabled' : ''; ?> onclick="accGoToPage(<?php echo min($accList['pages'], $accPage + 1); ?>)">
                Next
            </button>
        </div>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';

function accChangeVoucherType() {
    const voucherType = document.getElementById('accVoucherTypeFilter').value;
    window.location.href = accBaseUrl + 'index.php?page=accounting&voucher_type=' + voucherType;
}

function accApplyFilters() {
    const voucherType = document.getElementById('accVoucherTypeFilter').value;
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;
    const status = document.getElementById('accStatusFilter').value;
    const search = document.getElementById('accSearch').value;
    
    window.location.href = accBaseUrl + 'index.php?page=accounting&voucher_type=' + voucherType + 
        '&from_date=' + encodeURIComponent(fromDate) + 
        '&to_date=' + encodeURIComponent(toDate) + 
        '&status=' + encodeURIComponent(status) + 
        '&q=' + encodeURIComponent(search);
}

function accNewVoucher() {
    const voucherType = document.getElementById('accVoucherTypeFilter').value;
    window.location.href = accBaseUrl + 'index.php?page=accounting&action=entry&voucher_type=' + voucherType;
}

function accEditVoucher(id) {
    const voucherType = document.getElementById('accVoucherTypeFilter').value;
    window.location.href = accBaseUrl + 'index.php?page=accounting&action=entry&voucher_type=' + voucherType + '&vid=' + id;
}

function accPrintVoucher(id) {
    window.open(accBaseUrl + 'index.php?page=accounting&action=print&id=' + id, '_blank');
}

function accGoToPage(page) {
    const voucherType = document.getElementById('accVoucherTypeFilter').value;
    const fromDate = document.getElementById('accFromDate').value;
    const toDate = document.getElementById('accToDate').value;
    const status = document.getElementById('accStatusFilter').value;
    const search = document.getElementById('accSearch').value;
    
    window.location.href = accBaseUrl + 'index.php?page=accounting&voucher_type=' + voucherType + 
        '&from_date=' + encodeURIComponent(fromDate) + 
        '&to_date=' + encodeURIComponent(toDate) + 
        '&status=' + encodeURIComponent(status) + 
        '&q=' + encodeURIComponent(search) + 
        '&page_no=' + page;
}
</script>
