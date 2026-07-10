/**
 * Payment Voucher Module - Advanced Frontend JavaScript
 * 
 * Features:
 * - Professional grid editing with keyboard navigation
 * - Automatic balance calculation
 * - Smart account search with autocomplete
 * - Real-time validation
 * - Keyboard shortcuts (F2-F8, etc)
 * - Employee payment allocation
 * - Auto-save to backend
 */

class PaymentVoucherModule {
    constructor() {
        this.baseUrl = document.body.getAttribute('data-base-url') || '/TMS/public/';
        this.csrf = document.getElementById('csrf').value;
        this.voucherId = null;
        this.lineItems = [];
        this.currentEditRow = null;
        this.searchCallback = null;
        this.accounts = [];
        this.employees = [];
        this.apiBase = this.baseUrl + 'index.php';

        this.init();
    }

    init() {
        // Set today's date by default
        document.getElementById('pvVoucherDate').valueAsDate = new Date();

        // Event listeners
        this.setupFormListeners();
        this.setupGridListeners();
        this.setupSearchListeners();
        this.setupKeyboardShortcuts();
        this.setupActionButtons();
        this.loadInitialData();
    }

    /**
     * Setup form event listeners
     */
    setupFormListeners() {
        // Payment mode change - show/hide cheque details
        document.getElementById('pvPaymentMode').addEventListener('change', (e) => {
            const showCheque = e.target.value === 'CHEQUE';
            document.getElementById('pvChequeSection').style.display = showCheque ? 'block' : 'none';
        });

        // Voucher date - update summary
        document.getElementById('pvVoucherDate').addEventListener('change', (e) => {
            this.updateSummary();
        });

        // Payment mode - update summary
        document.getElementById('pvPaymentMode').addEventListener('change', (e) => {
            this.updateSummary();
        });
    }

    /**
     * Setup grid event listeners
     */
    setupGridListeners() {
        // Add row button
        document.getElementById('pvAddRow').addEventListener('click', () => this.addRow());

        // Auto balance button
        document.getElementById('pvAutoBalance').addEventListener('click', () => this.autoBalance());

        // Employee payment button
        document.getElementById('pvEmployeePayment').addEventListener('click', () => this.openEmployeePaymentForm());
    }

