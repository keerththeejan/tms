<?php
/**
 * Payment Voucher Entry Module - Professional Enterprise UI
 * 
 * Premium accounting module with:
 * - Professional grid-based entry
 * - Automatic double-entry balancing
 * - Smart search with employee allocation
 * - Real-time validation
 * - Keyboard shortcuts
 * - Industry-standard accounting UX
 */
$csrf = Helpers::csrfToken();
$baseUrl = Helpers::baseUrl('');
$voucherId = (int) ($_GET['vid'] ?? 0);
?>
<style>
/* Payment Voucher Module - Professional Accounting UI */
.pv-module {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 0;
}

.pv-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 1.5rem 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.pv-header h1 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.pv-header .breadcrumb {
    margin-top: 0.5rem;
    opacity: 0.8;
    font-size: 0.875rem;
}

.pv-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 1.5rem;
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

@media (max-width: 1024px) {
    .pv-container {
        grid-template-columns: 1fr;
    }
}

/* Left Panel - Form Entry */
.pv-form-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.08);
    overflow: hidden;
}

.pv-form-section {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.pv-form-section:last-child {
    border-bottom: none;
}

.pv-form-section-title {
    font-size: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #495057;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pv-form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.pv-form-group {
    display: flex;
    flex-direction: column;
}

.pv-form-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.pv-form-group .required {
    color: #dc3545;
    font-weight: 700;
}

.pv-form-group input,
.pv-form-group select,
.pv-form-group textarea {
    padding: 0.625rem 0.875rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9375rem;
    font-family: inherit;
    transition: all 0.2s ease;
    background-color: #fff;
}

.pv-form-group input:focus,
.pv-form-group select:focus,
.pv-form-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    background-color: #fff;
}

.pv-form-group textarea {
    resize: vertical;
    min-height: 60px;
}

/* Grid Section - Line Items */
.pv-grid-section {
    padding: 1.5rem;
    background: #f8f9fa;
}

.pv-grid-title {
    font-size: 1rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pv-grid-toolbar {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.pv-grid-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.pv-grid-btn-primary {
    background-color: #007bff;
    color: white;
}

.pv-grid-btn-primary:hover {
    background-color: #0056b3;
    transform: translateY(-1px);
}

.pv-grid-btn-success {
    background-color: #28a745;
    color: white;
}

.pv-grid-btn-success:hover {
    background-color: #218838;
}

.pv-grid-btn-danger {
    background-color: #dc3545;
    color: white;
}

.pv-grid-btn-danger:hover {
    background-color: #c82333;
}

.pv-grid-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

.pv-grid-table thead {
    background-color: #2c3e50;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.pv-grid-table th {
    padding: 0.875rem;
    text-align: left;
    border: none;
}

.pv-grid-table th.text-right {
    text-align: right;
}

.pv-grid-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.15s ease;
}

.pv-grid-table tbody tr:hover {
    background-color: #f8f9fa;
}

.pv-grid-table tbody tr.editing {
    background-color: #e7f3ff;
}

.pv-grid-table td {
    padding: 0.875rem;
    vertical-align: middle;
    border: none;
}

.pv-grid-table td.text-right {
    text-align: right;
    font-weight: 600;
}

.pv-grid-table input[type="number"],
.pv-grid-table input[type="text"] {
    width: 100%;
    padding: 0.4rem 0.6rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.875rem;
    font-family: 'Monaco', 'Courier New', monospace;
}

.pv-grid-table input[type="number"] {
    text-align: right;
}

.pv-grid-table input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.pv-grid-row-actions {
    display: flex;
    gap: 0.25rem;
}

.pv-grid-row-btn {
    padding: 0.4rem 0.6rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.15s ease;
}

.pv-grid-row-btn-edit {
    background-color: #ffc107;
    color: #000;
}

.pv-grid-row-btn-delete {
    background-color: #dc3545;
    color: white;
}

.pv-grid-row-btn:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

/* Totals Footer */
.pv-grid-totals {
    margin-top: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.pv-total-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-left: 4px solid #007bff;
    background: #f8f9fa;
    border-radius: 4px;
}

.pv-total-item.balance {
    border-left-color: #ffc107;
}

.pv-total-item.balance.warning {
    border-left-color: #dc3545;
    background-color: #ffe5e5;
}

.pv-total-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.pv-total-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2c3e50;
    font-family: 'Monaco', 'Courier New', monospace;
    text-align: right;
}

.pv-total-value.debit {
    color: #dc3545;
}

.pv-total-value.credit {
    color: #28a745;
}

.pv-total-value.balanced {
    color: #28a745;
}

