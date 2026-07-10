<?php
/**
 * Transfer Voucher Entry Module - Traditional Desktop Accounting UI
 * 
 * Busy/Tally/Marg ERP style grid-based entry with:
 * - Full-page accounting grid
 * - Keyboard-friendly navigation
 * - Auto row creation
 * - Real-time balance validation
 * - Double-entry transactions
 */
$csrf = Helpers::csrfToken();
$baseUrl = Helpers::baseUrl('');
$voucherId = (int) ($_GET['vid'] ?? 0);
?>
<style>
/* Transfer Voucher Module - Traditional Accounting UI */
.tv-module {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 0;
}

.tv-header {
    background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
    color: white;
    padding: 1.5rem 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.tv-header h1 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.tv-header .breadcrumb {
    margin-top: 0.5rem;
    opacity: 0.8;
    font-size: 0.875rem;
}

.tv-container {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.5rem;
    padding: 2rem;
    max-width: 1600px;
    margin: 0 auto;
}

@media (max-width: 1200px) {
    .tv-container {
        grid-template-columns: 1fr;
    }
}

/* Left Panel - Form Entry */
.tv-form-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.08);
    overflow: hidden;
}

.tv-form-section {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.tv-form-section:last-child {
    border-bottom: none;
}

.tv-form-section-title {
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

.tv-form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.tv-form-group {
    display: flex;
    flex-direction: column;
}

.tv-form-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1a365d;
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.tv-form-group .required {
    color: #dc3545;
    font-weight: 700;
}

.tv-form-group input,
.tv-form-group select,
.tv-form-group textarea {
    padding: 0.625rem 0.875rem;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-size: 0.9375rem;
    font-family: inherit;
    transition: all 0.2s ease;
    background-color: #fff;
}

.tv-form-group input:focus,
.tv-form-group select:focus,
.tv-form-group textarea:focus {
    outline: none;
    border-color: #3182ce;
    box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
    background-color: #fff;
}

.tv-form-group input[readonly] {
    background-color: #f7fafc;
    cursor: not-allowed;
}

.tv-form-group textarea {
    resize: vertical;
    min-height: 60px;
}

/* Grid Section - Line Items */
.tv-grid-section {
    padding: 1.5rem;
    background: #f8f9fa;
}

.tv-grid-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1a365d;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tv-grid-toolbar {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.tv-grid-btn {
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

.tv-grid-btn-primary {
    background-color: #3182ce;
    color: white;
}

.tv-grid-btn-primary:hover {
    background-color: #2c5282;
    transform: translateY(-1px);
}

.tv-grid-btn-success {
    background-color: #38a169;
    color: white;
}

.tv-grid-btn-success:hover {
    background-color: #2f855a;
}

.tv-grid-btn-danger {
    background-color: #e53e3e;
    color: white;
}

.tv-grid-btn-danger:hover {
    background-color: #c53030;
}

.tv-grid-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

.tv-grid-table thead {
    background-color: #1a365d;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.tv-grid-table th {
    padding: 0.875rem;
    text-align: left;
    border: none;
}

.tv-grid-table th.text-right {
    text-align: right;
}

.tv-grid-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.15s ease;
}

.tv-grid-table tbody tr:hover {
    background-color: #f8f9fa;
}

.tv-grid-table tbody tr.editing {
    background-color: #ebf8ff;
}

.tv-grid-table td {
    padding: 0.875rem;
    vertical-align: middle;
    border: none;
}

.tv-grid-table td.text-right {
    text-align: right;
    font-weight: 600;
}

.tv-grid-table input[type="number"],
.tv-grid-table input[type="text"] {
    width: 100%;
    padding: 0.4rem 0.6rem;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    font-size: 0.875rem;
    font-family: 'Monaco', 'Courier New', monospace;
}

.tv-grid-table input[type="number"] {
    text-align: right;
}

.tv-grid-table input:focus {
    outline: none;
    border-color: #3182ce;
    box-shadow: 0 0 0 2px rgba(49, 130, 206, 0.1);
}

.tv-grid-row-actions {
    display: flex;
    gap: 0.25rem;
}

.tv-grid-row-btn {
    padding: 0.4rem 0.6rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.15s ease;
}

.tv-grid-row-btn-edit {
    background-color: #ecc94b;
    color: #000;
}

.tv-grid-row-btn-delete {
    background-color: #e53e3e;
    color: white;
}

.tv-grid-row-btn:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

/* Totals Footer */
.tv-grid-totals {
    margin-top: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.tv-total-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-left: 4px solid #3182ce;
    background: #f8f9fa;
    border-radius: 4px;
}

.tv-total-item.balance {
    border-left-color: #ecc94b;
}

.tv-total-item.balance.warning {
    border-left-color: #e53e3e;
    background-color: #fff5f5;
}

.tv-total-item.balance.success {
    border-left-color: #38a169;
    background-color: #f0fff4;
}

.tv-total-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.tv-total-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a365d;
    font-family: 'Monaco', 'Courier New', monospace;
    text-align: right;
}

.tv-total-value.debit {
    color: #e53e3e;
}

.tv-total-value.credit {
    color: #38a169;
}

.tv-total-value.balanced {
    color: #38a169;
}

/* Right Panel - Summary & Actions */
.tv-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.tv-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.08);
    overflow: hidden;
}