    /**
     * Setup search modal listeners
     */
    setupSearchListeners() {
        const searchModal = document.getElementById('pvSearchModal');
        const searchInput = document.getElementById('pvSearchInput');
        const searchClose = document.getElementById('pvSearchClose');

        searchClose.addEventListener('click', () => this.closeSearch());

        // Search input
        searchInput.addEventListener('input', (e) => this.performSearch(e.target.value));

        // Tab switching
        document.querySelectorAll('.pv-search-tab').forEach(tab => {
            tab.addEventListener('click', (e) => this.switchSearchTab(e.target.dataset.tab));
        });

        // Close modal on background click
        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                this.closeSearch();
            }
        });
    }

    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // F2 - Save
            if (e.key === 'F2') {
                e.preventDefault();
                this.saveDraft();
            }

            // F3 - Search account
            if (e.key === 'F3') {
                e.preventDefault();
                this.openSearch('accounts');
            }

            // F4 - Focus narration
            if (e.key === 'F4') {
                e.preventDefault();
                document.getElementById('pvNarration').focus();
            }

            // F6 - Focus voucher type
            if (e.key === 'F6') {
                e.preventDefault();
                document.getElementById('pvVoucherType').focus();
            }

            // F8 - Delete last row
            if (e.key === 'F8') {
                e.preventDefault();
                this.deleteLastRow();
            }

            // Ctrl+Enter - Add row
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                this.addRow();
            }
        });

        // Grid keyboard navigation
        const grid = document.getElementById('pvGridBody');
        grid.addEventListener('keydown', (e) => {
            const currentRow = document.activeElement.closest('tr');
            if (!currentRow) return;

            // Arrow up/down - navigate rows
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                e.preventDefault();
                const rows = Array.from(grid.querySelectorAll('tr'));
                const currentIndex = rows.indexOf(currentRow);
                const nextIndex = e.key === 'ArrowUp' ? currentIndex - 1 : currentIndex + 1;

                if (nextIndex >= 0 && nextIndex < rows.length) {
                    const nextInput = rows[nextIndex].querySelector('input');
                    nextInput?.focus();
                }
            }

            // Tab - move to next cell or row
            if (e.key === 'Tab') {
                const inputs = Array.from(currentRow.querySelectorAll('input'));
                const currentInput = document.activeElement;
                const currentIndex = inputs.indexOf(currentInput);

                if (currentIndex === inputs.length - 1 && !e.shiftKey) {
                    e.preventDefault();
                    this.addRow();
                }
            }
        });
    }

    /**
     * Setup action buttons
     */
    setupActionButtons() {
        document.getElementById('pvSaveBtn').addEventListener('click', () => this.saveDraft());
        document.getElementById('pvSubmitBtn').addEventListener('click', () => this.submitVoucher());
        document.getElementById('pvPreviewBtn').addEventListener('click', () => this.previewVoucher());
        document.getElementById('pvCancelBtn').addEventListener('click', () => this.cancelEntry());
    }

    /**
     * Load initial data (accounts, employees, etc)
     */
    async loadInitialData() {
        try {
            // Load accounts list
            const accountsResponse = await fetch(`${this.apiBase}?pv_action=search_accounts&q=&limit=500`);
            const accountsData = await accountsResponse.json();
            this.accounts = accountsData.results || [];

            // Load employees
            const employeesResponse = await fetch(`${this.apiBase}?pv_action=search_employees&q=&limit=500`);
            const employeesData = await employeesResponse.json();
            this.employees = employeesData.results || [];

            // Generate initial voucher number
            const numberResponse = await fetch(`${this.apiBase}?pv_action=get_next_voucher_number&fiscal_year=${new Date().getFullYear()}`);
            const numberData = await numberResponse.json();
            document.getElementById('pvVoucherNumber').value = numberData.voucher_number || 'AUTO';
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.showNotification('Error loading data. Please refresh the page.', 'error');
        }
    }

    /**
     * Add a new line item row to grid
     */
    addRow() {
        const grid = document.getElementById('pvGridBody');
        const rowIndex = grid.rows.length + 1;

        const row = document.createElement('tr');
        row.dataset.rowIndex = rowIndex;

        row.innerHTML = `
            <td style="text-align: center; color: #6c757d; font-weight: 600;">${rowIndex}</td>
            <td>
                <input type="text" class="account-code" placeholder="Code" data-row="${rowIndex}">
            </td>
            <td>
                <input type="text" class="account-name" placeholder="Account Name" data-row="${rowIndex}" readonly>
            </td>
            <td class="text-right">
                <input type="number" class="debit-amount" placeholder="0.00" value="0.00" step="0.01" data-row="${rowIndex}">
            </td>
            <td class="text-right">
                <input type="number" class="credit-amount" placeholder="0.00" value="0.00" step="0.01" data-row="${rowIndex}">
            </td>
            <td>
                <div class="pv-grid-row-actions">
                    <button type="button" class="pv-grid-row-btn pv-grid-row-btn-edit" onclick="pv.editRow(${rowIndex})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="pv-grid-row-btn pv-grid-row-btn-delete" onclick="pv.deleteRow(${rowIndex})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        `;

        // Account code input - open search
        const accountCodeInput = row.querySelector('.account-code');
        accountCodeInput.addEventListener('focus', () => {
            this.searchCallback = (account) => {
                accountCodeInput.value = account.account_code;
                row.querySelector('.account-name').value = account.account_name;
                this.calculateTotals();
                this.closeSearch();
            };
            this.openSearch('accounts');
        });

        // Amount inputs - recalculate on change
        const debitInput = row.querySelector('.debit-amount');
        const creditInput = row.querySelector('.credit-amount');

        debitInput.addEventListener('change', () => {
            this.calculateTotals();
        });

        creditInput.addEventListener('change', () => {
            this.calculateTotals();
        });

        grid.appendChild(row);
        this.updateLineCount();
        this.calculateTotals();

        // Focus on account code input
        setTimeout(() => accountCodeInput.focus(), 100);
    }

    /**
     * Delete a row
     */
    deleteRow(rowIndex) {
        const grid = document.getElementById('pvGridBody');
        const row = grid.querySelector(`tr[data-row-index="${rowIndex}"]`);

        if (row) {
            row.remove();
            this.updateLineCount();
            this.calculateTotals();
            this.showNotification('Row deleted', 'success');
        }
    }

    /**
     * Delete last row
     */
    deleteLastRow() {
        const grid = document.getElementById('pvGridBody');
        const rows = grid.querySelectorAll('tr');

        if (rows.length > 0) {
            rows[rows.length - 1].remove();
            this.updateLineCount();
            this.calculateTotals();
        }
    }

    /**
     * Edit row (placeholder for edit functionality)
     */
    editRow(rowIndex) {
        const grid = document.getElementById('pvGridBody');
        const row = grid.querySelector(`tr[data-row-index="${rowIndex}"]`);

        if (row) {
            row.classList.toggle('editing');
        }
    }

    /**
     * Calculate totals from grid
     */
    calculateTotals() {
        const grid = document.getElementById('pvGridBody');
        let totalDebit = 0;
        let totalCredit = 0;

        grid.querySelectorAll('tr').forEach(row => {
            const debit = parseFloat(row.querySelector('.debit-amount')?.value || 0);
            const credit = parseFloat(row.querySelector('.credit-amount')?.value || 0);

            totalDebit += debit;
            totalCredit += credit;
        });

        const balance = totalDebit - totalCredit;
        const isBalanced = Math.abs(balance) < 0.01;

        // Update displays
        document.getElementById('pvTotalDebit').textContent = this.formatMoney(totalDebit);
        document.getElementById('pvTotalCredit').textContent = this.formatMoney(totalCredit);
        document.getElementById('pvBalance').textContent = this.formatMoney(Math.abs(balance));

        // Update balance status
        const balanceItem = document.getElementById('pvBalanceItem');
        const balanceStatus = document.getElementById('pvBalanceStatus');

        if (isBalanced && totalDebit > 0) {
            balanceItem.classList.remove('warning');
            balanceStatus.textContent = '✓ Balanced';
            balanceStatus.style.color = '#28a745';

            document.getElementById('pvSubmitBtn').disabled = false;
        } else {
            balanceItem.classList.add('warning');
            balanceStatus.textContent = `⚠ Balance: ${this.formatMoney(Math.abs(balance))}`;
            balanceStatus.style.color = '#dc3545';

            document.getElementById('pvSubmitBtn').disabled = true;
        }

        this.updateSummary();
    }

    /**
     * Auto-balance voucher
     */
    async autoBalance() {
        try {
            this.showNotification('Auto-balancing voucher...', 'info');

            if (!this.voucherId) {
                // First save as draft
                await this.saveDraft();
            }

            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    pv_action: 'auto_balance',
                    voucher_id: this.voucherId,
                    csrf_token: this.csrf
                })
            });

            const data = await response.json();

            if (data.success) {
                // Add balance row
                const grid = document.getElementById('pvGridBody');
                const rowIndex = grid.rows.length + 1;

                const row = document.createElement('tr');
                row.dataset.rowIndex = rowIndex;

                const balanceAmount = data.balance_amount || 0;
                const isDebit = data.balance_item?.debit_amount > 0;

                row.innerHTML = `
                    <td style="text-align: center; color: #6c757d; font-weight: 600;">${rowIndex}</td>
                    <td>
                        <input type="text" class="account-code" value="${data.balance_account || 'AUTO'}" readonly>
                    </td>
                    <td>
                        <input type="text" class="account-name" value="${data.balance_account || 'Auto-Balance'}" readonly style="background: #fff3cd;">
                    </td>
                    <td class="text-right">
                        <input type="number" class="debit-amount" value="${isDebit ? balanceAmount : 0}" readonly style="background: #e9ecef;">
                    </td>
                    <td class="text-right">
                        <input type="number" class="credit-amount" value="${!isDebit ? balanceAmount : 0}" readonly style="background: #e9ecef;">
                    </td>
                    <td>
                        <div class="pv-grid-row-actions">
                            <button type="button" class="pv-grid-row-btn pv-grid-row-btn-delete" onclick="pv.deleteRow(${rowIndex})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                `;

                grid.appendChild(row);
                this.updateLineCount();
                this.calculateTotals();

                this.showNotification('Voucher auto-balanced successfully!', 'success');
            } else {
                this.showNotification(data.error || 'Failed to auto-balance', 'error');
            }
        } catch (error) {
            console.error('Auto-balance error:', error);
            this.showNotification('Error during auto-balance', 'error');
        }
    }

    /**
     * Open employee payment form
     */
    openEmployeePaymentForm() {
        // This would open a modal for employee payment details
        // For now, simple implementation
        const employeeId = prompt('Enter Employee ID:');
        if (employeeId) {
            const salary = prompt('Salary Amount:', '0');
            const advance = prompt('Advance Amount:', '0');
            const bonus = prompt('Bonus Amount:', '0');

            if (salary || advance || bonus) {
                this.addEmployeePaymentEntry({
                    employee_id: employeeId,
                    salary_amount: parseFloat(salary) || 0,
                    advance_amount: parseFloat(advance) || 0,
                    bonus_amount: parseFloat(bonus) || 0
                });
            }
        }
    }

    /**
     * Add employee payment entry
     */
    async addEmployeePaymentEntry(paymentData) {
        try {
            if (!this.voucherId) {
                await this.saveDraft();
            }

            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    pv_action: 'add_employee_payment',
                    voucher_id: this.voucherId,
                    employee_id: paymentData.employee_id,
                    salary_amount: paymentData.salary_amount,
                    advance_amount: paymentData.advance_amount,
                    bonus_amount: paymentData.bonus_amount,
                    deduction_amount: paymentData.deduction_amount || 0,
                    csrf_token: this.csrf
                })
            });

            const data = await response.json();

            if (data.success) {
                this.calculateTotals();
                this.showNotification(`Employee payment added: ${this.formatMoney(data.total_payment)}`, 'success');
            } else {
                this.showNotification(data.error || 'Failed to add employee payment', 'error');
            }
        } catch (error) {
            console.error('Employee payment error:', error);
            this.showNotification('Error adding employee payment', 'error');
        }
    }

    /**
     * Save draft to backend
     */
    async saveDraft() {
        try {
            const formData = {
                fiscal_year: new Date().getFullYear(),
                voucher_date: document.getElementById('pvVoucherDate').value,
                voucher_type: document.getElementById('pvVoucherType').value,
                payment_mode: document.getElementById('pvPaymentMode').value,
                narration: document.getElementById('pvNarration').value,
                cheque_number: document.getElementById('pvChequeNumber').value,
                cheque_date: document.getElementById('pvChequeDate').value,
                cheque_bank: document.getElementById('pvChequeBank').value
            };

            // Create or update voucher
            if (!this.voucherId) {
                const response = await fetch(this.apiBase, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        pv_action: 'create_voucher',
                        ...formData,
                        csrf_token: this.csrf
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.voucherId = data.voucher_id;
                    document.getElementById('pvVoucherNumber').value = data.voucher_number;
                } else {
                    throw new Error(data.error);
                }
            } else {
                await fetch(this.apiBase, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        pv_action: 'update_voucher',
                        voucher_id: this.voucherId,
                        ...formData,
                        csrf_token: this.csrf
                    })
                });
            }

            // Save line items
            const grid = document.getElementById('pvGridBody');
            grid.querySelectorAll('tr').forEach((row, index) => {
                const debit = parseFloat(row.querySelector('.debit-amount').value) || 0;
                const credit = parseFloat(row.querySelector('.credit-amount').value) || 0;

                if (debit > 0 || credit > 0) {
                    fetch(this.apiBase, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            pv_action: 'add_line_item',
                            voucher_id: this.voucherId,
                            account_code: row.querySelector('.account-code').value,
                            account_name: row.querySelector('.account-name').value,
                            debit_amount: debit,
                            credit_amount: credit,
                            csrf_token: this.csrf
                        })
                    });
                }
            });

            this.showNotification('Draft saved successfully', 'success');
        } catch (error) {
            console.error('Save error:', error);
            this.showNotification('Error saving draft', 'error');
        }
    }

    /**
     * Submit voucher for approval
     */
    async submitVoucher() {
        if (!this.voucherId) {
            this.showNotification('Please save the voucher first', 'error');
            return;
        }

        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    pv_action: 'submit_voucher',
                    voucher_id: this.voucherId,
                    csrf_token: this.csrf
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Voucher submitted for approval', 'success');
                // Update status badge
                document.getElementById('pvStatusBadge').textContent = 'SUBMITTED';
                document.getElementById('pvStatusBadge').className = 'pv-status-badge pv-status-submitted';
            } else {
                this.showNotification(data.errors ? data.errors.join('\n') : data.error, 'error');
            }
        } catch (error) {
            console.error('Submit error:', error);
            this.showNotification('Error submitting voucher', 'error');
        }
    }

    /**
     * Preview voucher
     */
    previewVoucher() {
        alert('Preview functionality - would show printable voucher');
    }

    /**
     * Cancel entry
     */
    cancelEntry() {
        if (confirm('Are you sure you want to cancel this entry?')) {
            location.href = this.baseUrl + 'index.php?page=accounting&action=entry&voucher_type=PAYMENT';
        }
    }

    /**
     * Open smart search modal
     */
    openSearch(tab = 'accounts') {
        const modal = document.getElementById('pvSearchModal');
        const input = document.getElementById('pvSearchInput');

        modal.classList.add('active');
        input.focus();
        input.value = '';

        // Set active tab
        document.querySelectorAll('.pv-search-tab').forEach(t => t.classList.remove('active'));
        document.querySelector(`.pv-search-tab[data-tab="${tab}"]`)?.classList.add('active');

        this.performSearch('');
    }

    /**
     * Close search modal
     */
    closeSearch() {
        document.getElementById('pvSearchModal').classList.remove('active');
    }

    /**
     * Perform search
     */
    performSearch(query) {
        const activeTab = document.querySelector('.pv-search-tab.active')?.dataset.tab || 'accounts';
        const results = this.filterResults(query, activeTab);
        this.displaySearchResults(results);
    }

    /**
     * Filter search results
     */
    filterResults(query, tab) {
        const lowerQuery = query.toLowerCase();

        if (tab === 'employees') {
            return this.employees.filter(emp =>
                emp.name?.toLowerCase().includes(lowerQuery) ||
                emp.code?.toLowerCase().includes(lowerQuery)
            );
        } else if (tab === 'customers') {
            return []; // Placeholder
        } else {
            return this.accounts.filter(acc =>
                acc.account_name?.toLowerCase().includes(lowerQuery) ||
                acc.account_code?.toLowerCase().includes(lowerQuery)
            );
        }
    }

    /**
     * Display search results
     */
    displaySearchResults(results) {
        const resultsDiv = document.getElementById('pvSearchResults');
        resultsDiv.innerHTML = '';

        if (results.length === 0) {
            resultsDiv.innerHTML = '<div style="padding: 2rem; text-align: center; color: #6c757d;">No results found</div>';
            return;
        }

        results.forEach((item, index) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'pv-search-result-item';
            itemDiv.innerHTML = `
                <div class="pv-search-result-name">${item.account_name || item.name}</div>
                <div class="pv-search-result-code">${item.account_code || item.code} ${item.phone ? '• ' + item.phone : ''}</div>
            `;

            itemDiv.addEventListener('click', () => {
                if (this.searchCallback) {
                    this.searchCallback(item);
                }
            });

            resultsDiv.appendChild(itemDiv);
        });
    }

    /**
     * Switch search tab
     */
    switchSearchTab(tab) {
        document.querySelectorAll('.pv-search-tab').forEach(t => t.classList.remove('active'));
        document.querySelector(`.pv-search-tab[data-tab="${tab}"]`)?.classList.add('active');

        this.performSearch(document.getElementById('pvSearchInput').value);
    }

    /**
     * Update line count
     */
    updateLineCount() {
        const count = document.getElementById('pvGridBody').rows.length;
        document.getElementById('pvLineCount').textContent = `${count} items`;
    }

    /**
     * Update summary display
     */
    updateSummary() {
        document.getElementById('pvSummaryDate').textContent = document.getElementById('pvVoucherDate').value || '-';
        document.getElementById('pvSummaryMode').textContent = document.getElementById('pvPaymentMode').value || '-';
        document.getElementById('pvSummaryLines').textContent = document.getElementById('pvGridBody').rows.length;
    }

    /**
     * Format money value
     */
    formatMoney(amount) {
        return parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const alertClass = `pv-alert pv-alert-${type}`;
        const icon = {
            success: '<i class="bi bi-check-circle"></i>',
            error: '<i class="bi bi-exclamation-circle"></i>',
            warning: '<i class="bi bi-exclamation-triangle"></i>',
            info: '<i class="bi bi-info-circle"></i>'
        }[type] || '';

        const notification = document.createElement('div');
        notification.className = alertClass;
        notification.innerHTML = `${icon}<span>${message}</span>`;

        document.querySelector('.pv-form-panel').insertBefore(notification, document.querySelector('.pv-form-section'));

        // Auto-remove after 5 seconds
        setTimeout(() => notification.remove(), 5000);
    }
}

// Initialize module
let pv;
document.addEventListener('DOMContentLoaded', () => {
    pv = new PaymentVoucherModule();
});
