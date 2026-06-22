(function () {
  'use strict';

  var accounts = [];
  var groups = [];
  var openingMode = !!(window.TMS_ACCOUNTS_MASTER && window.TMS_ACCOUNTS_MASTER.openingMode);

  document.addEventListener('DOMContentLoaded', function () {
    bindFilters();
    document.getElementById('accCoaNewBtn')?.addEventListener('click', openNew);
    document.getElementById('accCoaNewGroupBtn')?.addEventListener('click', openNewGroup);
    document.getElementById('accAddGroupInlineBtn')?.addEventListener('click', openNewGroup);
    document.getElementById('accAccountForm')?.addEventListener('submit', saveAccount);
    document.getElementById('accGroupForm')?.addEventListener('submit', saveGroup);
    document.getElementById('accSeedGroupsBtn')?.addEventListener('click', seedDefaultGroups);
    document.getElementById('accGroupType')?.addEventListener('change', syncGroupNatureDefault);
    document.getElementById('accAccountModal')?.addEventListener('show.bs.modal', function () {
      loadGroups(true);
    });
    loadData();
  });

  function bindFilters() {
    document.getElementById('accCoaSearch')?.addEventListener('input', renderTable);
    document.getElementById('accCoaGroupFilter')?.addEventListener('change', renderTable);
  }

  function loadData() {
    loadGroups(false);
    AccModule.fetchJson({ acc_action: 'list_accounts' }).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'Accounts load failed');
      accounts = res.data || [];
      renderTable();
    }).catch(function (e) {
      AccModule.toast(String(e.message || e), 'error');
    });
  }

  function loadGroups(silent) {
    return AccModule.fetchJson({ acc_action: 'list_account_groups' }).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'Account groups load failed');
      groups = res.data || [];
      fillGroupSelect();
      return groups;
    }).catch(function (e) {
      groups = [];
      fillGroupSelect();
      if (!silent) {
        AccModule.toast(String(e.message || e), 'error');
      }
      return [];
    });
  }

  function fillGroupSelect() {
    var sel = document.getElementById('accAccountGroup');
    var emptyBox = document.getElementById('accGroupEmptyState');
    if (!sel) return;

    if (!groups.length) {
      sel.innerHTML = '<option value="">— Select account group —</option>';
      if (emptyBox) emptyBox.classList.remove('d-none');
    } else {
      if (emptyBox) emptyBox.classList.add('d-none');
      var sorted = sortGroupsForSelect(groups);
      sel.innerHTML = '<option value="">— Select account group —</option>' + sorted.map(function (g) {
        var indent = g._depth > 0 ? '\u00A0\u00A0'.repeat(g._depth) + '\u2514 ' : '';
        return '<option value="' + g.id + '">' + escapeHtml(indent + (g.group_name || '') + ' (' + (g.group_code || '') + ')') + '</option>';
      }).join('');
    }

    fillParentGroupSelect();
    AccModule.refreshSelect2(sel);
  }

  function sortGroupsForSelect(list) {
    var byId = {};
    list.forEach(function (g) { byId[g.id] = g; });
    function depth(g) {
      var d = 0;
      var cur = g;
      var guard = 0;
      while (cur && cur.parent_id && byId[cur.parent_id] && guard < 10) {
        d++;
        cur = byId[cur.parent_id];
        guard++;
      }
      return d;
    }
    return list.slice().map(function (g) {
      g._depth = depth(g);
      return g;
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
    sel.innerHTML = '<option value="">— None (top level) —</option>' + sorted.map(function (g) {
      var indent = g._depth > 0 ? '\u00A0\u00A0'.repeat(g._depth) + '\u2514 ' : '';
      return '<option value="' + g.id + '">' + escapeHtml(indent + (g.group_name || '')) + '</option>';
    }).join('');
    if (current) sel.value = current;
  }

  function syncGroupNatureDefault() {
    var type = document.getElementById('accGroupType')?.value || 'EXPENSES';
    var natureEl = document.getElementById('accGroupNature');
    if (!natureEl) return;
    natureEl.value = (type === 'LIABILITIES' || type === 'CAPITAL' || type === 'INCOME') ? 'CREDIT' : 'DEBIT';
  }

  function openNewGroup() {
    var form = document.getElementById('accGroupForm');
    if (form) form.reset();
    fillParentGroupSelect();
    syncGroupNatureDefault();
    var el = document.getElementById('accGroupModal');
    if (el && typeof bootstrap !== 'undefined') bootstrap.Modal.getOrCreateInstance(el).show();
  }

  function saveGroup(e) {
    e.preventDefault();
    var name = document.getElementById('accGroupName').value.trim();
    if (!name) {
      AccModule.toast('Group name is required.', 'warning');
      return;
    }
    var payload = {
      group_code: document.getElementById('accGroupCode').value.trim(),
      group_name: name,
      parent_id: document.getElementById('accGroupParent').value || null,
      group_type: document.getElementById('accGroupType').value,
      nature: document.getElementById('accGroupNature').value,
    };
    AccModule.postJson({ acc_action: 'save_account_group' }, payload).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'Save failed');
      AccModule.toast('Account group saved');
      bootstrap.Modal.getInstance(document.getElementById('accGroupModal'))?.hide();
      return loadGroups(true).then(function () {
        if (res.data && res.data.id) {
          AccModule.setSelectValue(document.getElementById('accAccountGroup'), String(res.data.id));
        }
      });
    }).catch(function (e) { AccModule.toast(String(e.message || e), 'error'); });
  }

  function seedDefaultGroups() {
    var btn = document.getElementById('accSeedGroupsBtn');
    if (btn) btn.disabled = true;
    AccModule.postJson({ acc_action: 'seed_default_account_groups' }, {}).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'Could not create default groups');
      groups = res.data || [];
      fillGroupSelect();
      AccModule.toast(res.message || 'Default account groups ready');
    }).catch(function (e) {
      AccModule.toast(String(e.message || e), 'error');
    }).finally(function () {
      if (btn) btn.disabled = false;
    });
  }

  function filteredAccounts() {
    var q = (document.getElementById('accCoaSearch')?.value || '').toLowerCase();
    var type = document.getElementById('accCoaGroupFilter')?.value || '';
    return accounts.filter(function (a) {
      var matchQ = !q || (a.account_code || '').toLowerCase().includes(q) || (a.account_name || '').toLowerCase().includes(q);
      var matchT = !type || a.group_type === type;
      return matchQ && matchT;
    });
  }

  function renderTable() {
    var tbody = document.querySelector('#accCoaTable tbody');
    if (!tbody) return;
    var rows = filteredAccounts();
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3">No accounts found</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(function (a) {
      var ob = parseFloat(a.opening_balance || 0);
      var bal = parseFloat(a.current_balance || 0);
      return '<tr>' +
        '<td>' + escapeHtml(a.account_code) + '</td>' +
        '<td>' + escapeHtml(a.account_name) + '</td>' +
        '<td>' + escapeHtml(a.group_name || '') + '</td>' +
        '<td>' + escapeHtml(a.group_type || '') + '</td>' +
        (openingMode ? '<td class="text-end">' + AccModule.money(ob) + '</td><td>' + escapeHtml(a.opening_balance_type || '') + '</td>' : '') +
        '<td class="text-end">' + AccModule.money(bal) + '</td>' +
        '<td>' + (String(a.is_active) === '1' ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>') + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary" data-edit="' + a.id + '">Edit</button></td>' +
        '</tr>';
    }).join('');
    tbody.querySelectorAll('[data-edit]').forEach(function (btn) {
      btn.addEventListener('click', function () { openEdit(parseInt(btn.getAttribute('data-edit'), 10)); });
    });
  }

  function openNew() {
    document.getElementById('accAccountModalTitle').textContent = 'New Account';
    document.getElementById('accAccountForm').reset();
    document.getElementById('accAccountId').value = '';
    document.getElementById('accAccountCode').readOnly = false;
    AccModule.setSelectValue(document.getElementById('accAccountGroup'), '');
    loadGroups(true).finally(function () {
      showModal();
    });
  }

  function openEdit(id) {
    AccModule.fetchJson({ acc_action: 'get_account', id: String(id) }).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'Load failed');
      var a = res.data;
      document.getElementById('accAccountModalTitle').textContent = 'Edit Account';
      document.getElementById('accAccountId').value = a.id;
      document.getElementById('accAccountCode').value = a.account_code;
      document.getElementById('accAccountCode').readOnly = true;
      document.getElementById('accAccountName').value = a.account_name;
      AccModule.setSelectValue(document.getElementById('accAccountGroup'), String(a.account_group_id || ''));
      document.getElementById('accOpeningBalance').value = a.opening_balance;
      document.getElementById('accOpeningType').value = a.opening_balance_type || 'DEBIT';
      document.getElementById('accAccountActive').value = String(a.is_active ?? 1);
      showModal();
    }).catch(function (e) { AccModule.toast(String(e.message || e), 'error'); });
  }

  function saveAccount(e) {
    e.preventDefault();
    var groupId = AccModule.getSelectValue(document.getElementById('accAccountGroup'));
    if (!groupId) {
      AccModule.toast('Please select an account group.', 'warning');
      return;
    }
    var code = document.getElementById('accAccountCode').value.trim();
    var name = document.getElementById('accAccountName').value.trim();
    if (!code || !name) {
      AccModule.toast('Account code and name are required.', 'warning');
      return;
    }
    var payload = {
      id: document.getElementById('accAccountId').value || 0,
      account_code: code,
      account_name: name,
      account_group_id: groupId,
      opening_balance: document.getElementById('accOpeningBalance').value,
      opening_balance_type: document.getElementById('accOpeningType').value,
      is_active: document.getElementById('accAccountActive').value,
    };
    AccModule.postJson({ acc_action: 'save_account' }, payload).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'Save failed');
      AccModule.toast('Account saved');
      bootstrap.Modal.getInstance(document.getElementById('accAccountModal'))?.hide();
      loadData();
    }).catch(function (e) { AccModule.toast(String(e.message || e), 'error'); });
  }

  function showModal() {
    var el = document.getElementById('accAccountModal');
    if (el && typeof bootstrap !== 'undefined') bootstrap.Modal.getOrCreateInstance(el).show();
  }

  function escapeHtml(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
})();