.tv-card-header {
    background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
    color: white;
    padding: 1rem;
    font-weight: 700;
    font-size: 0.9375rem;
}

.tv-card-body {
    padding: 1.25rem;
}

.tv-status-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.tv-status-draft {
    background-color: #e9ecef;
    color: #495057;
}

.tv-status-submitted {
    background-color: #bee3f8;
    color: #2c5282;
}

.tv-status-approved {
    background-color: #c6f6d5;
    color: #22543d;
}

.tv-status-posted {
    background-color: #b2f5ea;
    color: #134e4a;
}

.tv-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.tv-action-btn {
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

.tv-action-btn-primary {
    background-color: #3182ce;
    color: white;
}

.tv-action-btn-primary:hover {
    background-color: #2c5282;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(49, 130, 206, 0.3);
}

.tv-action-btn-success {
    background-color: #38a169;
    color: white;
}

.tv-action-btn-success:hover {
    background-color: #2f855a;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(56, 161, 105, 0.3);
}

.tv-action-btn-warning {
    background-color: #ecc94b;
    color: #000;
}

.tv-action-btn-warning:hover {
    background-color: #d69e2e;
    transform: translateY(-2px);
}

.tv-action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Smart Search Modal */
.tv-search-modal {
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

.tv-search-modal.active {
    display: flex;
}

.tv-search-modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 700px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.tv-search-modal-header {
    background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
    color: white;
    padding: 1.5rem;
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tv-search-modal-close {
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

.tv-search-modal-close:hover {
    background: rgba(255,255,255,0.2);
}

.tv-search-input-group {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.tv-search-input {
    width: 100%;
    padding: 0.875rem;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-size: 1rem;
    font-family: inherit;
}

.tv-search-input:focus {
    outline: none;
    border-color: #3182ce;
    box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
}

.tv-search-results {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
}

.tv-search-result-item {
    padding: 0.875rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-bottom: 0.25rem;
}

.tv-search-result-item:hover {
    background-color: #ebf8ff;
    transform: translateX(4px);
}

.tv-search-result-item.selected {
    background-color: #bee3f8;
    border-left: 4px solid #3182ce;
}

.tv-search-result-name {
    font-weight: 600;
    color: #1a365d;
    font-size: 0.9375rem;
}

.tv-search-result-code {
    font-size: 0.8125rem;
    color: #6c757d;
    font-family: 'Monaco', 'Courier New', monospace;
}

.tv-search-result-balance {
    font-size: 0.8125rem;
    color: #38a169;
    font-weight: 600;
}

/* Keyboard Shortcuts Help */
.tv-keyboard-help {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    font-size: 0.8125rem;
}

.tv-shortcut-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tv-shortcut-key {
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
.tv-alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.tv-alert-success {
    background-color: #c6f6d5;
    color: #22543d;
    border: 1px solid #9ae6b4;
}

.tv-alert-error {
    background-color: #fed7d7;
    color: #742a2a;
    border: 1px solid #fc8181;
}

.tv-alert-warning {
    background-color: #fefcbf;
    color: #744210;
    border: 1px solid #f6e05e;
}

.tv-alert-info {
    background-color: #bee3f8;
    color: #2c5282;
    border: 1px solid #90cdf4;
}

/* Loading & States */
.tv-loading {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3182ce;
    border-radius: 50%;
    animation: tv-spin 1s linear infinite;
}

@keyframes tv-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Print Styles */
@media print {
    .tv-sidebar,
    .tv-grid-toolbar,
    .tv-form-section-title {
        display: none;
    }

    .tv-container {
        grid-template-columns: 1fr;
        gap: 0;
        padding: 0;
        max-width: 100%;
    }

    .tv-form-panel {
        box-shadow: none;
        border-radius: 0;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .tv-container {
        padding: 1rem;
    }

    .tv-form-section {
        padding: 1rem;
    }

    .tv-grid-table {
        font-size: 0.8125rem;
    }

    .tv-grid-table th,
    .tv-grid-table td {
        padding: 0.5rem;
    }

    .tv-total-value {
        font-size: 1rem;
    }

    .tv-search-modal-content {
        width: 95%;
        max-height: 90vh;
    }
}
</style>

<?php
$reportTitle = 'Transfer Voucher';
include __DIR__ . '/../partials/report/embed_block.php';
?>

<div class="tv-module">
    <!-- Header -->
    <div class="tv-header">
        <h1>
            <i class="bi bi-arrow-left-right"></i>
            Transfer Voucher Entry
        </h1>
        <div class="breadcrumb">
            <span>Accounting → Cash Book → Transfer Vouchers → Entry</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="tv-container">
        <!-- Left Panel: Form Entry -->
        <div class="tv-form-panel">
            <!-- Voucher Information Section -->
            <div class="tv-form-section">
                <div class="tv-form-section-title">
                    <i class="bi bi-file-text"></i>
                    Voucher Information
                </div>

                <div class="tv-form-row">
                    <div class="tv-form-group">
                        <label>Voucher Number <span class="required">*</span></label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" id="tvVoucherNumber" readonly style="flex: 1; background: #f7fafc; cursor: not-allowed;">
                        </div>
                    </div>
                    <div class="tv-form-group">
                        <label>Voucher Date <span class="required">*</span></label>
                        <input type="date" id="tvVoucherDate" required>
                    </div>
                </div>

                <div class="tv-form-row">
                    <div class="tv-form-group">
                        <label>Payment Mode <span class="required">*</span></label>
                        <select id="tvPaymentMode">
                            <option value="CASH">Cash</option>
                            <option value="BANK">Bank Transfer</option>
                            <option value="ONLINE">Online Transfer</option>
                            <option value="CHEQUE">Cheque</option>
                        </select>
                    </div>
                    <div class="tv-form-group">
                        <label>Reference Number</label>
                        <input type="text" id="tvReferenceNumber" placeholder="Optional reference">
                    </div>
                </div>
            </div>

            <!-- Narration Section -->
            <div class="tv-form-section">
                <div class="tv-form-section-title">
                    <i class="bi bi-chat-dots"></i>
                    Details
                </div>

                <div class="tv-form-group">
                    <label>Narration / Description</label>
                    <textarea id="tvNarration" placeholder="Enter transfer description..."></textarea>
                </div>
            </div>

            <!-- Grid Entry Section -->
            <div class="tv-grid-section">
                <div class="tv-grid-title">
                    <span><i class="bi bi-table"></i> Line Items (Double-Entry Transfer)</span>
                    <span id="tvLineCount" style="font-size: 0.875rem; color: #6c757d;">0 items</span>
                </div>

                <div class="tv-grid-toolbar">
                    <button type="button" class="tv-grid-btn tv-grid-btn-primary" id="tvAddRow">
                        <i class="bi bi-plus-circle"></i> Add Row (Ctrl+Enter)
                    </button>
                    <button type="button" class="tv-grid-btn tv-grid-btn-success" id="tvAutoBalance">
                        <i class="bi bi-calculator"></i> Auto Balance
                    </button>
                    <button type="button" class="tv-grid-btn tv-grid-btn-primary" id="tvSearchAccount">
                        <i class="bi bi-search"></i> Search Account (F3)
                    </button>
                </div>

                <!-- Grid Table -->
                <div style="overflow-x: auto;">
                    <table class="tv-grid-table" id="tvGridTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 30%;">Account Name</th>
                                <th class="text-right" style="width: 20%;">Debit</th>
                                <th class="text-right" style="width: 20%;">Credit</th>
                                <th style="width: 25%;">Short Narration</th>
                                <th style="width: 80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tvGridBody">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="tv-grid-totals">
                    <div class="tv-total-item">
                        <div class="tv-total-label">Total Debit</div>
                        <div class="tv-total-value debit" id="tvTotalDebit">0.00</div>
                    </div>
                    <div class="tv-total-item">
                        <div class="tv-total-label">Total Credit</div>
                        <div class="tv-total-value credit" id="tvTotalCredit">0.00</div>
                    </div>
                    <div class="tv-total-item balance" id="tvBalanceItem">
                        <div class="tv-total-label">Difference</div>
                        <div class="tv-total-value" id="tvBalance">0.00</div>
                    </div>
                    <div class="tv-total-item balance" id="tvStatusItem">
                        <div class="tv-total-label">Status</div>
                        <div class="tv-total-value" id="tvBalanceStatus">Not Balanced</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: Summary & Actions -->
        <div class="tv-sidebar">
            <!-- Status Card -->
            <div class="tv-card">
                <div class="tv-card-header">
                    <i class="bi bi-info-circle"></i> Status & Summary
                </div>
                <div class="tv-card-body">
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.8rem; color: #6c757d; margin-bottom: 0.4rem;">Status</div>
                        <span class="tv-status-badge tv-status-draft" id="tvStatusBadge">DRAFT</span>
                    </div>

                    <div style="padding: 1rem 0; border-top: 1px solid #e9ecef; border-bottom: 1px solid #e9ecef;">
                        <div style="display: grid; gap: 0.75rem; font-size: 0.9rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #6c757d;">Voucher Date:</span>
                                <span id="tvSummaryDate" style="font-weight: 600;">-</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #6c757d;">Payment Mode:</span>
                                <span id="tvSummaryMode" style="font-weight: 600;">-</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #6c757d;">Line Items:</span>
                                <span id="tvSummaryLines" style="font-weight: 600;">0</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; padding: 0.875rem; background: #f8f9fa; border-radius: 6px;">
                        <div style="font-size: 0.8rem; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Balance Status</div>
                        <div id="tvBalanceStatusLarge" style="font-size: 1.25rem; font-weight: 700; color: #e53e3e;">
                            Not Balanced
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="tv-card">
                <div class="tv-card-header">
                    <i class="bi bi-lightning"></i> Actions
                </div>
                <div class="tv-card-body">
                    <div class="tv-actions">
                        <button type="button" class="tv-action-btn tv-action-btn-primary" id="tvSaveBtn">
                            <i class="bi bi-floppy"></i> Save Draft (F2)
                        </button>
                        <button type="button" class="tv-action-btn tv-action-btn-success" id="tvPostBtn" disabled>
                            <i class="bi bi-check-circle"></i> Post Voucher
                        </button>
                        <button type="button" class="tv-action-btn" style="background: #718096; color: white;" id="tvPreviewBtn">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                        <button type="button" class="tv-action-btn" style="background: #e53e3e; color: white;" id="tvCancelBtn">
                            <i class="bi bi-x-circle"></i> Cancel Entry
                        </button>
                    </div>
                </div>
            </div>

            <!-- Keyboard Shortcuts Card -->
            <div class="tv-card">
                <div class="tv-card-header">
                    <i class="bi bi-keyboard"></i> Keyboard Shortcuts
                </div>
                <div class="tv-card-body">
                    <div class="tv-keyboard-help">
                        <div class="tv-shortcut-item">
                            <span class="tv-shortcut-key">F2</span>
                            <span>Save Draft</span>
                        </div>
                        <div class="tv-shortcut-item">
                            <span class="tv-shortcut-key">F3</span>
                            <span>Search Account</span>
                        </div>
                        <div class="tv-shortcut-item">
                            <span class="tv-shortcut-key">F4</span>
                            <span>Add Narration</span>
                        </div>
                        <div class="tv-shortcut-item">
                            <span class="tv-shortcut-key">F8</span>
                            <span>Delete Row</span>
                        </div>
                        <div class="tv-shortcut-item">
                            <span class="tv-shortcut-key">Ctrl+↩</span>
                            <span>Add Row</span>
                        </div>
                        <div class="tv-shortcut-item">
                            <span class="tv-shortcut-key">↑↓</span>
                            <span>Navigate Rows</span>
                        </div>
                        <div class="tv-shortcut-item">
                            <span class="tv-shortcut-key">Tab</span>
                            <span>Next Field</span>
                        </div>
                        <div class="tv-shortcut-item">
                            <span class="tv-shortcut-key">Esc</span>
                            <span>Close Modal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Search Modal -->
    <div class="tv-search-modal" id="tvSearchModal">
        <div class="tv-search-modal-content">
            <div class="tv-search-modal-header">
                Search Cashbook Account
                <button type="button" class="tv-search-modal-close" id="tvSearchClose">×</button>
            </div>

            <div class="tv-search-input-group">
                <input type="text" class="tv-search-input" id="tvSearchInput" placeholder="Search by account name or type...">
            </div>

            <div class="tv-search-results" id="tvSearchResults"></div>
        </div>
    </div>
</div>

<!-- CSRF Token -->
<input type="hidden" id="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
<input type="hidden" id="tvVoucherId" value="<?php echo $voucherId; ?>">

<script>
const tvBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';
let tvRowCount = 0;
let tvCurrentRow = 0;
let tvAccounts = [];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    tvInitializeVoucher();
    tvLoadAccounts();
    tvSetupKeyboardShortcuts();
    tvAddInitialRows();
    tvLoadExistingVoucher();
});

function tvInitializeVoucher() {
    const voucherId = document.getElementById('tvVoucherId').value;
    if (!voucherId || voucherId === '0') {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tvVoucherDate').value = today;
        document.getElementById('tvVoucherNumber').value = tvGenerateVoucherNumber();
        tvUpdateSummary();
    }
}

function tvGenerateVoucherNumber() {
    const year = new Date().getFullYear();
    const sequence = String(Math.floor(Math.random() * 900000) + 100000);
    return `TRF-${year}-${sequence}`;
}

async function tvLoadAccounts() {
    try {
        const response = await fetch(tvBaseUrl + 'index.php?page=api_cashbook&cb_action=accounts&for_ops=1');
        const data = await response.json();
        if (data.ok && data.accounts) {
            tvAccounts = data.accounts;
        }
    } catch (error) {
        console.error('Error loading accounts:', error);
    }
}

async function tvLoadExistingVoucher() {
    const voucherId = document.getElementById('tvVoucherId').value;
    if (!voucherId || voucherId === '0') {
        return;
    }

    try {
        const response = await fetch(tvBaseUrl + 'index.php?page=api_transfer_voucher', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tv_action: 'get_voucher',
                csrf_token: document.getElementById('csrf').value,
                id: voucherId
            })
        });

        const data = await response.json();
        if (data.ok && data.data) {
            tvPopulateVoucher(data.data);
        }
    } catch (error) {
        console.error('Error loading voucher:', error);
    }
}

function tvPopulateVoucher(voucher) {
    document.getElementById('tvVoucherNumber').value = voucher.voucher_number || voucher.voucher_no || '';
    document.getElementById('tvVoucherDate').value = voucher.voucher_date || '';
    document.getElementById('tvPaymentMode').value = voucher.payment_mode || 'CASH';
    document.getElementById('tvReferenceNumber').value = voucher.reference_number || '';
    document.getElementById('tvNarration').value = voucher.narration || '';

    const tbody = document.getElementById('tvGridBody');
    tbody.innerHTML = '';
    tvRowCount = 0;

    const items = voucher.items || [];
    if (items.length > 0) {
        items.forEach(item => {
            tvAddRow();
            const rows = document.querySelectorAll('#tvGridBody tr');
            const lastRow = rows[rows.length - 1];
            lastRow.querySelector('.tv-account-input').value = item.account_name || '';
            lastRow.querySelector('.tv-account-id').value = item.ledger_account_id || '';
            lastRow.querySelector('.tv-debit-input').value = item.debit_amount || 0;
            lastRow.querySelector('.tv-credit-input').value = item.credit_amount || 0;
            lastRow.querySelector('.tv-narration-input').value = item.description || '';
        });
    } else {
        tvAddInitialRows();
    }

    tvUpdateSummary();
    tvCalculateTotals();

    const status = (voucher.status || 'DRAFT').toUpperCase();
    const badge = document.getElementById('tvStatusBadge');
    badge.textContent = status;
    badge.className = 'tv-status-badge tv-status-' + status.toLowerCase();
}

function tvAddInitialRows() {
    tvAddRow();
    tvAddRow();
}

function tvAddRow() {
    tvRowCount++;
    const tbody = document.getElementById('tvGridBody');
    const row = document.createElement('tr');
    row.id = `tvRow${tvRowCount}`;
    row.innerHTML = `
        <td>${tvRowCount}</td>
        <td>
            <input type="text" class="tv-account-input" placeholder="Select account..." onfocus="tvShowAccountSearch(this)">
            <input type="hidden" class="tv-account-id">
        </td>
        <td>
            <input type="number" class="tv-debit-input" step="0.01" min="0" oninput="tvCalculateTotals()">
        </td>
        <td>
            <input type="number" class="tv-credit-input" step="0.01" min="0" oninput="tvCalculateTotals()">
        </td>
        <td>
            <input type="text" class="tv-narration-input" placeholder="Short narration...">
        </td>
        <td>
            <div class="tv-grid-row-actions">
                <button type="button" class="tv-grid-row-btn tv-grid-row-btn-delete" onclick="tvDeleteRow(${tvRowCount})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    `;
    tbody.appendChild(row);
    tvUpdateLineCount();
    tvCalculateTotals();
}

function tvDeleteRow(rowId) {
    const row = document.getElementById(`tvRow${rowId}`);
    if (row) {
        row.remove();
        tvUpdateLineCount();
        tvCalculateTotals();
    }
}

function tvUpdateLineCount() {
    const count = document.querySelectorAll('#tvGridBody tr').length;
    document.getElementById('tvLineCount').textContent = `${count} items`;
    document.getElementById('tvSummaryLines').textContent = count;
}

function tvCalculateTotals() {
    let totalDebit = 0;
    let totalCredit = 0;

    document.querySelectorAll('#tvGridBody tr').forEach(row => {
        const debit = parseFloat(row.querySelector('.tv-debit-input').value) || 0;
        const credit = parseFloat(row.querySelector('.tv-credit-input').value) || 0;
        totalDebit += debit;
        totalCredit += credit;
    });

    const difference = totalDebit - totalCredit;
    const isBalanced = Math.abs(difference) < 0.01;

    document.getElementById('tvTotalDebit').textContent = totalDebit.toFixed(2);
    document.getElementById('tvTotalCredit').textContent = totalCredit.toFixed(2);
    document.getElementById('tvBalance').textContent = difference.toFixed(2);

    const balanceItem = document.getElementById('tvBalanceItem');
    const statusItem = document.getElementById('tvStatusItem');
    const balanceStatus = document.getElementById('tvBalanceStatus');
    const balanceStatusLarge = document.getElementById('tvBalanceStatusLarge');
    const postBtn = document.getElementById('tvPostBtn');

    if (isBalanced && totalDebit > 0) {
        balanceItem.className = 'tv-total-item balance success';
        statusItem.className = 'tv-total-item balance success';
        balanceStatus.textContent = 'Balanced';
        balanceStatusLarge.textContent = 'Balanced';
        balanceStatusLarge.style.color = '#38a169';
        postBtn.disabled = false;
    } else {
        balanceItem.className = 'tv-total-item balance warning';
        statusItem.className = 'tv-total-item balance warning';
        balanceStatus.textContent = 'Not Balanced';
        balanceStatusLarge.textContent = 'Not Balanced';
        balanceStatusLarge.style.color = '#e53e3e';
        postBtn.disabled = true;
    }
}

function tvUpdateSummary() {
    document.getElementById('tvSummaryDate').textContent = document.getElementById('tvVoucherDate').value;
    document.getElementById('tvSummaryMode').textContent = document.getElementById('tvPaymentMode').value;
}

function tvShowAccountSearch(input) {
    const modal = document.getElementById('tvSearchModal');
    modal.classList.add('active');
    document.getElementById('tvSearchInput').focus();
    tvCurrentRow = input.closest('tr').id.replace('tvRow', '');
    tvSearchAccounts('');
}

async function tvSearchAccounts(query) {
    const resultsDiv = document.getElementById('tvSearchResults');
    const filtered = tvAccounts.filter(acc => 
        acc.name.toLowerCase().includes(query.toLowerCase()) ||
        acc.type.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 20);

    if (filtered.length === 0) {
        resultsDiv.innerHTML = '<div style="padding: 1rem; text-align: center; color: #6c757d;">No accounts found</div>';
        return;
    }

    resultsDiv.innerHTML = filtered.map(acc => `
        <div class="tv-search-result-item" onclick="tvSelectAccount(${acc.id}, '${acc.name.replace(/'/g, "\\'")}')">
            <div class="tv-search-result-name">${acc.name}</div>
            <div class="tv-search-result-code">Type: ${acc.type} | Balance: ${parseFloat(acc.balance || 0).toFixed(2)}</div>
        </div>
    `).join('');
}

function tvSelectAccount(accountId, accountName) {
    const row = document.getElementById(`tvRow${tvCurrentRow}`);
    if (row) {
        row.querySelector('.tv-account-input').value = accountName;
        row.querySelector('.tv-account-id').value = accountId;
    }
    tvCloseSearchModal();
}

function tvCloseSearchModal() {
    document.getElementById('tvSearchModal').classList.remove('active');
}

function tvSetupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // F2 - Save Draft
        if (e.key === 'F2') {
            e.preventDefault();
            tvSaveDraft();
        }
        // F3 - Search Account
        if (e.key === 'F3') {
            e.preventDefault();
            document.getElementById('tvSearchModal').classList.add('active');
            document.getElementById('tvSearchInput').focus();
        }
        // F8 - Delete Row
        if (e.key === 'F8') {
            e.preventDefault();
            if (tvCurrentRow > 0) {
                tvDeleteRow(tvCurrentRow);
            }
        }
        // Ctrl+Enter - Add Row
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            tvAddRow();
        }
        // Escape - Close Modal
        if (e.key === 'Escape') {
            tvCloseSearchModal();
        }
    });

    // Search input
    document.getElementById('tvSearchInput').addEventListener('input', function(e) {
        tvSearchAccounts(e.target.value);
    });

    // Close button
    document.getElementById('tvSearchClose').addEventListener('click', tvCloseSearchModal);

    // Add row button
    document.getElementById('tvAddRow').addEventListener('click', tvAddRow);

    // Auto balance button
    document.getElementById('tvAutoBalance').addEventListener('click', tvAutoBalance);

    // Search account button
    document.getElementById('tvSearchAccount').addEventListener('click', function() {
        document.getElementById('tvSearchModal').classList.add('active');
        document.getElementById('tvSearchInput').focus();
    });

    // Save button
    document.getElementById('tvSaveBtn').addEventListener('click', tvSaveDraft);

    // Post button
    document.getElementById('tvPostBtn').addEventListener('click', tvPostVoucher);

    // Cancel button
    document.getElementById('tvCancelBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to cancel this entry?')) {
            window.location.href = tvBaseUrl + 'index.php?page=transfer_voucher';
        }
    });

    // Date change
    document.getElementById('tvVoucherDate').addEventListener('change', tvUpdateSummary);
    document.getElementById('tvPaymentMode').addEventListener('change', tvUpdateSummary);
}