/* Right Panel - Summary & Actions */
.pv-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.pv-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.08);
    overflow: hidden;
}

.pv-card-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 1rem;
    font-weight: 700;
    font-size: 0.9375rem;
}

.pv-card-body {
    padding: 1.25rem;
}

.pv-status-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.pv-status-draft {
    background-color: #e9ecef;
    color: #495057;
}

.pv-status-submitted {
    background-color: #cfe2ff;
    color: #084298;
}

.pv-status-approved {
    background-color: #d1e7dd;
    color: #0f5132;
}

.pv-status-posted {
    background-color: #d1ecf1;
    color: #0c5460;
}

.pv-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.pv-action-btn {
    padding: 0.875rem 1.25rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.pv-action-btn-primary {
    background-color: #007bff;
    color: white;
}

.pv-action-btn-primary:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.pv-action-btn-success {
    background-color: #28a745;
    color: white;
}

.pv-action-btn-success:hover {
    background-color: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.pv-action-btn-warning {
    background-color: #ffc107;
    color: #000;
}

.pv-action-btn-warning:hover {
    background-color: #e0a800;
    transform: translateY(-2px);
}

.pv-action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Smart Search Modal */
.pv-search-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.pv-search-modal.active {
    display: flex;
}

.pv-search-modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.pv-search-modal-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 1.5rem;
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pv-search-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background 0.15s ease;
}

.pv-search-modal-close:hover {
    background: rgba(255,255,255,0.2);
}

.pv-search-tabs {
    display: flex;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    padding: 0.5rem;
    gap: 0.25rem;
}

.pv-search-tab {
    flex: 1;
    padding: 0.75rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.875rem;
    color: #6c757d;
    border-bottom: 2px solid transparent;
    border-radius: 4px 4px 0 0;
    transition: all 0.2s ease;
}

.pv-search-tab.active {
    color: #007bff;
    border-bottom-color: #007bff;
    background: white;
}

.pv-search-input-group {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.pv-search-input {
    width: 100%;
    padding: 0.875rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    font-family: inherit;
}

.pv-search-input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.pv-search-results {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
}

.pv-search-result-item {
    padding: 0.875rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-bottom: 0.25rem;
}

.pv-search-result-item:hover {
    background-color: #e7f3ff;
    transform: translateX(4px);
}

.pv-search-result-item.selected {
    background-color: #d1ecf1;
    border-left: 4px solid #007bff;
}

.pv-search-result-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9375rem;
}

.pv-search-result-code {
    font-size: 0.8125rem;
    color: #6c757d;
    font-family: 'Monaco', 'Courier New', monospace;
}

/* Keyboard Shortcuts Help */
.pv-keyboard-help {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    font-size: 0.8125rem;
}

.pv-shortcut-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pv-shortcut-key {
    background: #e9ecef;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    font-weight: 600;
    font-family: 'Monaco', 'Courier New', monospace;
    min-width: 40px;
    text-align: center;
    border: 1px solid #dee2e6;
}

/* Notifications & Messages */
.pv-alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.pv-alert-success {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.pv-alert-error {
    background-color: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
}

.pv-alert-warning {
    background-color: #fff3cd;
    color: #664d03;
    border: 1px solid #ffecb5;
}

.pv-alert-info {
    background-color: #cfe2ff;
    color: #084298;
    border: 1px solid #b6d4fe;
}

/* Loading & States */
.pv-loading {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007bff;
    border-radius: 50%;
    animation: pv-spin 1s linear infinite;
}

@keyframes pv-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Print Styles */
@media print {
    .pv-sidebar,
    .pv-grid-toolbar,
    .pv-form-section-title {
        display: none;
    }

    .pv-container {
        grid-template-columns: 1fr;
        gap: 0;
        padding: 0;
        max-width: 100%;
    }

    .pv-form-panel {
        box-shadow: none;
        border-radius: 0;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .pv-container {
        padding: 1rem;
    }

    .pv-form-section {
        padding: 1rem;
    }

    .pv-grid-table {
        font-size: 0.8125rem;
    }

    .pv-grid-table th,
    .pv-grid-table td {
        padding: 0.5rem;
    }

    .pv-total-value {
        font-size: 1rem;
    }

    .pv-search-modal-content {
        width: 95%;
        max-height: 90vh;
    }
}
</style>

<div class="pv-module">
    <!-- Header -->
    <div class="pv-header">
        <h1>
            <i class="bi bi-receipt-cutoff"></i>
            Payment Voucher Entry System
        </h1>
        <div class="breadcrumb">
            <span>Accounting → Cash Book → Payment Vouchers</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="pv-container">
        <!-- Left Panel: Form Entry -->
        <div class="pv-form-panel">
            <!-- Voucher Information Section -->
            <div class="pv-form-section">
                <div class="pv-form-section-title">
                    <i class="bi bi-file-text"></i>
                    Voucher Information
                </div>

                <div class="pv-form-row">
                    <div class="pv-form-group">
                        <label>Voucher Number <span class="required">*</span></label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" id="pvVoucherNumber" readonly style="flex: 1; background: #e9ecef; cursor: not-allowed;">
                            <button style="padding: 0.625rem 1rem; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Generate</button>
                        </div>
                    </div>
                    <div class="pv-form-group">
                        <label>Voucher Date <span class="required">*</span></label>
                        <input type="date" id="pvVoucherDate" required>
                    </div>
                </div>

                <div class="pv-form-row">
                    <div class="pv-form-group">
                        <label>Voucher Type <span class="required">*</span></label>
                        <select id="pvVoucherType">
                            <option value="PAYMENT">Payment Voucher</option>
                            <option value="RECEIPT">Receipt Voucher</option>
                            <option value="JOURNAL">Journal Entry</option>
                            <option value="TRANSFER">Transfer</option>
                            <option value="CONTRA">Contra Entry</option>
                        </select>
                    </div>
                    <div class="pv-form-group">
                        <label>Payment Mode <span class="required">*</span></label>
                        <select id="pvPaymentMode">
                            <option value="CASH">Cash</option>
                            <option value="BANK">Bank</option>
                            <option value="CHEQUE">Cheque</option>
                            <option value="ONLINE">Online Transfer</option>
                            <option value="PETTY_CASH">Petty Cash</option>
                        </select>
                    </div>
                </div>

                <!-- Cheque Details (Hidden by default) -->
                <div id="pvChequeSection" style="display: none;">
                    <div class="pv-form-row">
                        <div class="pv-form-group">
                            <label>Cheque Number</label>
                            <input type="text" id="pvChequeNumber">
                        </div>
                        <div class="pv-form-group">
                            <label>Cheque Date</label>
                            <input type="date" id="pvChequeDate">
                        </div>
                        <div class="pv-form-group">
                            <label>Cheque Bank</label>
                            <input type="text" id="pvChequeBank">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Narration Section -->
            <div class="pv-form-section">
                <div class="pv-form-section-title">
                    <i class="bi bi-chat-dots"></i>
                    Details
                </div>

                <div class="pv-form-group">
                    <label>Narration / Description</label>
                    <textarea id="pvNarration" placeholder="Enter transaction description..."></textarea>
                </div>

                <div class="pv-form-row">
                    <div class="pv-form-group">
                        <label>Reference Number</label>
                        <input type="text" id="pvReferenceNumber" placeholder="PO / Bill / Invoice number">
                    </div>
                </div>
            </div>

            <!-- Grid Entry Section -->
            <div class="pv-grid-section">
                <div class="pv-grid-title">
                    <span><i class="bi bi-table"></i> Line Items (Double-Entry)</span>
                    <span id="pvLineCount" style="font-size: 0.875rem; color: #6c757d;">0 items</span>
                </div>

                <div class="pv-grid-toolbar">
                    <button type="button" class="pv-grid-btn pv-grid-btn-primary" id="pvAddRow">
                        <i class="bi bi-plus-circle"></i> Add Row
                    </button>
                    <button type="button" class="pv-grid-btn pv-grid-btn-success" id="pvAutoBalance">
                        <i class="bi bi-calculator"></i> Auto Balance
                    </button>
                    <button type="button" class="pv-grid-btn pv-grid-btn-primary" id="pvEmployeePayment">
                        <i class="bi bi-person-check"></i> Employee Payment
                    </button>
                </div>

                <!-- Grid Table -->
                <div style="overflow-x: auto;">
                    <table class="pv-grid-table" id="pvGridTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 25%;">Account Code</th>
                                <th style="width: 25%;">Account Name</th>
                                <th class="text-right" style="width: 15%;">Debit</th>
                                <th class="text-right" style="width: 15%;">Credit</th>
                                <th style="width: 80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="pvGridBody">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="pv-grid-totals">
                    <div class="pv-total-item">
                        <div class="pv-total-label">Total Debit</div>
                        <div class="pv-total-value debit" id="pvTotalDebit">0.00</div>
                    </div>
                    <div class="pv-total-item">
                        <div class="pv-total-label">Total Credit</div>
                        <div class="pv-total-value credit" id="pvTotalCredit">0.00</div>
                    </div>
                    <div class="pv-total-item balance" id="pvBalanceItem">
                        <div class="pv-total-label">Balance</div>
                        <div class="pv-total-value" id="pvBalance">0.00</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: Summary & Actions -->
        <div class="pv-sidebar">
            <!-- Status Card -->
            <div class="pv-card">
                <div class="pv-card-header">
                    <i class="bi bi-info-circle"></i> Status & Summary
                </div>
                <div class="pv-card-body">
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.8rem; color: #6c757d; margin-bottom: 0.4rem;">Status</div>
                        <span class="pv-status-badge pv-status-draft" id="pvStatusBadge">DRAFT</span>
                    </div>

                    <div style="padding: 1rem 0; border-top: 1px solid #e9ecef; border-bottom: 1px solid #e9ecef;">
                        <div style="display: grid; gap: 0.75rem; font-size: 0.9rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #6c757d;">Voucher Date:</span>
                                <span id="pvSummaryDate" style="font-weight: 600;">-</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #6c757d;">Payment Mode:</span>
                                <span id="pvSummaryMode" style="font-weight: 600;">-</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #6c757d;">Line Items:</span>
                                <span id="pvSummaryLines" style="font-weight: 600;">0</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; padding: 0.875rem; background: #f8f9fa; border-radius: 6px;">
                        <div style="font-size: 0.8rem; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Balance Status</div>
                        <div id="pvBalanceStatus" style="font-size: 1.25rem; font-weight: 700; color: #dc3545;">
                            Not Balanced
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="pv-card">
                <div class="pv-card-header">
                    <i class="bi bi-lightning"></i> Actions
                </div>
                <div class="pv-card-body">
                    <div class="pv-actions">
                        <button type="button" class="pv-action-btn pv-action-btn-primary" id="pvSaveBtn">
                            <i class="bi bi-floppy"></i> Save Draft (F2)
                        </button>
                        <button type="button" class="pv-action-btn pv-action-btn-success" id="pvSubmitBtn" disabled>
                            <i class="bi bi-check-circle"></i> Submit for Approval
                        </button>
                        <button type="button" class="pv-action-btn pv-action-btn-primary" id="pvPreviewBtn">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                        <button type="button" class="pv-action-btn" style="background: #6c757d; color: white;" id="pvCancelBtn">
                            <i class="bi bi-x-circle"></i> Cancel Entry
                        </button>
                    </div>
                </div>
            </div>

            <!-- Keyboard Shortcuts Card -->
            <div class="pv-card">
                <div class="pv-card-header">
                    <i class="bi bi-keyboard"></i> Keyboard Shortcuts
                </div>
                <div class="pv-card-body">
                    <div class="pv-keyboard-help">
                        <div class="pv-shortcut-item">
                            <span class="pv-shortcut-key">F2</span>
                            <span>Save Draft</span>
                        </div>
                        <div class="pv-shortcut-item">
                            <span class="pv-shortcut-key">F3</span>
                            <span>Search Account</span>
                        </div>
                        <div class="pv-shortcut-item">
                            <span class="pv-shortcut-key">F4</span>
                            <span>Add Narration</span>
                        </div>
                        <div class="pv-shortcut-item">
                            <span class="pv-shortcut-key">F6</span>
                            <span>Voucher Type</span>
                        </div>
                        <div class="pv-shortcut-item">
                            <span class="pv-shortcut-key">F8</span>
                            <span>Delete Row</span>
                        </div>
                        <div class="pv-shortcut-item">
                            <span class="pv-shortcut-key">Ctrl+↩</span>
                            <span>Add Row</span>
                        </div>
                        <div class="pv-shortcut-item">
                            <span class="pv-shortcut-key">↑↓</span>
                            <span>Navigate Rows</span>
                        </div>
                        <div class="pv-shortcut-item">
                            <span class="pv-shortcut-key">Tab</span>
                            <span>Next Field</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Search Modal -->
    <div class="pv-search-modal" id="pvSearchModal">
        <div class="pv-search-modal-content">
            <div class="pv-search-modal-header">
                Search Account
                <button type="button" class="pv-search-modal-close" id="pvSearchClose">×</button>
            </div>

            <div class="pv-search-tabs">
                <button type="button" class="pv-search-tab active" data-tab="accounts">Accounts</button>
                <button type="button" class="pv-search-tab" data-tab="employees">Employees</button>
                <button type="button" class="pv-search-tab" data-tab="customers">Customers</button>
            </div>

            <div class="pv-search-input-group">
                <input type="text" class="pv-search-input" id="pvSearchInput" placeholder="Search by name, code, or mobile...">
            </div>

            <div class="pv-search-results" id="pvSearchResults"></div>
        </div>
    </div>
</div>

<!-- CSRF Token -->
<input type="hidden" id="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
