<?php
/**
 * Desktop Accounting Style Balance Sheet
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
$asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
?>
<style>
.acc-bs-module {
    background-color: #F5F5DC;
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

.acc-bs-header {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
    padding: 4px 8px;
    border-bottom: 2px solid #1A1A1A;
}

.acc-bs-header-title {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.acc-bs-toolbar {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
    align-items: center;
}

.acc-bs-form-group {
    display: flex;
    flex-direction: column;
}

.acc-bs-form-group label {
    font-size: 10px;
    font-weight: bold;
    color: #333;
    margin-bottom: 2px;
}

.acc-bs-form-group input {
    font-size: 11px;
    padding: 3px 5px;
    border: 1px solid #999;
    background-color: #FFF;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-bs-btn {
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

.acc-bs-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-bs-section {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-bs-section-title {
    font-size: 12px;
    font-weight: bold;
    color: #333;
    margin-bottom: 6px;
    text-transform: uppercase;
    border-bottom: 1px solid #999;
    padding-bottom: 4px;
}

.acc-bs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.acc-bs-table thead {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
}

.acc-bs-table th {
    padding: 4px 6px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-bs-table td {
    padding: 4px 6px;
    border: 1px solid #999;
}

.acc-bs-table .text-right {
    text-align: right;
}

.acc-bs-summary {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 20px;
    align-items: center;
    justify-content: flex-end;
}

.acc-bs-summary-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.acc-bs-summary-item label {
    font-size: 11px;
    font-weight: bold;
    color: #333;
}

.acc-bs-summary-item span {
    font-size: 12px;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    min-width: 120px;
    text-align: right;
}

.acc-bs-summary-item span.balanced {
    color: #006600;
}

.acc-bs-summary-item span.not-balanced {
    color: #CC0000;
}
</style>

<div class="acc-bs-module">
    <!-- Header -->
    <div class="acc-bs-header">
        <span class="acc-bs-header-title">
            <i class="bi bi-bank2"></i> Balance Sheet
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-bs-toolbar">
        <div class="acc-bs-form-group">
            <label>As Of Date</label>
            <input type="date" id="accAsOfDate" value="<?php echo htmlspecialchars($asOfDate); ?>">
        </div>
        <button type="button" class="acc-bs-btn" onclick="accLoadBalanceSheet()">
            Load
        </button>
        <button type="button" class="acc-bs-btn" onclick="accExportExcel()">
            Export Excel
        </button>
        <button type="button" class="acc-bs-btn" onclick="accPrintBalanceSheet()">
            Print
        </button>
    </div>

    <!-- Assets Section -->
    <div class="acc-bs-section">
        <div class="acc-bs-section-title">Assets</div>
        <table class="acc-bs-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Account Code</th>
                    <th style="width: 40%;">Account Name</th>
                    <th style="width: 20%;">Group</th>
                    <th class="text-right" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody id="accAssetsBody">
                <tr>
                    <td colspan="4" class="text-center" style="padding: 20px; color: #999;">
                        Click "Load" to view Balance Sheet
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Total Assets:</strong></td>
                    <td class="text-right"><strong id="accTotalAssets">0.00</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Liabilities Section -->
    <div class="acc-bs-section">
        <div class="acc-bs-section-title">Liabilities</div>
        <table class="acc-bs-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Account Code</th>
                    <th style="width: 40%;">Account Name</th>
                    <th style="width: 20%;">Group</th>
                    <th class="text-right" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody id="accLiabilitiesBody">
                <tr>
                    <td colspan="4" class="text-center" style="padding: 20px; color: #999;">
                        Click "Load" to view Balance Sheet
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Total Liabilities:</strong></td>
                    <td class="text-right"><strong id="accTotalLiabilities">0.00</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Capital Section -->
    <div class="acc-bs-section">
        <div class="acc-bs-section-title">Capital</div>
        <table class="acc-bs-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Account Code</th>
                    <th style="width: 40%;">Account Name</th>
                    <th style="width: 20%;">Group</th>
                    <th class="text-right" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody id="accCapitalBody">
                <tr>
                    <td colspan="4" class="text-center" style="padding: 20px; color: #999;">
                        Click "Load" to view Balance Sheet
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Total Capital:</strong></td>
                    <td class="text-right"><strong id="accTotalCapital">0.00</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Summary -->
    <div class="acc-bs-summary">
        <div class="acc-bs-summary-item">
            <label>Assets = Liabilities + Capital:</label>
            <span id="accBalanceStatus" class="balanced">BALANCED</span>
        </div>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';

async function accLoadBalanceSheet() {
    const asOfDate = document.getElementById('accAsOfDate').value;

    try {
        const response = await fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=balance_sheet&as_of_date=' + encodeURIComponent(asOfDate));
        const data = await response.json();
        
        if (data.ok && data.data) {
            accRenderBalanceSheet(data.data);
        } else {
            alert('Error loading Balance Sheet: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        alert('Error loading Balance Sheet: ' + error.message);
    }
}

function accRenderBalanceSheet(balanceSheet) {
    const assetsBody = document.getElementById('accAssetsBody');
    const liabilitiesBody = document.getElementById('accLiabilitiesBody');
    const capitalBody = document.getElementById('accCapitalBody');
    
    const assets = balanceSheet.assets || [];
    const liabilities = balanceSheet.liabilities || [];
    const capital = balanceSheet.capital || [];

    if (assets.length === 0) {
        assetsBody.innerHTML = '<tr><td colspan="4" class="text-center" style="padding: 20px; color: #999;">No assets found</td></tr>';
    } else {
        assetsBody.innerHTML = assets.map(acc => `
            <tr>
                <td>${acc.account_code || ''}</td>
                <td>${acc.account_name || ''}</td>
                <td>${acc.group_name || ''}</td>
                <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
            </tr>
        `).join('');
    }

    if (liabilities.length === 0) {
        liabilitiesBody.innerHTML = '<tr><td colspan="4" class="text-center" style="padding: 20px; color: #999;">No liabilities found</td></tr>';
    } else {
        liabilitiesBody.innerHTML = liabilities.map(acc => `
            <tr>
                <td>${acc.account_code || ''}</td>
                <td>${acc.account_name || ''}</td>
                <td>${acc.group_name || ''}</td>
                <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
            </tr>
        `).join('');
    }

    if (capital.length === 0) {
        capitalBody.innerHTML = '<tr><td colspan="4" class="text-center" style="padding: 20px; color: #999;">No capital accounts found</td></tr>';
    } else {
        capitalBody.innerHTML = capital.map(acc => `
            <tr>
                <td>${acc.account_code || ''}</td>
                <td>${acc.account_name || ''}</td>
                <td>${acc.group_name || ''}</td>
                <td class="text-right">${parseFloat(acc.amount || 0).toFixed(2)}</td>
            </tr>
        `).join('');
    }

    const totalAssets = parseFloat(balanceSheet.total_assets || 0);
    const totalLiabilities = parseFloat(balanceSheet.total_liabilities || 0);
    const totalCapital = parseFloat(balanceSheet.total_capital || 0);

    document.getElementById('accTotalAssets').textContent = totalAssets.toFixed(2);
    document.getElementById('accTotalLiabilities').textContent = totalLiabilities.toFixed(2);
    document.getElementById('accTotalCapital').textContent = totalCapital.toFixed(2);

    const balanceStatus = document.getElementById('accBalanceStatus');
    const isBalanced = Math.abs(totalAssets - (totalLiabilities + totalCapital)) < 0.01;

    if (isBalanced) {
        balanceStatus.textContent = 'BALANCED';
        balanceStatus.className = 'balanced';
    } else {
        balanceStatus.textContent = 'NOT BALANCED (Difference: ' + (totalAssets - (totalLiabilities + totalCapital)).toFixed(2) + ')';
        balanceStatus.className = 'not-balanced';
    }
}

function accExportExcel() {
    alert('Excel export functionality - to be implemented');
}

function accPrintBalanceSheet() {
    window.print();
}
</script>