function tvAutoBalance() {
    let totalDebit = 0;
    let totalCredit = 0;

    document.querySelectorAll('#tvGridBody tr').forEach(row => {
        totalDebit += parseFloat(row.querySelector('.tv-debit-input').value) || 0;
        totalCredit += parseFloat(row.querySelector('.tv-credit-input').value) || 0;
    });

    const difference = totalDebit - totalCredit;
    
    if (Math.abs(difference) < 0.01) {
        alert('Voucher is already balanced!');
        return;
    }

    // Add balancing row
    tvAddRow();
    const rows = document.querySelectorAll('#tvGridBody tr');
    const lastRow = rows[rows.length - 1];

    if (difference > 0) {
        // Debit is higher, add credit
        lastRow.querySelector('.tv-credit-input').value = difference.toFixed(2);
        lastRow.querySelector('.tv-account-input').value = 'Balance Account';
        lastRow.querySelector('.tv-narration-input').value = 'Auto-balanced entry';
    } else {
        // Credit is higher, add debit
        lastRow.querySelector('.tv-debit-input').value = Math.abs(difference).toFixed(2);
        lastRow.querySelector('.tv-account-input').value = 'Balance Account';
        lastRow.querySelector('.tv-narration-input').value = 'Auto-balanced entry';
    }

    tvCalculateTotals();
}

