/**
 * BUSY-style voucher entry (Payment / Receipt)
 */
(function () {
    'use strict';

    const cfg = window.BUSY_VOUCHER || {};
    const baseUrl = cfg.baseUrl || '';
    const voucherType = (cfg.voucherType || 'PAYMENT').toUpperCase();
    const defaultPaymentMode = (cfg.paymentMode || 'CASH').toUpperCase();
    const ROW_COUNT = 16;

    const PAYMENT_ACCOUNT_MAP = {
        CASH: 'CASH_MAIN',
        BANK: 'BANK_MAIN',
        CHEQUE: 'BANK_MAIN',
        ONLINE: 'BANK_MAIN',
        PETTY_CASH: 'CASH_PETTY',
    };

    let accounts = [];
    let accountsByCode = {};
    let activeRow = null;
    let searchTargetInput = null;
    let searchSelectedIndex = 0;

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        bindHeaderEvents();
        bindActionButtons();
        bindSearchModal();
        bindKeyboardShortcuts();
        buildGridRows(ROW_COUNT);
        loadAccounts().then(() => {
            const modeEl = document.getElementById('busyPaymentMode');
            if (modeEl && defaultPaymentMode) {
                modeEl.value = defaultPaymentMode;
            }
            loadExistingVoucher();
            focusFirstAccount();
        });
        setTodayDate();
    }

    function apiUrl(params) {
        const q = new URLSearchParams({ page: 'api_accounting', ...params });
        return baseUrl + 'index.php?' + q.toString();
    }

    function setTodayDate() {
        const dateEl = document.getElementById('busyVoucherDate');
        if (!dateEl || dateEl.value) {
            updateDateDisplay();
            return;
        }
        dateEl.value = new Date().toISOString().split('T')[0];
        updateDateDisplay();
    }

    function bindHeaderEvents() {
        const dateEl = document.getElementById('busyVoucherDate');
        if (dateEl) {
            dateEl.addEventListener('change', updateDateDisplay);
        }
    }

    function updateDateDisplay() {
        const dateEl = document.getElementById('busyVoucherDate');
        const display = document.getElementById('busyDateDisplay');
        if (!dateEl || !display) {
            return;
        }
        if (!dateEl.value) {
            display.textContent = '';
            return;
        }
        const d = new Date(dateEl.value + 'T12:00:00');
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        display.textContent = dd + '-' + mm + '-' + yyyy + ' (' + days[d.getDay()] + ')';
    }

    function buildGridRows(count) {
        const tbody = document.getElementById('busyGridBody');
        if (!tbody) {
            return;
        }
        tbody.innerHTML = '';
        for (let i = 1; i <= count; i++) {
            tbody.appendChild(createRow(i));
        }
        recalcTotal();
    }

    function createRow(index) {
        const tr = document.createElement('tr');
        tr.dataset.row = String(index);
        tr.innerHTML =
            '<td class="col-sno">' + index + '</td>' +
            '<td class="col-account"><input type="text" class="account-input" autocomplete="off" data-row="' + index + '">' +
            '<input type="hidden" class="account-id"></td>' +
            '<td class="col-amount"><input type="text" class="amount-input" inputmode="decimal" autocomplete="off" data-row="' + index + '"></td>' +
            '<td class="col-narr"><input type="text" class="short-narr-input" autocomplete="off" data-row="' + index + '"></td>';

        const accountInput = tr.querySelector('.account-input');
        const amountInput = tr.querySelector('.amount-input');
        const narrInput = tr.querySelector('.short-narr-input');

        [accountInput, amountInput, narrInput].forEach(function (input) {
            input.addEventListener('focus', function () {
                setActiveRow(tr);
            });
        });

        accountInput.addEventListener('focus', function () {
            openAccountSearch(accountInput);
        });

        accountInput.addEventListener('keydown', function (e) {
            if (e.key === 'F3' || (e.key === 'Enter' && accountInput.value.trim() === '')) {
                e.preventDefault();
                openAccountSearch(accountInput);
            }
        });

        accountInput.addEventListener('blur', function () {
            resolveAccountFromText(accountInput);
        });

        amountInput.addEventListener('input', function () {
            recalcTotal();
        });

        amountInput.addEventListener('blur', function () {
            amountInput.value = formatAmount(parseAmount(amountInput.value));
            recalcTotal();
        });

        amountInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                narrInput.focus();
            }
        });

        narrInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                moveToNextRow(index);
            }
        });

        return tr;
    }

    function setActiveRow(tr) {
        document.querySelectorAll('#busyGridBody tr').forEach(function (row) {
            row.classList.remove('active-row');
        });
        tr.classList.add('active-row');
        activeRow = tr;
        updateCurrentBalance();
    }

    function moveToNextRow(currentIndex) {
        const next = document.querySelector('#busyGridBody tr[data-row="' + (currentIndex + 1) + '"] .account-input');
        if (next) {
            next.focus();
        }
    }

    function focusFirstAccount() {
        const first = document.querySelector('#busyGridBody tr[data-row="1"] .account-input');
        if (first) {
            first.focus();
        }
    }

    async function loadAccounts() {
        try {
            const res = await fetch(apiUrl({ acc_action: 'list_accounts' }));
            const data = await res.json();
            if (data.ok && Array.isArray(data.data)) {
                accounts = data.data;
                accountsByCode = {};
                accounts.forEach(function (a) {
                    accountsByCode[a.account_code] = a;
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    function resolveAccountFromText(input) {
        const text = input.value.trim();
        if (!text) {
            clearAccountOnRow(input.closest('tr'));
            return;
        }
        const match = accounts.find(function (a) {
            return a.account_name.toLowerCase() === text.toLowerCase() ||
                a.account_code.toLowerCase() === text.toLowerCase();
        });
        if (match) {
            setAccountOnRow(input.closest('tr'), match);
        }
    }

    function setAccountOnRow(row, account) {
        if (!row || !account) {
            return;
        }
        row.querySelector('.account-input').value = account.account_name;
        row.querySelector('.account-id').value = account.id;
        updateCurrentBalance();
    }

    function clearAccountOnRow(row) {
        if (!row) {
            return;
        }
        row.querySelector('.account-id').value = '';
        updateCurrentBalance();
    }

    async function updateCurrentBalance() {
        const el = document.getElementById('busyCurBal');
        if (!el || !activeRow) {
            return;
        }
        const accountId = activeRow.querySelector('.account-id').value;
        if (!accountId) {
            el.textContent = '( Cur. Bal. : Rs. 0.00 )';
            return;
        }
        const asOf = document.getElementById('busyVoucherDate')?.value || '';
        try {
            const res = await fetch(apiUrl({
                acc_action: 'get_account_balance',
                account_id: accountId,
                as_of_date: asOf,
            }));
            const data = await res.json();
            if (data.ok) {
                el.textContent = formatBalanceLabel(data.data.balance);
            }
        } catch (e) {
            el.textContent = '( Cur. Bal. : — )';
        }
    }

    function formatBalanceLabel(balance) {
        const val = Math.abs(parseFloat(balance) || 0);
        const side = (parseFloat(balance) || 0) >= 0 ? 'Dr' : 'Cr';
        return '( Cur. Bal. : Rs. ' + formatAmount(val) + ' ' + side + ' )';
    }

    function parseAmount(raw) {
        if (raw === null || raw === undefined) {
            return 0;
        }
        const cleaned = String(raw).replace(/,/g, '').trim();
        const n = parseFloat(cleaned);
        return isNaN(n) ? 0 : n;
    }

    function formatAmount(n) {
        return (parseFloat(n) || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('#busyGridBody .amount-input').forEach(function (input) {
            total += parseAmount(input.value);
        });
        const totalEl = document.getElementById('busyTotalAmount');
        if (totalEl) {
            totalEl.textContent = formatAmount(total);
        }
    }

    function collectLineItems() {
        const items = [];
        const isPayment = voucherType === 'PAYMENT';
        const isReceipt = voucherType === 'RECEIPT';
        const isTransfer = voucherType === 'TRANSFER' || voucherType === 'CONTRA';
        const isJournal = voucherType === 'JOURNAL';
        let lineIndex = 0;

        document.querySelectorAll('#busyGridBody tr').forEach(function (row) {
            const accountId = row.querySelector('.account-id').value;
            const accountName = row.querySelector('.account-input').value.trim();
            const amount = parseAmount(row.querySelector('.amount-input').value);
            const narration = row.querySelector('.short-narr-input').value.trim();
            if (!accountName || amount <= 0) {
                return;
            }
            let resolvedId = accountId;
            if (!resolvedId) {
                const match = accounts.find(function (a) {
                    return a.account_name.toLowerCase() === accountName.toLowerCase();
                });
                resolvedId = match ? match.id : '';
            }
            if (!resolvedId) {
                throw new Error('Invalid account: ' + accountName);
            }

            let debitAmount = 0;
            let creditAmount = 0;

            if (isPayment) {
                debitAmount = amount;
            } else if (isReceipt) {
                creditAmount = amount;
            } else if (isTransfer) {
                if (lineIndex === 0) {
                    creditAmount = amount;
                } else if (lineIndex === 1) {
                    debitAmount = amount;
                } else {
                    throw new Error('Transfer/Contra vouchers support exactly two account lines.');
                }
            } else if (isJournal) {
                if (lineIndex % 2 === 0) {
                    debitAmount = amount;
                } else {
                    creditAmount = amount;
                }
            } else {
                debitAmount = amount;
            }

            items.push({
                account_id: resolvedId,
                account_name: accountName,
                debit_amount: debitAmount,
                credit_amount: creditAmount,
                narration: narration,
            });
            lineIndex += 1;
        });

        if (isTransfer && items.length !== 2) {
            throw new Error('Transfer/Contra vouchers require exactly two accounts (From and To).');
        }

        if (isTransfer && Math.abs(items[0].credit_amount - items[1].debit_amount) > 0.01) {
            throw new Error('Transfer amounts must match on both accounts.');
        }

        return items;
    }

    function getPaymentModeAccount() {
        const mode = document.getElementById('busyPaymentMode')?.value || 'CASH';
        const code = PAYMENT_ACCOUNT_MAP[mode] || 'CASH_MAIN';
        return accountsByCode[code] || accounts.find(function (a) {
            return a.account_code === code;
        }) || null;
    }

    function buildVoucherPayload() {
        const lines = collectLineItems();
        if (lines.length === 0) {
            throw new Error('Enter at least one account with amount.');
        }

        const isPayment = voucherType === 'PAYMENT';
        const isReceipt = voucherType === 'RECEIPT';

        if (isPayment || isReceipt) {
            let total = 0;
            lines.forEach(function (l) {
                total += parseFloat(l.debit_amount || l.credit_amount || 0);
            });

            const modeAccount = getPaymentModeAccount();
            if (!modeAccount) {
                throw new Error('Payment mode account not found in chart of accounts.');
            }

            if (isPayment) {
                lines.push({
                    account_id: modeAccount.id,
                    account_name: modeAccount.account_name,
                    debit_amount: 0,
                    credit_amount: total,
                    narration: document.getElementById('busyHeaderNarration')?.value || '',
                });
            } else {
                lines.push({
                    account_id: modeAccount.id,
                    account_name: modeAccount.account_name,
                    debit_amount: total,
                    credit_amount: 0,
                    narration: document.getElementById('busyHeaderNarration')?.value || '',
                });
            }
        }

        let rawMode = document.getElementById('busyPaymentMode')?.value || 'CASH';
        if (rawMode === 'PETTY_CASH') {
            rawMode = 'CASH';
        }

        return {
            id: document.getElementById('busyVoucherId')?.value || 0,
            voucher_type: voucherType,
            voucher_date: document.getElementById('busyVoucherDate')?.value || '',
            reference_number: document.getElementById('busyVoucherTypeField')?.value || '',
            payment_mode: rawMode,
            narration: document.getElementById('busyLongNarration')?.value || '',
            details: lines,
        };
    }

    async function saveVoucher() {
        document.querySelectorAll('#busyGridBody .account-input').forEach(function (input) {
            resolveAccountFromText(input);
        });

        let payload;
        try {
            payload = buildVoucherPayload();
        } catch (e) {
            alert(e.message);
            return;
        }

        try {
            const res = await fetch(apiUrl({ acc_action: 'save_voucher' }), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acc_action: 'save_voucher',
                    csrf_token: document.getElementById('csrf')?.value || '',
                    ...payload,
                }),
            });
            const data = await res.json();
            if (data.ok && data.data) {
                document.getElementById('busyVoucherId').value = data.data.id;
                document.getElementById('busyVoucherNo').value = data.data.voucher_number || '';
                await postSavedVoucher(data.data.id);
            } else {
                alert('Error: ' + (data.error || 'Save failed'));
            }
        } catch (e) {
            alert('Error saving voucher: ' + e.message);
        }
    }

    async function postSavedVoucher(id) {
        try {
            const res = await fetch(apiUrl({ acc_action: 'post_voucher' }), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acc_action: 'post_voucher',
                    csrf_token: document.getElementById('csrf')?.value || '',
                    id: id,
                }),
            });
            const data = await res.json();
            if (data.ok) {
                if (window.AccModule && AccModule.toast) {
                    AccModule.toast('Voucher saved and posted to ledger');
                } else {
                    alert('Voucher saved and posted to ledger.');
                }
            } else {
                alert('Saved as draft. Posting failed: ' + (data.error || 'Unknown error'));
            }
        } catch (e) {
            alert('Saved as draft. Posting failed: ' + e.message);
        }
    }

    function quitVoucher() {
        if (confirm('Quit without saving?')) {
            window.location.href = baseUrl + 'index.php?page=accounting&action=vouchers&voucher_type=' + encodeURIComponent(voucherType);
        }
    }

    function bindActionButtons() {
        document.getElementById('busySaveBtn')?.addEventListener('click', saveVoucher);
        document.getElementById('busyQuitBtn')?.addEventListener('click', quitVoucher);
    }

    function bindKeyboardShortcuts() {
        document.addEventListener('keydown', function (e) {
            if (e.key === 'F2') {
                e.preventDefault();
                saveVoucher();
            }
            if (e.key === 'Escape') {
                const modal = document.getElementById('busySearchModal');
                if (modal?.classList.contains('open')) {
                    closeAccountSearch();
                } else {
                    quitVoucher();
                }
            }
        });
    }

    /* Account search modal */
    function bindSearchModal() {
        document.getElementById('busySearchClose')?.addEventListener('click', closeAccountSearch);
        document.getElementById('busySearchInput')?.addEventListener('input', function (e) {
            renderSearchResults(filterAccounts(e.target.value));
        });
        document.getElementById('busySearchInput')?.addEventListener('keydown', function (e) {
            const items = document.querySelectorAll('.busy-search-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                searchSelectedIndex = Math.min(searchSelectedIndex + 1, items.length - 1);
                highlightSearchItem(items);
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                searchSelectedIndex = Math.max(searchSelectedIndex - 1, 0);
                highlightSearchItem(items);
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                if (items[searchSelectedIndex]) {
                    items[searchSelectedIndex].click();
                }
            }
        });
        document.getElementById('busySearchModal')?.addEventListener('click', function (e) {
            if (e.target.id === 'busySearchModal') {
                closeAccountSearch();
            }
        });
    }

    function highlightSearchItem(items) {
        items.forEach(function (el, i) {
            el.classList.toggle('selected', i === searchSelectedIndex);
        });
        items[searchSelectedIndex]?.scrollIntoView({ block: 'nearest' });
    }

    function openAccountSearch(input) {
        searchTargetInput = input;
        searchSelectedIndex = 0;
        const modal = document.getElementById('busySearchModal');
        const searchInput = document.getElementById('busySearchInput');
        modal?.classList.add('open');
        if (searchInput) {
            searchInput.value = input.value.trim();
            renderSearchResults(filterAccounts(searchInput.value));
            searchInput.focus();
            searchInput.select();
        }
    }

    function closeAccountSearch() {
        document.getElementById('busySearchModal')?.classList.remove('open');
        searchTargetInput = null;
    }

    function filterAccounts(query) {
        const q = (query || '').toLowerCase().trim();
        if (!q) {
            return accounts.slice(0, 30);
        }
        return accounts.filter(function (a) {
            return a.account_name.toLowerCase().includes(q) ||
                a.account_code.toLowerCase().includes(q);
        }).slice(0, 30);
    }

    function renderSearchResults(list) {
        const box = document.getElementById('busySearchResults');
        if (!box) {
            return;
        }
        if (!list.length) {
            box.innerHTML = '<div style="padding:8px;color:#666;">No accounts found</div>';
            return;
        }
        box.innerHTML = list.map(function (a, i) {
            return '<div class="busy-search-item' + (i === 0 ? ' selected' : '') + '" data-id="' + a.id + '" data-name="' + escapeAttr(a.account_name) + '">' +
                '<div>' + escapeHtml(a.account_name) + '</div>' +
                '<div class="code">' + escapeHtml(a.account_code) + ' | ' + escapeHtml(a.group_name || '') + '</div></div>';
        }).join('');

        box.querySelectorAll('.busy-search-item').forEach(function (el) {
            el.addEventListener('click', function () {
                selectSearchAccount(el.dataset.id, el.dataset.name);
            });
        });
        searchSelectedIndex = 0;
    }

    function selectSearchAccount(id, name) {
        const row = searchTargetInput?.closest('tr');
        if (row) {
            row.querySelector('.account-id').value = id;
            row.querySelector('.account-input').value = name;
            updateCurrentBalance();
        }
        closeAccountSearch();
        row?.querySelector('.amount-input')?.focus();
    }

    async function loadExistingVoucher() {
        const id = parseInt(document.getElementById('busyVoucherId')?.value || '0', 10);
        if (!id) {
            return;
        }
        try {
            const res = await fetch(apiUrl({ acc_action: 'get_voucher', id: String(id) }));
            const data = await res.json();
            if (data.ok && data.data) {
                populateVoucher(data.data);
            }
        } catch (e) {
            console.error(e);
        }
    }

    function populateVoucher(voucher) {
        document.getElementById('busyVoucherNo').value = voucher.voucher_number || '';
        document.getElementById('busyVoucherDate').value = voucher.voucher_date || '';
        document.getElementById('busyPaymentMode').value = voucher.payment_mode || 'CASH';
        document.getElementById('busyLongNarration').value = voucher.narration || '';
        updateDateDisplay();

        const details = (voucher.details || []).filter(function (d) {
            const modeAcc = getPaymentModeAccount();
            return !modeAcc || String(d.account_id) !== String(modeAcc.id);
        });

        const tbody = document.getElementById('busyGridBody');
        tbody.innerHTML = '';
        const count = Math.max(ROW_COUNT, details.length);
        for (let i = 1; i <= count; i++) {
            tbody.appendChild(createRow(i));
        }

        details.forEach(function (d, idx) {
            const row = tbody.querySelector('tr[data-row="' + (idx + 1) + '"]');
            if (!row) {
                return;
            }
            row.querySelector('.account-input').value = d.account_name || '';
            row.querySelector('.account-id').value = d.account_id || '';
            const amt = parseFloat(d.debit_amount || d.credit_amount || 0);
            row.querySelector('.amount-input').value = amt > 0 ? formatAmount(amt) : '';
            row.querySelector('.short-narr-input').value = d.narration || '';
        });

        recalcTotal();
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(s) {
        return escapeHtml(s).replace(/'/g, '&#39;');
    }

    window.busySaveVoucher = saveVoucher;
    window.busyQuitVoucher = quitVoucher;
})();
