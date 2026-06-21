<?php
/**
 * Desktop Accounting Style Chart of Accounts
 * BUSY/Tally ERP Style Interface
 */
$baseUrl = Helpers::baseUrl('');
?>
<style>
.acc-coa-module {
    background-color: #F5F5DC;
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 11px;
    color: #000;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

.acc-coa-header {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
    padding: 4px 8px;
    border-bottom: 2px solid #1A1A1A;
}

.acc-coa-header-title {
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.acc-coa-toolbar {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
    align-items: center;
}

.acc-coa-form-group {
    display: flex;
    flex-direction: column;
}

.acc-coa-form-group label {
    font-size: 10px;
    font-weight: bold;
    color: #333;
    margin-bottom: 2px;
}

.acc-coa-form-group input,
.acc-coa-form-group select {
    font-size: 11px;
    padding: 3px 5px;
    border: 1px solid #999;
    background-color: #FFF;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-coa-btn {
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

.acc-coa-btn:hover {
    background: linear-gradient(180deg, #5AA0F2 0%, #458ACD 100%);
}

.acc-coa-tabs {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
    display: flex;
    gap: 4px;
}

.acc-coa-tab {
    font-size: 11px;
    font-weight: bold;
    padding: 4px 12px;
    border: 1px solid #999;
    background: #E0E0E0;
    cursor: pointer;
    font-family: 'Tahoma', 'Arial', sans-serif;
}

.acc-coa-tab.active {
    background: linear-gradient(180deg, #4A90E2 0%, #357ABD 100%);
    color: #FFF;
    border-color: #2C5F8E;
}

.acc-coa-table-section {
    background-color: #FFF;
    border: 1px solid #999;
    margin: 4px;
    padding: 6px;
}

.acc-coa-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.acc-coa-table thead {
    background: linear-gradient(180deg, #4A4A4A 0%, #2C2C2C 100%);
    color: #FFF;
}

.acc-coa-table th {
    padding: 4px 6px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #000;
    font-size: 11px;
    text-transform: uppercase;
}

.acc-coa-table td {
    padding: 4px 6px;
    border: 1px solid #999;
}

.acc-coa-table .text-right {
    text-align: right;
}

.acc-coa-table .text-center {
    text-align: center;
}

.acc-coa-tree-item {
    padding: 4px 6px;
    cursor: pointer;
    border-bottom: 1px solid #EEE;
    font-size: 11px;
}

.acc-coa-tree-item:hover {
    background-color: #FFFFE0;
}

.acc-coa-tree-item.group {
    font-weight: bold;
    color: #000;
}

.acc-coa-tree-item.account {
    color: #333;
    padding-left: 20px;
}

.acc-coa-tree-item .code {
    color: #666;
    font-family: 'Courier New', monospace;
    margin-right: 8px;
}

.acc-coa-tree-item .balance {
    float: right;
    font-family: 'Courier New', monospace;
    font-weight: bold;
}

.acc-coa-tree-item .balance.debit {
    color: #CC0000;
}

.acc-coa-tree-item .balance.credit {
    color: #006600;
}
</style>

<div class="acc-coa-module">
    <!-- Header -->
    <div class="acc-coa-header">
        <span class="acc-coa-header-title">
            <i class="bi bi-diagram-3"></i> Chart of Accounts
        </span>
    </div>

    <!-- Toolbar -->
    <div class="acc-coa-toolbar">
        <div class="acc-coa-form-group">
            <label>Search</label>
            <input type="text" id="accSearch" placeholder="Search account name or code...">
        </div>
        <div class="acc-coa-form-group">
            <label>Group Type</label>
            <select id="accGroupTypeFilter">
                <option value="">All Types</option>
                <option value="ASSETS">Assets</option>
                <option value="LIABILITIES">Liabilities</option>
                <option value="CAPITAL">Capital</option>
                <option value="INCOME">Income</option>
                <option value="EXPENSES">Expenses</option>
            </select>
        </div>
        <button type="button" class="acc-coa-btn" onclick="accLoadAccounts()">
            Refresh
        </button>
        <button type="button" class="acc-coa-btn" onclick="accNewAccount()">
            New Account
        </button>
        <button type="button" class="acc-coa-btn" onclick="accNewGroup()">
            New Group
        </button>
    </div>

    <!-- Tabs -->
    <div class="acc-coa-tabs">
        <div class="acc-coa-tab active" onclick="accSwitchTab('tree')">Tree View</div>
        <div class="acc-coa-tab" onclick="accSwitchTab('list')">List View</div>
    </div>

    <!-- Tree View Section -->
    <div class="acc-coa-table-section" id="accTreeView">
        <div id="accTreeContent">
            <div style="padding: 20px; text-align: center; color: #999;">
                Click "Refresh" to load Chart of Accounts
            </div>
        </div>
    </div>

    <!-- List View Section -->
    <div class="acc-coa-table-section" id="accListView" style="display: none;">
        <table class="acc-coa-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Account Code</th>
                    <th style="width: 30%;">Account Name</th>
                    <th style="width: 20%;">Group</th>
                    <th style="width: 15%;">Group Type</th>
                    <th class="text-right" style="width: 15%;">Balance</th>
                    <th style="width: 80px;">Actions</th>
                </tr>
            </thead>
            <tbody id="accListBody">
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px; color: #999;">
                        Click "Refresh" to load Chart of Accounts
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const accBaseUrl = '<?php echo htmlspecialchars($baseUrl); ?>';
let accAccounts = [];
let accGroups = [];

document.addEventListener('DOMContentLoaded', function() {
    accLoadAccounts();
});

async function accLoadAccounts() {
    try {
        const [accountsRes, groupsRes] = await Promise.all([
            fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=list_accounts'),
            fetch(accBaseUrl + 'index.php?page=api_accounting&acc_action=list_account_groups')
        ]);

        const accountsData = await accountsRes.json();
        const groupsData = await groupsRes.json();

        if (accountsData.ok && accountsData.data) {
            accAccounts = accountsData.data;
        }

        if (groupsData.ok && groupsData.data) {
            accGroups = groupsData.data;
        }

        accRenderTreeView();
        accRenderListView();
    } catch (error) {
        console.error('Error loading accounts:', error);
    }
}

function accRenderTreeView() {
    const treeContent = document.getElementById('accTreeContent');
    const groupTypeFilter = document.getElementById('accGroupTypeFilter').value;
    const searchQuery = document.getElementById('accSearch').value.toLowerCase();

    let html = '';

    // Filter groups by type
    const filteredGroups = groupTypeFilter 
        ? accGroups.filter(g => g.group_type === groupTypeFilter)
        : accGroups;

    // Render groups and their accounts
    filteredGroups.forEach(group => {
        const groupAccounts = accAccounts.filter(a => 
            a.account_group_id === group.id && 
            (a.account_name.toLowerCase().includes(searchQuery) || a.account_code.toLowerCase().includes(searchQuery))
        );

        if (groupAccounts.length > 0 || !searchQuery) {
            html += `
                <div class="acc-coa-tree-item group">
                    <span class="code">${group.group_code || ''}</span>
                    ${group.group_name || ''}
                </div>
            `;

            groupAccounts.forEach(account => {
                html += `
                    <div class="acc-coa-tree-item account">
                        <span class="code">${account.account_code || ''}</span>
                        ${account.account_name || ''}
                        <span class="balance ${account.group_nature === 'DEBIT' ? 'debit' : 'credit'}">
                            ${parseFloat(account.current_balance || 0).toFixed(2)}
                        </span>
                    </div>
                `;
            });
        }
    });

    if (html === '') {
        html = '<div style="padding: 20px; text-align: center; color: #999;">No accounts found</div>';
    }

    treeContent.innerHTML = html;
}

function accRenderListView() {
    const listBody = document.getElementById('accListBody');
    const groupTypeFilter = document.getElementById('accGroupTypeFilter').value;
    const searchQuery = document.getElementById('accSearch').value.toLowerCase();

    const filteredAccounts = accAccounts.filter(a => {
        const matchesType = !groupTypeFilter || a.group_type === groupTypeFilter;
        const matchesSearch = !searchQuery || 
            a.account_name.toLowerCase().includes(searchQuery) || 
            a.account_code.toLowerCase().includes(searchQuery);
        return matchesType && matchesSearch;
    });

    if (filteredAccounts.length === 0) {
        listBody.innerHTML = '<tr><td colspan="6" class="text-center" style="padding: 20px; color: #999;">No accounts found</td></tr>';
        return;
    }

    listBody.innerHTML = filteredAccounts.map(acc => `
        <tr>
            <td>${acc.account_code || ''}</td>
            <td>${acc.account_name || ''}</td>
            <td>${acc.group_name || ''}</td>
            <td>${acc.group_type || ''}</td>
            <td class="text-right">${parseFloat(acc.current_balance || 0).toFixed(2)}</td>
            <td>
                <button type="button" class="acc-coa-btn" style="padding: 2px 6px; font-size: 10px;" onclick="accEditAccount(${acc.id})">
                    Edit
                </button>
            </td>
        </tr>
    `).join('');
}

function accSwitchTab(tab) {
    const tabs = document.querySelectorAll('.acc-coa-tab');
    tabs.forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');

    document.getElementById('accTreeView').style.display = tab === 'tree' ? 'block' : 'none';
    document.getElementById('accListView').style.display = tab === 'list' ? 'block' : 'none';
}

function accNewAccount() {
    alert('New Account functionality - to be implemented');
}

function accNewGroup() {
    alert('New Group functionality - to be implemented');
}

function accEditAccount(id) {
    alert('Edit Account functionality - to be implemented for account ID: ' + id);
}

document.getElementById('accSearch').addEventListener('input', function() {
    accRenderTreeView();
    accRenderListView();
});

document.getElementById('accGroupTypeFilter').addEventListener('change', function() {
    accRenderTreeView();
    accRenderListView();
});
</script>
