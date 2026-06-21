<?php
/**
 * Payment Voucher List View
 * 
 * Display all vouchers with filtering, searching, and actions
 */
$csrf = Helpers::csrfToken();
$baseUrl = Helpers::baseUrl('');
?>

<style>
.pv-list-container {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

.pv-list-header {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.pv-list-filters {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.pv-list-filters input,
.pv-list-filters select {
    padding: 0.625rem 0.875rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9375rem;
}

.pv-list-btn {
    padding: 0.625rem 1.25rem;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}

.pv-list-btn:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.pv-list-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.pv-list-table table {
    width: 100%;
    border-collapse: collapse;
}

.pv-list-table th {
    background: #2c3e50;
    color: white;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
}

.pv-list-table td {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.pv-list-table tbody tr:hover {
    background: #f8f9fa;
}

.pv-status-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
}

.status-draft { background: #e9ecef; color: #495057; }
.status-submitted { background: #cfe2ff; color: #084298; }
.status-approved { background: #d1e7dd; color: #0f5132; }
.status-posted { background: #d1ecf1; color: #0c5460; }
.status-cancelled { background: #f8d7da; color: #842029; }

.pv-list-actions {
    display: flex;
    gap: 0.5rem;
}

.pv-list-action-btn {
    padding: 0.4rem 0.8rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.15s ease;
}

.pv-list-action-edit {
    background: #ffc107;
    color: #000;
}

.pv-list-action-view {
    background: #17a2b8;
    color: white;
}

.pv-list-action-delete {
    background: #dc3545;
    color: white;
}

.pv-list-action-btn:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

.pv-list-empty {
    padding: 3rem;
    text-align: center;
    color: #6c757d;
}

.pv-list-pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 2rem;
    flex-wrap: wrap;
}

.pv-list-page-btn {
    padding: 0.5rem 0.875rem;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.pv-list-page-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.pv-list-page-btn:hover {
    border-color: #007bff;
}
</style>

<div class="pv-list-container">
    <div class="pv-list-header">
        <div>
            <h1 style="margin: 0; font-size: 1.75rem; font-weight: 700; color: #2c3e50;">
                Payment Vouchers
            </h1>
            <p style="margin: 0.5rem 0 0 0; color: #6c757d;">Manage all payment and receipt vouchers</p>
        </div>
        <a href="<?php echo htmlspecialchars($baseUrl . 'index.php?page=payment_voucher&action=entry'); ?>" class="pv-list-btn">
            <i class="bi bi-plus-circle"></i> New Voucher
        </a>
    </div>

    <div class="pv-list-header">
        <div class="pv-list-filters">
            <input type="date" id="pvFilterFromDate" placeholder="From Date">
            <input type="date" id="pvFilterToDate" placeholder="To Date">
            
            <select id="pvFilterStatus">
                <option value="">All Status</option>
                <option value="DRAFT">Draft</option>
                <option value="SUBMITTED">Submitted</option>
                <option value="APPROVED">Approved</option>
                <option value="POSTED">Posted</option>
                <option value="CANCELLED">Cancelled</option>
            </select>

            <select id="pvFilterType">
                <option value="">All Types</option>
                <option value="PAYMENT">Payment</option>
                <option value="RECEIPT">Receipt</option>
                <option value="JOURNAL">Journal</option>
            </select>

            <button type="button" class="pv-list-btn" onclick="pvFilterVouchers()">Filter</button>
            <button type="button" class="pv-list-btn" style="background: #6c757d;" onclick="pvResetFilters()">Reset</button>
        </div>
    </div>

    <div class="pv-list-table">
        <table>
            <thead>
                <tr>
                    <th>Voucher No.</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Payment Mode</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                    <th>Posted By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="pvListBody">
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: #6c757d;">
                        <i class="bi bi-hourglass-split"></i> Loading vouchers...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="pv-list-pagination" id="pvPagination"></div>
</div>

<input type="hidden" id="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

<script>
let currentPage = 1;
const itemsPerPage = 50;
const baseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';

async function loadVouchers(page = 1) {
    const filters = {
        status: document.getElementById('pvFilterStatus').value,
        voucher_type: document.getElementById('pvFilterType').value,
        from_date: document.getElementById('pvFilterFromDate').value,
        to_date: document.getElementById('pvFilterToDate').value
    };

    const params = new URLSearchParams({
        pv_action: 'list_vouchers',
        page_no: String(page),
        limit: String(itemsPerPage),
        status: filters.status,
        voucher_type: filters.voucher_type,
        from_date: filters.from_date,
        to_date: filters.to_date,
    });

    try {
        const response = await fetch(baseUrl + 'index.php?page=api_payment_voucher&' + params);
        const data = await response.json();

        if (data.ok && data.data) {
            displayVouchers(data.data.data);
            displayPagination(data.data.pages, page);
            currentPage = page;
        }
    } catch (error) {
        console.error('Error loading vouchers:', error);
    }
}

function displayVouchers(vouchers) {
    const tbody = document.getElementById('pvListBody');
    
    if (vouchers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="pv-list-empty">No vouchers found</td></tr>';
        return;
    }

    tbody.innerHTML = vouchers.map(v => `
        <tr>
            <td><strong>${v.voucher_number}</strong></td>
            <td>${new Date(v.voucher_date).toLocaleDateString()}</td>
            <td>${v.voucher_type}</td>
            <td>${v.payment_mode}</td>
            <td class="text-right"><strong>${parseFloat(v.total_debit).toLocaleString()}</strong></td>
            <td>
                <span class="pv-status-badge status-${v.status.toLowerCase()}">
                    ${v.status}
                </span>
            </td>
            <td>${v.posted_by ? 'System' : '-'}</td>
            <td>
                <div class="pv-list-actions">
                    <a href="${baseUrl}index.php?page=payment_voucher&action=entry&vid=${v.id}" 
                       class="pv-list-action-btn pv-list-action-edit" title="Edit">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <button class="pv-list-action-btn pv-list-action-view" onclick="viewVoucher(${v.id})">
                        <i class="bi bi-eye"></i> View
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function displayPagination(pages, current) {
    const pagination = document.getElementById('pvPagination');
    let html = '';

    for (let i = 1; i <= pages; i++) {
        if (i === current) {
            html += `<button class="pv-list-page-btn active">${i}</button>`;
        } else {
            html += `<button class="pv-list-page-btn" onclick="loadVouchers(${i})">${i}</button>`;
        }
    }

    pagination.innerHTML = html;
}

function pvFilterVouchers() {
    loadVouchers(1);
}

function pvResetFilters() {
    document.getElementById('pvFilterFromDate').value = '';
    document.getElementById('pvFilterToDate').value = '';
    document.getElementById('pvFilterStatus').value = '';
    document.getElementById('pvFilterType').value = '';
    loadVouchers(1);
}

function viewVoucher(voucherId) {
    window.open(baseUrl + `index.php?page=payment_voucher&action=view&vid=${voucherId}`, '_blank');
}

// Load initial vouchers
loadVouchers(1);
</script>