async function tvSaveDraft() {
    const voucherData = tvCollectVoucherData();
    
    try {
        const response = await fetch(tvBaseUrl + 'index.php?page=api_transfer_voucher', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tv_action: 'save_voucher',
                csrf_token: document.getElementById('csrf').value,
                ...voucherData
            })
        });

        const data = await response.json();
        if (data.ok) {
            alert('Draft saved successfully!');
            document.getElementById('tvVoucherId').value = data.data.id;
            document.getElementById('tvVoucherNumber').value = data.data.voucher_no;
        } else {
            alert('Error saving draft: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error saving draft: ' + error.message);
    }
}

async function tvPostVoucher() {
    const voucherData = tvCollectVoucherData();
    
    try {
        const response = await fetch(tvBaseUrl + 'index.php?page=api_transfer_voucher', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tv_action: 'post_voucher',
                csrf_token: document.getElementById('csrf').value,
                ...voucherData
            })
        });

        const data = await response.json();
        if (data.ok) {
            alert('Voucher posted successfully!');
            window.location.href = tvBaseUrl + 'index.php?page=transfer_voucher';
        } else {
            alert('Error posting voucher: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error posting voucher: ' + error.message);
    }
}

function tvCollectVoucherData() {
    const items = [];
    document.querySelectorAll('#tvGridBody tr').forEach(row => {
        const accountId = row.querySelector('.tv-account-id').value;
        const accountName = row.querySelector('.tv-account-input').value;
        const debit = parseFloat(row.querySelector('.tv-debit-input').value) || 0;
        const credit = parseFloat(row.querySelector('.tv-credit-input').value) || 0;
        const narration = row.querySelector('.tv-narration-input').value;

        if (accountName && (debit > 0 || credit > 0)) {
            items.push({
                account_id: accountId,
                account_name: accountName,
                debit_amount: debit,
                credit_amount: credit,
                narration: narration
            });
        }
    });

    return {
        id: document.getElementById('tvVoucherId').value,
        voucher_date: document.getElementById('tvVoucherDate').value,
        payment_mode: document.getElementById('tvPaymentMode').value,
        reference_number: document.getElementById('tvReferenceNumber').value,
        narration: document.getElementById('tvNarration').value,
        items: items
    };
}
</script>
