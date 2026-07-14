/**
 * BUSY-style voucher entry — simple single-entry lines.
 */
(function () {
    'use strict';

    const cfg = window.BUSY_VOUCHER || {};
    const baseUrl = cfg.baseUrl || '';
    const voucherType = (cfg.voucherType || 'PAYMENT').toUpperCase();
    const defaultPaymentMode = (cfg.paymentMode || 'CASH').toUpperCase();
    const ROW_COUNT = 16;

    let accounts = [];
    let paymentModeSettings = {};
    let searchTargetInput = null;
    let searchSelectedIndex = 0;
    let editingRow = null;

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        try { console.log('[BUSY_VOUCHER_UI]', cfg.uiVersion || 'unknown'); } catch (e) {}
        bindHeaderEvents();
        bindActionButtons();
        bindSearchModal();
        bindKeyboardShortcuts();
        buildGridRows(ROW_COUNT);
        Promise.all([loadAccounts(), loadPaymentModeSettings()]).then(function () {
            const modeEl = document.getElementById('busyPaymentMode');
            if (modeEl && defaultPaymentMode) modeEl.value = defaultPaymentMode;
            loadExistingVoucher();
            updateStatusText();
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

        const modeEl = document.getElementById('busyPaymentMode');
        if (modeEl) modeEl.addEventListener('change', updateStatusText);
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
        for (let i = 1; i <= count; i++) tbody.appendChild(createRow(i));
        recalcTotals();
    }

    function createRow(index) {
        const tr = document.createElement('tr');
        tr.dataset.row = String(index);
        tr.innerHTML =
            '<td class="col-sno">' + index + '</td>' +
            '<td class="col-account"><input type="text" class="account-input" autocomplete="off" data-row="' + index + '">' +
            '<input type="hidden" class="account-id"></td>' +
            '<td class="col-narr"><input type="text" class="desc-input" autocomplete="off"></td>' +
            '<td class="col-ref"><input type="text" class="ref-input" autocomplete="off"></td>' +
            '<td class="col-amount"><input type="text" class="debit-input" inputmode="decimal" autocomplete="off"></td>' +
            '<td class="col-amount"><input type="text" class="credit-input" inputmode="decimal" autocomplete="off"></td>' +
            '<td class="col-branch"><input type="text" class="branch-input" autocomplete="off"></td>' +
            '<td class="col-actions">' +
            '<button type="button" class="busy-row-btn" data-action="edit" title="Edit"><i class="bi bi-pencil"></i></button>' +
            '<button type="button" class="busy-row-btn danger" data-action="delete" title="Delete"><i class="bi bi-trash"></i></button>' +
            '<button type="button" class="busy-row-btn" data-action="dup" title="Duplicate"><i class="bi bi-files"></i></button>' +
            '<button type="button" class="busy-row-btn" data-action="up" title="Move Up"><i class="bi bi-arrow-up"></i></button>' +
            '<button type="button" class="busy-row-btn" data-action="down" title="Move Down"><i class="bi bi-arrow-down"></i></button>' +
            '</td>';

        const accountInput = tr.querySelector('.account-input');
        const descInput = tr.querySelector('.desc-input');
        const refInput = tr.querySelector('.ref-input');
        const debitInput = tr.querySelector('.debit-input');
        const creditInput = tr.querySelector('.credit-input');
        const branchInput = tr.querySelector('.branch-input');

        accountInput.addEventListener('focus', function () { openAccountSearch(accountInput); });

        accountInput.addEventListener('keydown', function (e) {
            if (e.key === 'F3') {
                e.preventDefault();
                openAccountSearch(accountInput);
            }
        });

        accountInput.addEventListener('blur', function () { resolveAccountFromText(accountInput); });

        [debitInput, creditInput].forEach(function (input) {
            input.addEventListener('input', function () {
                // ERP rule: one side only per line — typing Debit clears Credit and vice versa
                if (input === debitInput && parseAmount(debitInput.value) > 0) {
                    creditInput.value = '';
                }
                if (input === creditInput && parseAmount(creditInput.value) > 0) {
                    debitInput.value = '';
                }
                recalcTotals();
            });
            input.addEventListener('blur', function () {
                const value = parseAmount(input.value);
                input.value = value > 0 ? formatAmount(value) : '';
                recalcTotals();
            });
        });

        // Keep entry flow in-grid; never trigger implicit submit/save on Enter.
        [accountInput, descInput, refInput, debitInput, creditInput, branchInput].forEach(function (input, idx, arr) {
            input.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const next = arr[idx + 1];
                if (next) next.focus();
            });
        });

        tr.querySelectorAll('[data-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const action = btn.getAttribute('data-action');
                if (action === 'edit') setEditingRow(tr);
                if (action === 'delete') { tr.remove(); renumberRows(); recalcTotals(); }
                if (action === 'dup') { duplicateRow(tr); }
                if (action === 'up') { moveRow(tr, -1); }
                if (action === 'down') { moveRow(tr, 1); }
            });
        });

        return tr;
    }

    function setEditingRow(row) {
        document.querySelectorAll('#busyGridBody tr').forEach(function (r) { r.classList.remove('editing-row'); });
        editingRow = row;
        row.classList.add('editing-row');
    }

    function duplicateRow(row) {
        const clone = row.cloneNode(true);
        row.after(clone);
        wireRow(clone);
        renumberRows();
        recalcTotals();
    }

    function moveRow(row, dir) {
        const sibling = dir < 0 ? row.previousElementSibling : row.nextElementSibling;
        if (!sibling) return;
        if (dir < 0) sibling.before(row); else sibling.after(row);
        renumberRows();
    }

    function renumberRows() {
        document.querySelectorAll('#busyGridBody tr').forEach(function (row, idx) {
            row.dataset.row = String(idx + 1);
            const sno = row.querySelector('.col-sno');
            if (sno) sno.textContent = String(idx + 1);
        });
    }

    function wireRow(row) {
        const idx = parseInt(row.dataset.row || '1', 10);
        const rebuilt = createRow(idx);
        ['account-input', 'account-id', 'desc-input', 'ref-input', 'debit-input', 'credit-input', 'branch-input'].forEach(function (cls) {
            const src = row.querySelector('.' + cls);
            const dst = rebuilt.querySelector('.' + cls);
            if (src && dst) dst.value = src.value;
        });
        row.replaceWith(rebuilt);
    }

    async function loadAccounts() {
        try {
            const res = await fetch(apiUrl({ acc_action: 'list_accounts' }));
            const data = await res.json();
            if (data.ok && Array.isArray(data.data)) {
                accounts = data.data;
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function loadPaymentModeSettings() {
        try {
            const res = await fetch(apiUrl({ acc_action: 'payment_mode_settings' }));
            const data = await res.json();
            if (data.ok && Array.isArray(data.data)) {
                paymentModeSettings = {};
                data.data.forEach(function (row) {
                    paymentModeSettings[row.payment_mode] = row;
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    function resolveSettingsMode(mode) {
        let resolved = (mode || 'CASH').toUpperCase();
        if (resolved === 'PETTY_CASH') {
            resolved = 'CASH';
        }
        if (resolved === 'ONLINE' || resolved === 'OTHER') {
            resolved = 'BANK';
        }
        return resolved;
    }

    function getPaymentModeAccount(mode) {
        const settingsMode = resolveSettingsMode(mode || document.getElementById('busyPaymentMode')?.value);
        const setting = paymentModeSettings[settingsMode];
        if (!setting) {
            return null;
        }
        return accounts.find(function (a) {
            return String(a.id) === String(setting.account_id);
        }) || {
            id: setting.account_id,
            account_code: setting.account_code,
            account_name: setting.account_name,
        };
    }

    function getExcludedAccountIds() {
        return [];
    }

    function resolveAccountFromText(input) {
        const text = input.value.trim();
        if (!text) {
            clearAccountOnRow(input.closest('tr'));
            return;
        }
        const match = resolveAccountMatch(text);
        if (match) {
            setAccountOnRow(input.closest('tr'), match);
        }
    }

    function normalizeAccountText(v) {
        return String(v || '')
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function resolveAccountMatch(text) {
        const q = normalizeAccountText(text);
        if (!q) return null;
        const exact = accounts.find(function (a) {
            return normalizeAccountText(a.account_name) === q || normalizeAccountText(a.account_code) === q;
        });
        if (exact) return exact;
        const starts = accounts.find(function (a) {
            return normalizeAccountText(a.account_name).startsWith(q) || normalizeAccountText(a.account_code).startsWith(q);
        });
        if (starts) return starts;
        return accounts.find(function (a) {
            return normalizeAccountText(a.account_name).includes(q) || normalizeAccountText(a.account_code).includes(q);
        }) || null;
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
        if (!el || !editingRow) {
            return;
        }
        const accountId = editingRow.querySelector('.account-id').value;
        if (!accountId) {
            el.textContent = '( Cur. Bal. : ' + formatMoneyLabel(0) + ' )';
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
        return '( Cur. Bal. : ' + formatMoneyLabel(val) + ' ' + side + ' )';
    }

    function formatMoneyLabel(n) {
        if (window.TMS && typeof window.TMS.formatMoney === 'function') {
            return window.TMS.formatMoney(n);
        }
        return 'LKR ' + formatAmount(n);
    }

    function parseAmount(raw) {
        if (raw === null || raw === undefined) {
            return 0;
        }
        // Accept formatted money like "LKR 1,234.50" or "Rs. 1,234.50"
        const cleaned = String(raw)
            .replace(/,/g, '')
            .replace(/[^\d.\-]/g, '')
            .trim();
        const n = parseFloat(cleaned);
        return isNaN(n) ? 0 : n;
    }

    function formatAmount(n) {
        if (window.TMS && typeof window.TMS.formatMoney === 'function') {
            return window.TMS.formatMoney(n, false);
        }
        return (parseFloat(n) || 0).toLocaleString('en-LK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function recalcTotals() {
        const lines = collectLineItems({ silent: true });
        let debit = 0;
        let credit = 0;
        lines.forEach(function (l) {
            debit += parseAmount(l.debit_amount);
            credit += parseAmount(l.credit_amount);
        });
        const diff = Math.abs(debit - credit);
        document.getElementById('busyTotalDebit').textContent = formatAmount(debit);
        document.getElementById('busyTotalCredit').textContent = formatAmount(credit);
        document.getElementById('busyTotalDiff').textContent = formatAmount(diff);
        const badge = document.getElementById('busyBalanceStatus');
        badge.textContent = 'Entry Total';
        badge.classList.remove('is-balanced', 'is-unbalanced');
        updateStatusText();
    }

    function collectLineItems(opts) {
        opts = opts || {};
        const items = [];
        getActualVoucherRows().forEach(function (row) {
            const accountName = (row.querySelector('.account-input')?.value || '').trim();
            let accountId = (row.querySelector('.account-id')?.value || '').trim();
            const debit = parseAmount(row.querySelector('.debit-input')?.value || '');
            const credit = parseAmount(row.querySelector('.credit-input')?.value || '');
            const desc = (row.querySelector('.desc-input')?.value || '').trim();
            const ref = (row.querySelector('.ref-input')?.value || '').trim();
            const branch = (row.querySelector('.branch-input')?.value || '').trim();

            if (!accountName && !accountId && debit <= 0 && credit <= 0) return;
            if (!accountId && accountName) {
                const match = resolveAccountMatch(accountName);
                if (match) accountId = String(match.id);
            }
            if (!accountId) {
                if (!opts.silent) throw new Error('Account is required.');
                return;
            }
            if (debit < 0 || credit < 0) {
                if (!opts.silent) throw new Error('Amounts cannot be negative.');
                return;
            }
            if (debit <= 0 && credit <= 0) {
                if (!opts.silent) throw new Error('Enter a debit or credit amount.');
                return;
            }
            if (debit > 0 && credit > 0) {
                if (!opts.silent) throw new Error('Enter either Debit or Credit on a line, not both.');
                return;
            }
            items.push({
                account_id: parseInt(accountId, 10),
                account_name: accountName,
                debit_amount: debit,
                credit_amount: credit,
                narration: [desc, ref, branch].filter(Boolean).join(' | ')
            });
        });
        return items;
    }

    function getActualVoucherRows() {
        return Array.from(document.querySelectorAll('#busyGridBody tr')).filter(function (row) {
            if (!row || row.hidden) {
                return false;
            }
            if (row.classList.contains('template-row') || row.classList.contains('placeholder-row') || row.classList.contains('deleted-row')) {
                return false;
            }
            if (row.style && row.style.display === 'none') {
                return false;
            }
            return true;
        });
    }

    function buildVoucherPayload() {
        const lines = collectLineItems();
        const validLines = lines.filter(function (line) {
            const account = parseInt(line.account_id || 0, 10);
            const debit = parseAmount(line.debit_amount);
            const credit = parseAmount(line.credit_amount);
            if (account <= 0) return false;
            if (debit < 0 || credit < 0) return false;
            if (debit > 0 && credit > 0) return false;
            return debit > 0 || credit > 0;
        });
        if (validLines.length < 1) {
            throw new Error('At least one valid voucher line is required.');
        }

        // Single-entry UI: server appends payment-mode (Cash/Bank) balancing line.
        // Do not block save when the user entered only Debit or only Credit.

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
            header_narration: document.getElementById('busyHeaderNarration')?.value || '',
            narration: document.getElementById('busyLongNarration')?.value || '',
            details: validLines,
        };
    }

    async function saveVoucher() {
        document.querySelectorAll('#busyGridBody .account-input').forEach(function (input) {
            resolveAccountFromText(input);
        });

        let payload;
        try {
            payload = buildVoucherPayload();
            const tbodyRowCount = document.querySelectorAll('#busyGridBody tr').length;
            const activeRows = getActualVoucherRows().length;
            const totals = payload.details.reduce(function (acc, line) {
                acc.debit += parseAmount(line.debit_amount);
                acc.credit += parseAmount(line.credit_amount);
                return acc;
            }, { debit: 0, credit: 0 });
            console.log('[Voucher] voucherLines:', payload.details);
            console.log('[Voucher] tbody row count:', tbodyRowCount);
            console.log('[Voucher] active voucher rows:', activeRows);
            console.log('[Voucher] payload details length:', payload.details.length);
            console.log('[Voucher] totals:', totals, 'difference=', Math.abs(totals.debit - totals.credit));
            console.log('[Voucher] submit payload:', payload);
        } catch (e) {
            showAlert(e.message, 'danger');
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
                if (window.AccModule && AccModule.toast) {
                    AccModule.toast('Voucher saved successfully.');
                } else {
                    showAlert('Voucher saved successfully.', 'success');
                }
                if ((data.data.status || '') === 'POSTED') {
                    console.log('[Voucher] posted to ledger:', data.data.id);
                }
            } else {
                showAlert(data.error || 'Save failed', 'danger');
            }
        } catch (e) {
            showAlert('Error saving voucher: ' + e.message, 'danger');
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
                    showAlert('Voucher saved and posted to ledger.', 'success');
                }
            } else {
                showAlert('Saved as draft. Posting failed: ' + (data.error || 'Unknown error'), 'warning');
            }
        } catch (e) {
            showAlert('Saved as draft. Posting failed: ' + e.message, 'warning');
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
            if (e.ctrlKey && e.key.toLowerCase() === 's') {
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
        const excluded = getExcludedAccountIds();
        let list = accounts;
        if (excluded.length) {
            list = accounts.filter(function (a) {
                return excluded.indexOf(parseInt(a.id, 10)) < 0;
            });
        }
        if (!q) {
            return list.slice(0, 30);
        }
        return list.filter(function (a) {
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
            setEditingRow(row);
            updateCurrentBalance();
        }
        closeAccountSearch();
        focusAmountAfterAccountSelect(row);
    }

    /**
     * After picking an account, focus the amount column that needs an entry.
     * Previous behaviour always focused Debit — Journal credit lines were missed
     * and only Debit reached the backend (Debit X ≠ Credit 0.00).
     */
    function focusAmountAfterAccountSelect(row) {
        if (!row) return;
        const debit = row.querySelector('.debit-input');
        const credit = row.querySelector('.credit-input');
        const lines = collectLineItems({ silent: true });
        let totalDr = 0;
        let totalCr = 0;
        lines.forEach(function (l) {
            totalDr += parseAmount(l.debit_amount);
            totalCr += parseAmount(l.credit_amount);
        });

        const needsCredit = totalDr > totalCr + 0.009;
        const needsDebit = totalCr > totalDr + 0.009;

        if ((voucherType === 'JOURNAL' || voucherType === 'CONTRA' || voucherType === 'TRANSFER') && needsCredit) {
            credit?.focus();
            return;
        }
        if ((voucherType === 'JOURNAL' || voucherType === 'CONTRA' || voucherType === 'TRANSFER') && needsDebit) {
            debit?.focus();
            return;
        }
        if (debit && parseAmount(debit.value) <= 0) {
            debit.focus();
            return;
        }
        if (credit && parseAmount(credit.value) <= 0) {
            credit.focus();
            return;
        }
        debit?.focus();
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

        let details = voucher.details || [];

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
            if (d.account_name) {
                row.querySelector('.account-input').value = d.account_name || '';
                row.querySelector('.account-id').value = d.account_id || '';
            }
            const debit = parseFloat(d.debit_amount || 0);
            const credit = parseFloat(d.credit_amount || 0);
            row.querySelector('.debit-input').value = debit > 0 ? formatAmount(debit) : '';
            row.querySelector('.credit-input').value = credit > 0 ? formatAmount(credit) : '';
            row.querySelector('.desc-input').value = d.narration || '';
        });

        recalcTotals();
    }

    function updateStatusText() {
        const text = document.getElementById('busyAutoLineText');
        if (!text) return;
        const mode = document.getElementById('busyPaymentMode')?.value || 'CASH';
        text.textContent = 'Single-entry mode: opposite ' + mode + ' account posts automatically';
    }

    function showAlert(message, type) {
        const host = document.getElementById('busyAlertHost');
        if (!host) return alert(message);
        host.innerHTML = '<div class="alert alert-' + (type || 'danger') + ' py-2 mb-2" role="alert">' + escapeHtml(message) + '</div>';
        setTimeout(function () { if (host) host.innerHTML = ''; }, 4000);
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

    // Intentionally not exposing save globally; save must run only via explicit Save/F2/Ctrl+S.
    window.busyQuitVoucher = quitVoucher;
})();
