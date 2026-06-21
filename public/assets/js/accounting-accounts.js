(function () {
  'use strict';

  var accounts = [];
  var groups = [];
  var openingMode = !!(window.TMS_ACCOUNTS_MASTER && window.TMS_ACCOUNTS_MASTER.openingMode);

  document.addEventListener('DOMContentLoaded', function () {
    bindFilters();
    document.getElementById('accCoaNewBtn')?.addEventListener('click', openNew);
    document.getElementById('accAccountForm')?.addEventListener('submit', saveAccount);
    loadData();
  });

  function bindFilters() {
    document.getElementById('accCoaSearch')?.addEventListener('input', renderTable);
    document.getElementById('accCoaGroupFilter')?.addEventListener('change', renderTable);
  }

  function loadData() {
    Promise.all([
      AccModule.fetchJson({ acc_action: 'list_accounts' }),
      AccModule.fetchJson({ acc_action: 'list_account_groups' }),
    ]).then(function (results) {
      if (!results[0].ok) throw new Error(results[0].error || 'Accounts load failed');
      accounts = results[0].data || [];
      groups = (results[1].ok ? results[1].data : []) || [];
      fillGroupSelect();
      renderTable();
    }).catch(function (e) {
      AccModule.toast(String(e.message || e), 'error');
    });
  }

  function fillGroupSelect() {
    var sel = document.getElementById('accAccountGroup');
    if (!sel) return;
    sel.innerHTML = groups.map(function (g) {
      return '<option value="' + g.id + '">' + escapeHtml((g.group_code || '') + ' — ' + (g.group_name || '')) + '</option>';
    }).join('');
    AccModule.initSelect2(sel.parentElement);
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
    showModal();
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
      document.getElementById('accAccountGroup').value = a.account_group_id;
      document.getElementById('accOpeningBalance').value = a.opening_balance;
      document.getElementById('accOpeningType').value = a.opening_balance_type || 'DEBIT';
      document.getElementById('accAccountActive').value = String(a.is_active ?? 1);
      showModal();
    }).catch(function (e) { AccModule.toast(String(e.message || e), 'error'); });
  }

  function saveAccount(e) {
    e.preventDefault();
    var payload = {
      id: document.getElementById('accAccountId').value || 0,
      account_code: document.getElementById('accAccountCode').value.trim(),
      account_name: document.getElementById('accAccountName').value.trim(),
      account_group_id: document.getElementById('accAccountGroup').value,
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
