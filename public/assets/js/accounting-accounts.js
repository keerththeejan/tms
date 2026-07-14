(function () {
  'use strict';

  var accounts = [];
  var groups = [];
  var openingMode = !!(window.TMS_ACCOUNTS_MASTER && window.TMS_ACCOUNTS_MASTER.openingMode);
  var pendingGroupSelectId = null;
  var savingAccount = false;
  var savingGroup = false;
  var balanceTypeManual = false;
  var codeMode = 'auto';
  var searchTimer = null;
  var modalInstances = {};
  var state = {
    page: 1,
    limit: 25,
    total: 0,
    sort: 'account_code',
    order: 'ASC',
  };

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    if (typeof window.AccModule === 'undefined') {
      console.error('[COA] AccModule is not loaded.');
      return;
    }

    if (Array.isArray(window.TMS_ACCOUNTS_MASTER?.groupsBoot) && window.TMS_ACCOUNTS_MASTER.groupsBoot.length) {
      groups = window.TMS_ACCOUNTS_MASTER.groupsBoot.slice();
      fillGroupSelect();
    }

    placeModalsAtBodyRoot();
    initModalLifecycle();
    bindGlobalEvents();
    bindFilters();
    loadGroups(false);
    loadAccounts();
  }

  /** Move modals once to document.body so overflow:hidden ancestors cannot trap focus/clicks. */
  function placeModalsAtBodyRoot() {
    ['accAccountModal', 'accGroupModal'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el && el.parentElement !== document.body) {
        document.body.appendChild(el);
      }
    });
  }

  function initModalLifecycle() {
    ['accAccountModal', 'accGroupModal'].forEach(function (id) {
      var el = document.getElementById(id);
      if (!el || typeof bootstrap === 'undefined') return;
      el.addEventListener('hidden.bs.modal', cleanupModalState);
      el.addEventListener('shown.bs.modal', function () {
        var openCount = document.querySelectorAll('.modal.show').length;
        var backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > openCount) {
          for (var i = openCount; i < backdrops.length; i++) {
            backdrops[i].remove();
          }
        }
      });
    });
  }

  function cleanupModalState() {
    var openModals = document.querySelectorAll('.modal.show');
    if (openModals.length === 0) {
      document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('padding-right');
    }
  }

  function getModalInstance(id) {
    if (typeof bootstrap === 'undefined') return null;
    var el = document.getElementById(id);
    if (!el) return null;
    if (!modalInstances[id]) {
      modalInstances[id] = bootstrap.Modal.getOrCreateInstance(el, {
        backdrop: true,
        keyboard: true,
        focus: true,
      });
    }
    return modalInstances[id];
  }

  /** Delegated handlers survive select innerHTML rebuilds and modal reparenting. */
  function bindGlobalEvents() {
    var master = document.querySelector('.acc-accounts-master');

    if (master) {
      master.addEventListener('click', function (e) {
        if (e.target.closest('#accCoaNewBtn')) { openNew(); return; }
        if (e.target.closest('#accCoaNewGroupBtn')) { openNewGroup(); return; }
        if (e.target.closest('#accCoaPrevBtn')) {
          if (state.page > 1) { state.page -= 1; loadAccounts(); }
          return;
        }
        if (e.target.closest('#accCoaNextBtn')) {
          var maxPage = Math.max(1, Math.ceil(state.total / state.limit));
          if (state.page < maxPage) { state.page += 1; loadAccounts(); }
          return;
        }
        var sortTh = e.target.closest('th.acc-sortable');
        if (sortTh && sortTh.dataset.sort) {
          var col = sortTh.dataset.sort;
          if (state.sort === col) state.order = state.order === 'ASC' ? 'DESC' : 'ASC';
          else { state.sort = col; state.order = 'ASC'; }
          state.page = 1;
          loadAccounts();
          return;
        }
        var editBtn = e.target.closest('[data-edit]');
        if (editBtn) openEdit(parseInt(editBtn.getAttribute('data-edit'), 10));
      });
    }

    document.addEventListener('click', function (e) {
      if (e.target.closest('#accAddGroupInlineBtn')) { openNewGroup(); return; }
      if (e.target.closest('#accSeedGroupsBtn')) { seedDefaultGroups(); return; }
    });

    var accountForm = document.getElementById('accAccountForm');
    if (accountForm) {
      accountForm.addEventListener('submit', saveAccount);
      accountForm.addEventListener('change', function (e) {
        if (e.target.id === 'accAccountGroup') {
          onGroupChanged();
        }
        if (e.target.id === 'accOpeningType') {
          balanceTypeManual = true;
        }
        if (e.target.id === 'accCodeModeAuto' || e.target.id === 'accCodeModeManual') {
          applyCodeModeUI();
        }
      });
      accountForm.addEventListener('input', function (e) {
        if (e.target.id === 'accAccountName' || e.target.id === 'accOpeningBalance') {
          clearFieldError(e.target.id);
        }
        if (e.target.id === 'accAccountName' || e.target.id === 'accOpeningBalance' || e.target.id === 'accAccountCode') {
          if (e.target.id === 'accAccountCode') {
            clearFieldError('accAccountCode');
          }
          updateSaveButtonState();
        }
      });
    }

    var groupForm = document.getElementById('accGroupForm');
    if (groupForm) {
      groupForm.addEventListener('submit', saveGroup);
      groupForm.addEventListener('change', function (e) {
        if (e.target.id === 'accGroupType') syncGroupNatureDefault();
      });
    }

    document.getElementById('accAccountDeleteBtn')?.addEventListener('click', deleteAccount);

    var accountModal = document.getElementById('accAccountModal');
    if (accountModal) {
      accountModal.addEventListener('show.bs.modal', function () {
        cleanupModalState();
        loadGroups(true).then(updateSaveButtonState);
      });
      accountModal.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && e.target.tagName !== 'TEXTAREA') {
          var submitBtn = document.getElementById('accAccountSaveBtn');
          if (submitBtn && !submitBtn.disabled && !savingAccount) {
            e.preventDefault();
            accountForm?.requestSubmit();
          }
        }
      });
      accountModal.addEventListener('hidden.bs.modal', function () {
        clearFieldErrors();
        setSaveLoading('accAccountSaveBtn', false);
        savingAccount = false;
        updateSaveButtonState();
      });
    }

    var groupModal = document.getElementById('accGroupModal');
    if (groupModal) {
      groupModal.addEventListener('hidden.bs.modal', function () {
        setSaveLoading('accGroupSaveBtn', false);
        savingGroup = false;
        if (pendingGroupSelectId) {
          setGroupValue(String(pendingGroupSelectId));
          pendingGroupSelectId = null;
        }
        updateSaveButtonState();
      });
    }
  }

  function bindFilters() {
    document.getElementById('accCoaSearch')?.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () { state.page = 1; loadAccounts(); }, 300);
    });
    document.getElementById('accCoaGroupFilter')?.addEventListener('change', function () { state.page = 1; loadAccounts(); });
    document.getElementById('accCoaStatusFilter')?.addEventListener('change', function () { state.page = 1; loadAccounts(); });
  }

  function groupSelectEl() { return document.getElementById('accAccountGroup'); }

  function getGroupValue() { return AccModule.getSelectValue(groupSelectEl()); }

  function setGroupValue(value) {
    AccModule.setSelectValue(groupSelectEl(), value || '');
    updateSaveButtonState();
  }

  function rebuildSelectOptions(sel, placeholder, options) {
    if (!sel) return;
    var previous = sel.value;
    sel.textContent = '';
    var ph = document.createElement('option');
    ph.value = '';
    ph.textContent = placeholder;
    sel.appendChild(ph);
    options.forEach(function (opt) {
      sel.appendChild(opt);
    });
    if (previous) sel.value = previous;
  }

  function loadAccounts() {
    var tbody = document.querySelector('#accCoaTable tbody');
    var cols = document.getElementById('accCoaTable')?.dataset.colCount || '7';
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="' + cols + '" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span> Loading…</td></tr>';
    }

    return AccModule.fetchJson({
      acc_action: 'chart_accounts',
      q: document.getElementById('accCoaSearch')?.value || '',
      group_type: document.getElementById('accCoaGroupFilter')?.value || '',
      status: document.getElementById('accCoaStatusFilter')?.value || '',
      sort: state.sort,
      order: state.order,
      page_no: String(state.page),
      limit: String(state.limit),
      _t: String(Date.now()),
    }).then(function (res) {
      if (!res.ok && !res.success) throw new Error(res.message || res.error || 'Accounts load failed');
      accounts = res.data || [];
      state.total = parseInt(res.total, 10) || accounts.length;
      renderTable();
      updatePagination();
    }).catch(function (e) {
      console.error('[COA] loadAccounts:', e);
      AccModule.toast(String(e.message || e), 'error');
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="' + cols + '" class="text-center text-danger py-3">' + escapeHtml(String(e.message || e)) + '</td></tr>';
      }
    });
  }

  function loadGroups(silent) {
    return AccModule.fetchJson({ acc_action: 'list_account_groups', _t: String(Date.now()) }).then(function (res) {
      if (!res.ok && !res.success) throw new Error(res.message || res.error || 'Account groups load failed');
      groups = res.data || [];
      fillGroupSelect();
      updateSaveButtonState();
      return groups;
    }).catch(function (e) {
      console.error('[COA] loadGroups:', e);
      if (!groups.length) fillGroupSelect();
      if (!silent) AccModule.toast(String(e.message || e), 'error');
      updateSaveButtonState();
      return groups;
    });
  }

  function upsertGroup(group) {
    if (!group || group.id == null) return;
    var id = String(group.id);
    var idx = groups.findIndex(function (g) { return String(g.id) === id; });
    if (idx >= 0) groups[idx] = Object.assign({}, groups[idx], group);
    else groups.push(group);
    fillGroupSelect();
  }

  function fillGroupSelect() {
    var sel = groupSelectEl();
    var emptyBox = document.getElementById('accGroupEmptyState');
    if (!sel) return;

    var previous = getGroupValue();

    if (!groups.length) {
      rebuildSelectOptions(sel, '— Select account group —', []);
      if (emptyBox) emptyBox.classList.remove('d-none');
      updateSaveButtonState();
      return;
    }

    if (emptyBox) emptyBox.classList.add('d-none');
    var sorted = sortGroupsForSelect(groups);
    var opts = sorted.map(function (g) {
      var indent = g._depth > 0 ? '\u00A0\u00A0'.repeat(g._depth) + '\u2514 ' : '';
      var o = document.createElement('option');
      o.value = String(g.id);
      o.textContent = indent + (g.group_name || '') + ' (' + (g.group_code || '') + ')';
      return o;
    });
    rebuildSelectOptions(sel, '— Select account group —', opts);
    fillParentGroupSelect();

    if (pendingGroupSelectId) setGroupValue(String(pendingGroupSelectId));
    else if (previous) setGroupValue(previous);
    updateSaveButtonState();
  }

  function sortGroupsForSelect(list) {
    var byId = {};
    list.forEach(function (g) { byId[String(g.id)] = g; });
    function depth(g) {
      var d = 0, cur = g, guard = 0;
      while (cur && cur.parent_id && byId[String(cur.parent_id)] && guard < 10) {
        d++; cur = byId[String(cur.parent_id)]; guard++;
      }
      return d;
    }
    return list.slice().map(function (g) {
      return Object.assign({}, g, { _depth: depth(g) });
    }).sort(function (a, b) {
      if (a.sort_order !== b.sort_order) return Number(a.sort_order || 0) - Number(b.sort_order || 0);
      return String(a.group_name || '').localeCompare(String(b.group_name || ''));
    });
  }

  function fillParentGroupSelect() {
    var sel = document.getElementById('accGroupParent');
    if (!sel) return;
    var current = sel.value;
    var sorted = sortGroupsForSelect(groups);
    var opts = sorted.map(function (g) {
      var indent = g._depth > 0 ? '\u00A0\u00A0'.repeat(g._depth) + '\u2514 ' : '';
      var o = document.createElement('option');
      o.value = String(g.id);
      o.textContent = indent + (g.group_name || '');
      return o;
    });
    rebuildSelectOptions(sel, '— None (top level) —', opts);
    if (current) sel.value = current;
    sel.dispatchEvent(new Event('refresh-choices', { bubbles: true }));
  }

  function syncGroupNatureDefault() {
    var type = document.getElementById('accGroupType')?.value || 'EXPENSES';
    var natureEl = document.getElementById('accGroupNature');
    if (!natureEl) return;
    natureEl.value = (type === 'LIABILITIES' || type === 'CAPITAL' || type === 'INCOME') ? 'CREDIT' : 'DEBIT';
  }

  function balanceTypeForGroup(groupId) {
    var g = groups.find(function (row) { return String(row.id) === String(groupId); });
    if (!g) return 'DEBIT';
    var type = String(g.group_type || '').toUpperCase();
    if (type === 'LIABILITIES' || type === 'CAPITAL' || type === 'INCOME') return 'CREDIT';
    return 'DEBIT';
  }

  function accountTypeForGroup(groupId) {
    var g = groups.find(function (row) { return String(row.id) === String(groupId); });
    if (!g) return 'GENERAL';
    var type = String(g.group_type || '').toUpperCase();
    if (type === 'ASSETS') return 'ASSET';
    if (type === 'LIABILITIES') return 'LIABILITY';
    if (type === 'CAPITAL') return 'CAPITAL';
    if (type === 'INCOME') return 'INCOME';
    if (type === 'EXPENSES') return 'EXPENSE';
    return 'GENERAL';
  }

  function onGroupChanged() {
    var groupId = getGroupValue();
    clearFieldError('accAccountGroup');
    if (!groupId) { updateSaveButtonState(); return; }
    var side = balanceTypeForGroup(groupId);
    if (!balanceTypeManual) {
      document.getElementById('accOpeningType').value = side;
    }
    var nbEl = document.getElementById('accNormalBalance');
    if (nbEl && !document.getElementById('accAccountId').value) {
      nbEl.value = side;
    }
    var atEl = document.getElementById('accAccountType');
    if (atEl && !document.getElementById('accAccountId').value) {
      atEl.value = accountTypeForGroup(groupId);
    }
    if (!document.getElementById('accAccountId').value) {
      if (getCodeMode() === 'auto') {
        fetchNextCode(groupId);
      }
    }
    updateSaveButtonState();
  }

  function fillParentAccountSelect(excludeId) {
    var sel = document.getElementById('accParentAccount');
    if (!sel) return;
    var current = sel.value;
    sel.innerHTML = '<option value="">— None —</option>';
    accounts.forEach(function (a) {
      if (excludeId && String(a.id) === String(excludeId)) return;
      sel.innerHTML += '<option value="' + a.id + '">' + escapeHtml(a.account_code + ' - ' + a.account_name) + '</option>';
    });
    if (current) sel.value = current;
  }

  function getCodeMode() {
    var manual = document.getElementById('accCodeModeManual');
    return manual && manual.checked ? 'manual' : 'auto';
  }

  function setCodeMode(mode) {
    var auto = document.getElementById('accCodeModeAuto');
    var manual = document.getElementById('accCodeModeManual');
    if (mode === 'manual' && manual) manual.checked = true;
    else if (auto) auto.checked = true;
    codeMode = getCodeMode();
    applyCodeModeUI();
  }

  function applyCodeModeUI() {
    codeMode = getCodeMode();
    var codeEl = document.getElementById('accAccountCode');
    var isEdit = !!document.getElementById('accAccountId').value;
    if (!codeEl || isEdit) return;

    if (codeMode === 'manual') {
      codeEl.readOnly = false;
      codeEl.value = '';
    } else {
      codeEl.readOnly = true;
      fetchNextCode(getGroupValue() || 0);
    }
    updateSaveButtonState();
  }

  function fetchNextCode(groupId) {
    if (getCodeMode() === 'manual' && !document.getElementById('accAccountId').value) {
      return;
    }
    var params = { acc_action: 'next_account_code' };
    if (groupId) params.account_group_id = String(groupId);
    AccModule.fetchJson(params).then(function (res) {
      if (!res.ok && !res.success) return;
      var code = res.data && res.data.account_code;
      var codeEl = document.getElementById('accAccountCode');
      if (code && codeEl && !document.getElementById('accAccountId').value) {
        codeEl.value = code;
        codeEl.readOnly = true;
      }
    }).catch(function (e) { console.warn('[COA] fetchNextCode:', e); });
  }

  function openNewGroup() {
    loadGroups(true).then(function () {
      var form = document.getElementById('accGroupForm');
      if (form) form.reset();
      fillParentGroupSelect();
      syncGroupNatureDefault();
      showModal('accGroupModal');
    });
  }

  function saveGroup(e) {
    e.preventDefault();
    if (savingGroup) return;
    var name = document.getElementById('accGroupName').value.trim();
    if (!name) {
      setFieldError('accGroupName', 'Group name is required.');
      AccModule.toast('Group name is required.', 'warning');
      return;
    }
    clearFieldErrors();
    savingGroup = true;
    setSaveLoading('accGroupSaveBtn', true);

    AccModule.postJson({ acc_action: 'save_account_group' }, {
      group_code: document.getElementById('accGroupCode').value.trim(),
      group_name: name,
      parent_id: document.getElementById('accGroupParent').value || null,
      group_type: document.getElementById('accGroupType').value,
      nature: document.getElementById('accGroupNature').value,
      description: document.getElementById('accGroupDescription')?.value.trim() || '',
      is_active: document.getElementById('accGroupStatus')?.value || '1',
    }).then(function (res) {
      if (!res.ok && !res.success) throw new Error(res.message || res.error || 'Save failed');
      if (res.data) {
        upsertGroup(res.data);
        pendingGroupSelectId = res.data.id;
      }
      AccModule.toast(res.message || 'Account group created successfully.');
      hideModal('accGroupModal');
      return loadGroups(true);
    }).catch(function (err) {
      console.error('[COA] saveGroup:', err);
      AccModule.toast(String(err.message || err), 'error');
    }).finally(function () {
      savingGroup = false;
      setSaveLoading('accGroupSaveBtn', false);
    });
  }

  function seedDefaultGroups() {
    var btn = document.getElementById('accSeedGroupsBtn');
    if (btn) btn.disabled = true;
    AccModule.postJson({ acc_action: 'seed_default_account_groups' }, {}).then(function (res) {
      if (!res.ok && !res.success) throw new Error(res.message || res.error || 'Could not create default groups');
      groups = res.data || [];
      fillGroupSelect();
      AccModule.toast(res.message || 'Default account groups ready');
      updateSaveButtonState();
    }).catch(function (e) {
      AccModule.toast(String(e.message || e), 'error');
    }).finally(function () {
      if (btn) btn.disabled = false;
    });
  }

  function renderTable() {
    var tbody = document.querySelector('#accCoaTable tbody');
    if (!tbody) return;
    var cols = parseInt(document.getElementById('accCoaTable')?.dataset.colCount || '7', 10);
    if (!accounts.length) {
      tbody.innerHTML = '<tr><td colspan="' + cols + '" class="text-center text-muted py-3">No accounts found</td></tr>';
      return;
    }
    tbody.innerHTML = accounts.map(function (a) {
      var ob = parseFloat(a.opening_balance || 0);
      var bal = parseFloat(a.current_balance || 0);
      return '<tr>' +
        '<td><code>' + escapeHtml(a.account_code) + '</code></td>' +
        '<td>' + escapeHtml(a.account_name) + '</td>' +
        '<td>' + escapeHtml(a.group_name || '') + '</td>' +
        '<td><span class="badge text-bg-light border">' + escapeHtml(a.group_type || '') + '</span></td>' +
        (openingMode ? '<td class="text-end">' + AccModule.money(ob) + '</td><td>' + escapeHtml(a.opening_balance_type || '') + '</td>' : '') +
        '<td class="text-end fw-semibold">' + AccModule.money(bal) + '</td>' +
        '<td>' + (String(a.is_active) === '1' ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>') + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary" data-edit="' + a.id + '" title="Edit"><i class="bi bi-pencil"></i></button></td>' +
        '</tr>';
    }).join('');
  }

  function updatePagination() {
    var maxPage = Math.max(1, Math.ceil(state.total / state.limit));
    var info = document.getElementById('accCoaPageInfo');
    if (info) {
      var from = state.total === 0 ? 0 : ((state.page - 1) * state.limit + 1);
      var to = Math.min(state.page * state.limit, state.total);
      info.textContent = 'Showing ' + from + '–' + to + ' of ' + state.total;
    }
    var prev = document.getElementById('accCoaPrevBtn');
    var next = document.getElementById('accCoaNextBtn');
    if (prev) prev.disabled = state.page <= 1;
    if (next) next.disabled = state.page >= maxPage;
  }

  function openNew() {
    var modalEl = document.getElementById('accAccountModal');
    if (modalEl?.classList.contains('show')) return;

    document.getElementById('accAccountModalTitle').innerHTML = '<i class="bi bi-plus-circle"></i> New Account';
    document.getElementById('accAccountForm').reset();
    document.getElementById('accAccountId').value = '';
    document.getElementById('accOpeningBalance').value = '0.00';
    document.getElementById('accAccountDeleteWrap')?.classList.add('d-none');
    document.getElementById('accCodeModeWrap')?.classList.remove('d-none');
    fillParentAccountSelect(null);
    var nb = document.getElementById('accNormalBalance');
    if (nb) nb.value = 'DEBIT';
    var at = document.getElementById('accAccountType');
    if (at) at.value = 'GENERAL';
    var lt = document.getElementById('accLedgerType');
    if (lt) lt.value = 'GENERAL';
    pendingGroupSelectId = null;
    balanceTypeManual = false;
    setCodeMode('auto');
    clearFieldErrors();
    setGroupValue('');
    loadGroups(true).finally(function () {
      showModal('accAccountModal');
      applyCodeModeUI();
      updateSaveButtonState();
    });
  }

  function openEdit(id) {
    loadGroups(true).then(function () {
      return AccModule.fetchJson({ acc_action: 'get_account', id: String(id) });
    }).then(function (res) {
      if (!res.ok && !res.success) throw new Error(res.message || res.error || 'Load failed');
      var a = res.data;
      document.getElementById('accAccountModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Account';
      document.getElementById('accAccountId').value = a.id;
      document.getElementById('accAccountCode').value = a.account_code;
      document.getElementById('accAccountCode').readOnly = true;
      document.getElementById('accAccountName').value = a.account_name;
      setGroupValue(String(a.account_group_id || ''));
      document.getElementById('accOpeningBalance').value = parseFloat(a.opening_balance || 0).toFixed(2);
      document.getElementById('accOpeningType').value = a.opening_balance_type || 'DEBIT';
      document.getElementById('accAccountActive').value = String(a.is_active ?? 1);
      fillParentAccountSelect(a.id);
      var nb = document.getElementById('accNormalBalance');
      if (nb) nb.value = a.normal_balance || a.group_nature || a.opening_balance_type || 'DEBIT';
      var at = document.getElementById('accAccountType');
      if (at) at.value = a.account_type || accountTypeForGroup(a.account_group_id) || 'GENERAL';
      var lt = document.getElementById('accLedgerType');
      if (lt) lt.value = a.ledger_type || 'GENERAL';
      var pa = document.getElementById('accParentAccount');
      if (pa) pa.value = a.parent_account_id ? String(a.parent_account_id) : '';
      document.getElementById('accAccountDeleteWrap')?.classList.remove('d-none');
      document.getElementById('accCodeModeWrap')?.classList.add('d-none');
      balanceTypeManual = true;
      clearFieldErrors();
      showModal('accAccountModal');
      updateSaveButtonState();
    }).catch(function (e) { AccModule.toast(String(e.message || e), 'error'); });
  }

  function validateClientForm() {
    clearFieldErrors();
    var ok = true;
    var groupId = getGroupValue();
    var name = document.getElementById('accAccountName').value.trim();
    var code = document.getElementById('accAccountCode').value.trim();
    var opening = document.getElementById('accOpeningBalance').value;

    if (!groupId) {
      setFieldError('accAccountGroup', 'Please select an account group.');
      ok = false;
    }
    if (!name) {
      setFieldError('accAccountName', 'Account name is required.');
      ok = false;
    } else if (name.length < 3) {
      setFieldError('accAccountName', 'Account name must be at least 3 characters.');
      ok = false;
    }
    if (!document.getElementById('accAccountId').value) {
      var mode = getCodeMode();
      if (mode === 'manual') {
        if (!code) {
          setFieldError('accAccountCode', 'Account code is required.');
          ok = false;
        } else if (!/^\d+$/.test(code)) {
          setFieldError('accAccountCode', 'Account code must be numeric.');
          ok = false;
        }
      }
    }
    if (opening !== '' && isNaN(parseFloat(opening))) {
      setFieldError('accOpeningBalance', 'Opening balance must be numeric.');
      ok = false;
    }
    return ok;
  }

  function saveAccount(e) {
    e.preventDefault();
    if (savingAccount) return;
    if (!validateClientForm()) {
      AccModule.toast('Please fix the highlighted fields.', 'warning');
      return;
    }

    savingAccount = true;
    setSaveLoading('accAccountSaveBtn', true);

    var payload = {
      id: document.getElementById('accAccountId').value || 0,
      code_mode: getCodeMode(),
      account_code: document.getElementById('accAccountCode').value.trim(),
      account_name: document.getElementById('accAccountName').value.trim(),
      account_group_id: getGroupValue(),
      parent_account_id: document.getElementById('accParentAccount')?.value || '',
      opening_balance: document.getElementById('accOpeningBalance').value || '0',
      opening_balance_type: document.getElementById('accOpeningType').value,
      normal_balance: document.getElementById('accNormalBalance')?.value || document.getElementById('accOpeningType').value,
      account_type: document.getElementById('accAccountType')?.value || 'GENERAL',
      ledger_type: document.getElementById('accLedgerType')?.value || 'GENERAL',
      is_active: document.getElementById('accAccountActive').value,
    };

    AccModule.postJson({ acc_action: 'save_account' }, payload).then(function (res) {
      if (!res.ok && !res.success) {
        applyServerErrors(res.errors || {});
        throw new Error(res.message || res.error || 'Save failed');
      }
      AccModule.toast(res.message || 'Account created successfully.');
      hideModal('accAccountModal');
      state.page = 1;
      return loadAccounts();
    }).catch(function (err) {
      console.error('[COA] saveAccount:', err);
      AccModule.toast(String(err.message || err), 'error');
    }).finally(function () {
      savingAccount = false;
      setSaveLoading('accAccountSaveBtn', false);
      updateSaveButtonState();
    });
  }

  function deleteAccount() {
    var id = parseInt(document.getElementById('accAccountId').value, 10);
    if (!id || savingAccount) return;
    if (!confirm('Soft-delete this account? It will be marked inactive and hidden from active lists.')) return;

    savingAccount = true;
    setSaveLoading('accAccountSaveBtn', true);
    AccModule.postJson({ acc_action: 'delete_account' }, { id: id }).then(function (res) {
      if (!res.ok && !res.success) throw new Error(res.message || res.error || 'Delete failed');
      AccModule.toast(res.message || 'Account deleted.');
      hideModal('accAccountModal');
      loadAccounts();
    }).catch(function (e) {
      AccModule.toast(String(e.message || e), 'error');
    }).finally(function () {
      savingAccount = false;
      setSaveLoading('accAccountSaveBtn', false);
      updateSaveButtonState();
    });
  }

  function updateSaveButtonState() {
    var btn = document.getElementById('accAccountSaveBtn');
    if (!btn || savingAccount) return;
    var ready = groups.length > 0 && !!getGroupValue();
    var name = document.getElementById('accAccountName')?.value.trim() || '';
    if (name.length < 3) ready = false;
    if (!document.getElementById('accAccountId').value && getCodeMode() === 'manual') {
      var code = document.getElementById('accAccountCode')?.value.trim() || '';
      ready = ready && code !== '' && /^\d+$/.test(code);
    }
    btn.disabled = !ready;
  }

  function setSaveLoading(btnId, loading) {
    var btn = document.getElementById(btnId);
    if (!btn) return;
    btn.classList.toggle('acc-is-saving', loading);
    var label = btn.querySelector('.acc-save-label');
    var spinner = btn.querySelector('.acc-save-spinner');
    if (label) label.classList.toggle('d-none', loading);
    if (spinner) spinner.classList.toggle('d-none', !loading);
    if (loading) {
      btn.disabled = true;
    } else if (btnId === 'accAccountSaveBtn') {
      updateSaveButtonState();
    } else {
      btn.disabled = false;
    }
  }

  function setFieldError(fieldId, message) {
    var input = document.getElementById(fieldId);
    var err = document.getElementById(fieldId + 'Error');
    if (input) input.classList.add('is-invalid');
    if (err) err.textContent = message;
  }

  function clearFieldError(fieldId) {
    var input = document.getElementById(fieldId);
    var err = document.getElementById(fieldId + 'Error');
    if (input) input.classList.remove('is-invalid');
    if (err) err.textContent = '';
  }

  function clearFieldErrors() {
    document.querySelectorAll('#accAccountForm .is-invalid, #accGroupForm .is-invalid').forEach(function (el) {
      el.classList.remove('is-invalid');
    });
    document.querySelectorAll('#accAccountForm .invalid-feedback, #accGroupForm .invalid-feedback').forEach(function (el) {
      el.textContent = '';
    });
  }

  function applyServerErrors(errors) {
    var map = {
      account_group_id: 'accAccountGroup',
      account_name: 'accAccountName',
      account_code: 'accAccountCode',
      opening_balance: 'accOpeningBalance',
      group_name: 'accGroupName',
    };
    Object.keys(errors || {}).forEach(function (key) {
      setFieldError(map[key] || key, errors[key]);
    });
  }

  function showModal(id) {
    var inst = getModalInstance(id);
    if (!inst) {
      console.error('[COA] Bootstrap Modal unavailable for', id);
      return;
    }
    inst.show();
  }

  function hideModal(id) {
    var inst = getModalInstance(id);
    if (inst) inst.hide();
    else cleanupModalState();
  }

  function escapeHtml(s) { return AccModule.escapeHtml(s); }
})();
