(function () {
  'use strict';

  var app = document.getElementById('expensesApp');
  if (!app) return;

  var apiBase = app.getAttribute('data-api-base') || '';
  var csrf = app.getAttribute('data-csrf') || '';
  var isAdmin = app.getAttribute('data-is-admin') === '1';
  var defaultBranch = parseInt(app.getAttribute('data-default-branch') || '0', 10);
  var editId = parseInt(app.getAttribute('data-edit-id') || '0', 10);

  var state = {
    boot: null,
    page: 1,
    limit: 25,
    rows: [],
    total: 0,
    charts: { pie: null, trend: null },
    filters: {}
  };

  var els = {
    filterForm: document.getElementById('expFilterForm'),
    tableBody: document.getElementById('expTableBody'),
    cardsMobile: document.getElementById('expCardsMobile'),
    loading: document.getElementById('expLoading'),
    empty: document.getElementById('expEmpty'),
    pagination: document.getElementById('expPagination'),
    paginationInfo: document.getElementById('expPaginationInfo'),
    pageSize: document.getElementById('expPageSize'),
    alert: document.getElementById('expAlert'),
    expenseForm: document.getElementById('expenseForm'),
    categoryList: document.getElementById('expCategoryList')
  };

  function money(n) {
    var cfg = (window.TMS_CURRENCY || state.boot && state.boot.currency) || { symbol: 'Rs.', decimals: 2, locale: 'en-LK' };
    var v = Number(n) || 0;
    try {
      return cfg.symbol + ' ' + v.toLocaleString(cfg.locale || 'en-LK', { minimumFractionDigits: cfg.decimals || 2, maximumFractionDigits: cfg.decimals || 2 });
    } catch (e) {
      return cfg.symbol + ' ' + v.toFixed(cfg.decimals || 2);
    }
  }

  function apiUrl(action, params) {
    var u = new URL(apiBase, window.location.origin);
    u.searchParams.set('exp_action', action);
    if (params) {
      Object.keys(params).forEach(function (k) {
        if (params[k] !== '' && params[k] != null) u.searchParams.set(k, params[k]);
      });
    }
    return u.toString();
  }

  function showAlert(msg, type) {
    if (!els.alert) return;
    els.alert.className = 'alert alert-' + (type || 'info');
    els.alert.textContent = msg;
    els.alert.classList.remove('d-none');
    setTimeout(function () { els.alert.classList.add('d-none'); }, 5000);
  }

  function getFilters() {
    var fd = new FormData(els.filterForm);
    var f = {};
    fd.forEach(function (v, k) { if (v !== '') f[k] = v; });
    return f;
  }

  function fetchJson(url, options) {
    return fetch(url, options || {}).then(function (r) {
      return r.json().then(function (j) {
        if (!r.ok) throw new Error((j && j.message) || 'Request failed');
        return j;
      });
    });
  }

  function postForm(action, data) {
    var fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('exp_action', action);
    Object.keys(data).forEach(function (k) {
      if (data[k] !== undefined && data[k] !== null) fd.append(k, data[k]);
    });
    return fetchJson(apiBase, { method: 'POST', body: fd });
  }

  function loadBoot() {
    return fetchJson(apiUrl('boot')).then(function (res) {
      state.boot = res.data;
      if (res.data.csrf_token) csrf = res.data.csrf_token;
      populateSelects(res.data);
      document.querySelectorAll('#expFormCsrf, #expSettleCsrf, #expCatCsrf').forEach(function (el) {
        if (el) el.value = csrf;
      });
    });
  }

  function populateSelects(data) {
    fillSelect(document.getElementById('expCategory'), data.categories, 'id', 'name', true);
    fillSelect(document.getElementById('expSupplier'), data.suppliers, 'id', 'name', true);
    fillSelect(document.getElementById('expFormBranch'), data.branches, 'id', 'name', false);
    fillSelect(document.getElementById('expFormCategory'), data.categories, 'id', 'name', true);
    fillSelect(document.getElementById('expFormSupplier'), data.suppliers, 'id', 'name', true);
    fillSelect(document.getElementById('expFormAccount'), data.expense_accounts, 'id', function (a) {
      return (a.account_code ? a.account_code + ' — ' : '') + a.account_name;
    }, true);
    fillSelect(document.getElementById('expCatAccount'), data.expense_accounts, 'id', function (a) {
      return (a.account_code ? a.account_code + ' — ' : '') + a.account_name;
    }, true);
    if (defaultBranch > 0) {
      var fb = document.getElementById('expFormBranch');
      if (fb) fb.value = String(defaultBranch);
    }
  }

  function fillSelect(el, items, valKey, labelKey, allowEmpty) {
    if (!el) return;
    var first = allowEmpty ? el.querySelector('option') : null;
    var emptyLabel = first ? first.textContent : '—';
    el.innerHTML = allowEmpty ? '<option value="">' + emptyLabel + '</option>' : '';
    (items || []).forEach(function (item) {
      var opt = document.createElement('option');
      opt.value = item[valKey];
      opt.textContent = typeof labelKey === 'function' ? labelKey(item) : item[labelKey];
      el.appendChild(opt);
    });
  }

  function loadList() {
    els.loading.classList.remove('d-none');
    els.empty.classList.add('d-none');
    var f = getFilters();
    state.filters = f;
    var params = Object.assign({}, f, { page_num: state.page, limit: state.limit });
    return fetchJson(apiUrl('list', params)).then(function (res) {
      state.rows = res.data.rows || [];
      state.total = res.data.total || 0;
      renderTable();
      renderPagination(res.data);
    }).catch(function (e) {
      showAlert(e.message, 'danger');
    }).finally(function () {
      els.loading.classList.add('d-none');
    });
  }

  function loadStats() {
    return fetchJson(apiUrl('stats', getFilters())).then(function (res) {
      var d = res.data || {};
      document.querySelectorAll('[data-stat]').forEach(function (el) {
        var k = el.getAttribute('data-stat');
        el.textContent = money(d[k] || 0);
      });
      renderCharts(d);
    }).catch(function () { /* silent */ });
  }

  function statusBadge(status) {
    var map = {
      draft: 'secondary',
      pending: 'warning',
      approved: 'success',
      rejected: 'danger',
      cancelled: 'dark'
    };
    var s = (status || 'pending').toLowerCase();
    return '<span class="badge bg-' + (map[s] || 'secondary') + ' exp-badge-status">' + s + '</span>';
  }

  function paymentLabel(m) {
    var labels = { cash: 'Cash', bank: 'Bank', cheque: 'Cheque', credit: 'Credit', transfer: 'Transfer' };
    return labels[m] || m || '—';
  }

  function actionButtons(row) {
    var html = '<div class="exp-actions btn-group btn-group-sm">';
    html += '<button type="button" class="btn btn-outline-secondary" data-exp-action="edit" data-id="' + row.id + '" title="Edit"><i class="bi bi-pencil"></i></button>';
    if (isAdmin && row.status !== 'approved') {
      html += '<button type="button" class="btn btn-outline-success" data-exp-action="approve" data-id="' + row.id + '" title="Approve"><i class="bi bi-check2"></i></button>';
    }
    if (isAdmin && row.status === 'pending') {
      html += '<button type="button" class="btn btn-outline-warning" data-exp-action="reject" data-id="' + row.id + '" title="Reject"><i class="bi bi-x"></i></button>';
    }
    if ((row.payment_method === 'credit' || row.payment_mode === 'credit') && parseFloat(row.balance_amount) > 0.01) {
      html += '<button type="button" class="btn btn-outline-primary" data-exp-action="settle" data-id="' + row.id + '" data-balance="' + row.balance_amount + '" title="Pay"><i class="bi bi-cash"></i></button>';
    }
    html += '<button type="button" class="btn btn-outline-danger" data-exp-action="delete" data-id="' + row.id + '" title="Delete"><i class="bi bi-trash"></i></button>';
    html += '</div>';
    return html;
  }

  function renderTable() {
    if (!state.rows.length) {
      els.empty.classList.remove('d-none');
      els.tableBody.innerHTML = '';
      els.cardsMobile.innerHTML = '';
      return;
    }
    els.empty.classList.add('d-none');

    els.tableBody.innerHTML = state.rows.map(function (r) {
      return '<tr>' +
        '<td><code class="small">' + esc(r.expense_number || r.id) + '</code></td>' +
        '<td>' + esc(r.expense_date) + '</td>' +
        '<td>' + esc(r.category_name || '—') + '</td>' +
        '<td>' + esc(r.supplier_name || r.credit_party || '—') + '</td>' +
        '<td>' + esc(r.branch_name || '—') + '</td>' +
        '<td class="text-end fw-semibold">' + money(r.total_amount) + '</td>' +
        '<td class="text-end">' + money(r.paid_amount) + '</td>' +
        '<td class="text-end">' + money(r.balance_amount) + '</td>' +
        '<td>' + esc(paymentLabel(r.payment_method)) + '</td>' +
        '<td>' + statusBadge(r.status) + '</td>' +
        '<td class="small">' + esc(r.approver_name || '—') + '</td>' +
        '<td class="text-end">' + actionButtons(r) + '</td>' +
        '</tr>';
    }).join('');

    els.cardsMobile.innerHTML = state.rows.map(function (r) {
      return '<div class="exp-mobile-card">' +
        '<div class="d-flex justify-content-between align-items-start gap-2 mb-1">' +
        '<div><div class="exp-mc-title">' + esc(r.expense_number || '#' + r.id) + '</div>' +
        '<div class="exp-mc-meta">' + esc(r.expense_date) + ' · ' + esc(r.category_name || '') + '</div></div>' +
        statusBadge(r.status) + '</div>' +
        '<div class="d-flex justify-content-between small mb-2"><span>' + esc(r.branch_name) + '</span><strong>' + money(r.total_amount) + '</strong></div>' +
        '<div class="d-flex justify-content-between align-items-center">' +
        '<span class="small text-muted">Bal: ' + money(r.balance_amount) + '</span>' +
        actionButtons(r) + '</div></div>';
    }).join('');
  }

  function renderPagination(data) {
    var pages = data.pages || 1;
    var page = data.page || 1;
    var from = state.total ? (page - 1) * state.limit + 1 : 0;
    var to = Math.min(page * state.limit, state.total);
    els.paginationInfo.textContent = 'Showing ' + from + '–' + to + ' of ' + state.total;

    var html = '';
    var addLi = function (p, label, disabled, active) {
      html += '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
        '<a class="page-link" href="#" data-page="' + p + '">' + label + '</a></li>';
    };
    addLi(page - 1, '‹', page <= 1, false);
    for (var i = Math.max(1, page - 2); i <= Math.min(pages, page + 2); i++) {
      addLi(i, String(i), false, i === page);
    }
    addLi(page + 1, '›', page >= pages, false);
    els.pagination.innerHTML = html;
  }

  function renderCharts(data) {
    if (typeof Chart === 'undefined') return;
    var cats = data.top_categories || [];
    var trend = data.monthly_trend || [];

    var pieCtx = document.getElementById('expChartPie');
    if (pieCtx) {
      if (state.charts.pie) state.charts.pie.destroy();
      state.charts.pie = new Chart(pieCtx, {
        type: 'doughnut',
        data: {
          labels: cats.map(function (c) { return c.label; }),
          datasets: [{ data: cats.map(function (c) { return parseFloat(c.total) || 0; }), borderWidth: 1 }]
        },
        options: { plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false }
      });
    }

    var trendCtx = document.getElementById('expChartTrend');
    if (trendCtx) {
      if (state.charts.trend) state.charts.trend.destroy();
      state.charts.trend = new Chart(trendCtx, {
        type: 'bar',
        data: {
          labels: trend.map(function (t) { return t.month; }),
          datasets: [{ label: 'Expenses', data: trend.map(function (t) { return parseFloat(t.total) || 0; }), backgroundColor: 'rgba(13,110,253,0.6)' }]
        },
        options: { scales: { y: { beginAtZero: true } }, maintainAspectRatio: false }
      });
    }
  }

  function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function refresh() {
    return Promise.all([loadList(), loadStats()]);
  }

  function resetExpenseForm() {
    els.expenseForm.reset();
    document.getElementById('expFormId').value = '0';
    document.getElementById('expenseModalLabel').textContent = 'New Expense';
    document.getElementById('expFormNumber').value = '';
    document.getElementById('expFormDate').value = new Date().toISOString().slice(0, 10);
    if (defaultBranch > 0) document.getElementById('expFormBranch').value = String(defaultBranch);
    recalcTotal();
    toggleCreditFields();
  }

  function recalcTotal() {
    var amount = parseFloat(document.getElementById('expFormAmount').value) || 0;
    var tax = parseFloat(document.getElementById('expFormTax').value) || 0;
    var discount = parseFloat(document.getElementById('expFormDiscount').value) || 0;
    var total = Math.max(0, amount + tax - discount);
    document.getElementById('expFormTotal').value = total.toFixed(2);
    var method = document.getElementById('expFormPayMethod').value;
    if (method !== 'credit') {
      document.getElementById('expFormPaid').value = total.toFixed(2);
      document.getElementById('expFormBalance').value = '0.00';
    } else {
      var paid = parseFloat(document.getElementById('expFormPaid').value) || 0;
      document.getElementById('expFormBalance').value = Math.max(0, total - paid).toFixed(2);
    }
  }

  function toggleCreditFields() {
    var method = document.getElementById('expFormPayMethod').value;
    var isCredit = method === 'credit';
    document.getElementById('expFormDueWrap').classList.toggle('d-none', !isCredit);
    document.getElementById('expFormPartyWrap').classList.toggle('d-none', !isCredit);
    document.getElementById('expFormPaidWrap').classList.toggle('d-none', !isCredit);
    document.getElementById('expFormBalanceWrap').classList.toggle('d-none', !isCredit);
    recalcTotal();
  }

  function openEdit(id) {
    fetchJson(apiUrl('get', { id: id })).then(function (res) {
      var r = res.data;
      document.getElementById('expenseModalLabel').textContent = 'Edit Expense';
      document.getElementById('expFormId').value = r.id;
      document.getElementById('expFormNumber').value = r.expense_number || '';
      document.getElementById('expFormDate').value = r.expense_date || '';
      document.getElementById('expFormBranch').value = r.branch_id || '';
      document.getElementById('expFormCategory').value = r.category_id || '';
      document.getElementById('expFormAccount').value = r.account_id || '';
      document.getElementById('expFormSupplier').value = r.supplier_id || '';
      document.getElementById('expFormRef').value = r.reference_number || '';
      document.getElementById('expFormDesc').value = r.description || '';
      document.getElementById('expFormAmount').value = r.amount || '';
      document.getElementById('expFormTax').value = r.tax_amount || 0;
      document.getElementById('expFormDiscount').value = r.discount_amount || 0;
      document.getElementById('expFormTotal').value = r.total_amount || '';
      document.getElementById('expFormPayMethod').value = r.payment_method || 'cash';
      document.getElementById('expFormPaid').value = r.paid_amount || 0;
      document.getElementById('expFormDue').value = r.credit_due_date || '';
      document.getElementById('expFormParty').value = r.credit_party || '';
      document.getElementById('expFormStatus').value = r.status || 'pending';
      document.getElementById('expFormNotes').value = r.notes || '';
      toggleCreditFields();
      bootstrap.Modal.getOrCreateInstance(document.getElementById('expenseModal')).show();
    }).catch(function (e) { showAlert(e.message, 'danger'); });
  }

  function loadCategoriesList() {
    return fetchJson(apiUrl('categories')).then(function (res) {
      var rows = res.data || [];
      els.categoryList.innerHTML = rows.map(function (c) {
        return '<tr><td>' + esc(c.name) + '</td><td><code class="small">' + esc(c.code) + '</code></td>' +
          '<td class="small">' + esc(c.account_name || '—') + '</td>' +
          '<td>' + (c.is_active == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Off</span>') + '</td>' +
          '<td class="text-end">' +
          '<button type="button" class="btn btn-sm btn-outline-secondary me-1" data-cat-edit="' + c.id + '">Edit</button>' +
          (c.is_system == 1 ? '' : '<button type="button" class="btn btn-sm btn-outline-danger" data-cat-del="' + c.id + '">Del</button>') +
          '</td></tr>';
      }).join('');
    });
  }

  // Events
  els.filterForm.addEventListener('submit', function (e) {
    e.preventDefault();
    state.page = 1;
    refresh();
  });

  document.getElementById('expBtnClear').addEventListener('click', function () {
    els.filterForm.reset();
    document.getElementById('expFrom').value = new Date().toISOString().slice(0, 7) + '-01';
    document.getElementById('expTo').value = new Date().toISOString().slice(0, 10);
    state.page = 1;
    refresh();
  });

  els.pageSize.addEventListener('change', function () {
    state.limit = parseInt(this.value, 10) || 25;
    state.page = 1;
    loadList();
  });

  els.pagination.addEventListener('click', function (e) {
    var a = e.target.closest('[data-page]');
    if (!a) return;
    e.preventDefault();
    var p = parseInt(a.getAttribute('data-page'), 10);
    if (p < 1 || p > Math.ceil(state.total / state.limit)) return;
    state.page = p;
    loadList();
  });

  app.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-exp-action]');
    if (!btn) return;
    var action = btn.getAttribute('data-exp-action');
    var id = btn.getAttribute('data-id');
    if (action === 'edit') openEdit(id);
    else if (action === 'approve' && confirm('Approve and post this expense to accounts?')) {
      postForm('approve', { id: id }).then(function () { showAlert('Approved.', 'success'); refresh(); });
    } else if (action === 'reject' && confirm('Reject this expense?')) {
      postForm('reject', { id: id }).then(function () { showAlert('Rejected.', 'warning'); refresh(); });
    } else if (action === 'delete' && confirm('Delete this expense?')) {
      postForm('delete', { id: id }).then(function () { showAlert('Deleted.', 'success'); refresh(); });
    } else if (action === 'settle') {
      document.getElementById('expSettleId').value = id;
      document.getElementById('expSettleAmount').value = btn.getAttribute('data-balance') || '';
      document.getElementById('expSettleSummary').textContent = 'Outstanding balance: ' + money(btn.getAttribute('data-balance'));
      bootstrap.Modal.getOrCreateInstance(document.getElementById('expSettleModal')).show();
    }
  });

  document.getElementById('expBtnNew').addEventListener('click', resetExpenseForm);

  ['expFormAmount', 'expFormTax', 'expFormDiscount', 'expFormPaid'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', recalcTotal);
  });
  document.getElementById('expFormPayMethod').addEventListener('change', toggleCreditFields);

  els.expenseForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var spinner = document.getElementById('expFormSpinner');
    spinner.classList.remove('d-none');
    var fd = new FormData(els.expenseForm);
    fd.set('csrf_token', csrf);
    fd.set('exp_action', 'save');
    fetch(apiBase, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.success) throw new Error(res.message || 'Save failed');
        bootstrap.Modal.getInstance(document.getElementById('expenseModal')).hide();
        showAlert('Expense saved.', 'success');
        refresh();
      })
      .catch(function (err) { showAlert(err.message, 'danger'); })
      .finally(function () { spinner.classList.add('d-none'); });
  });

  document.getElementById('expSettleForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(this);
    fd.set('csrf_token', csrf);
    fetch(apiBase, { method: 'POST', body: fd }).then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.success) throw new Error(res.message);
        bootstrap.Modal.getInstance(document.getElementById('expSettleModal')).hide();
        showAlert('Payment recorded.', 'success');
        refresh();
      }).catch(function (err) { showAlert(err.message, 'danger'); });
  });

  document.getElementById('expBtnExportCsv').addEventListener('click', function () {
    var f = getFilters();
    f.exp_action = 'export_csv';
    window.location.href = apiUrl('export_csv', f);
  });

  document.getElementById('expBtnPrint').addEventListener('click', function () {
    window.print();
  });

  document.getElementById('expCategoryModal').addEventListener('show.bs.modal', loadCategoriesList);

  document.getElementById('expCategoryForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(this);
    fd.set('csrf_token', csrf);
    fetch(apiBase, { method: 'POST', body: fd }).then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.success) throw new Error(res.message);
        document.getElementById('expCategoryForm').reset();
        document.getElementById('expCatId').value = '0';
        loadCategoriesList();
        loadBoot().then(refresh);
        showAlert('Category saved.', 'success');
      }).catch(function (err) { showAlert(err.message, 'danger'); });
  });

  els.categoryList.addEventListener('click', function (e) {
    var editBtn = e.target.closest('[data-cat-edit]');
    var delBtn = e.target.closest('[data-cat-del]');
    if (editBtn) {
      var id = editBtn.getAttribute('data-cat-edit');
      var row = (state.boot && state.boot.categories || []).find(function (c) { return String(c.id) === String(id); });
      if (row) {
        document.getElementById('expCatId').value = row.id;
        document.getElementById('expCatName').value = row.name;
        document.getElementById('expCatAccount').value = row.account_id || '';
      }
    }
    if (delBtn && confirm('Delete category?')) {
      postForm('category_delete', { id: delBtn.getAttribute('data-cat-del') })
        .then(function () { loadCategoriesList(); loadBoot(); showAlert('Deleted.', 'success'); })
        .catch(function (err) { showAlert(err.message, 'danger'); });
    }
  });

  // Init
  loadBoot().then(function () {
    refresh();
    if (editId > 0) openEdit(editId);
  });
})();
