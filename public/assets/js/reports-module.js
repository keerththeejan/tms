(function () {
  'use strict';

  var app = document.getElementById('reportsApp');
  if (!app) return;

  var apiBase = app.getAttribute('data-api-base') || '';
  var charts = { branch: null, expense: null, monthly: null, supplier: null };
  var loadTimer = null;
  var bootDefaults = { from: '', to: '' };
  var lastData = null;

  function money(n) {
    if (window.TMS && typeof window.TMS.formatMoney === 'function') {
      return window.TMS.formatMoney(n);
    }
    return 'LKR ' + (parseFloat(n) || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function apiUrl(params) {
    var q = new URLSearchParams(Object.assign({ page: 'reports' }, params));
    return apiBase.split('?')[0] + '?' + q.toString();
  }

  function fetchJson(params) {
    return fetch(apiUrl(params), {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    }).then(function (r) {
      if (!r.ok) {
        return r.text().then(function (t) {
          throw new Error((t || '').trim() || 'Request failed (' + r.status + ')');
        });
      }
      return r.json();
    });
  }

  function getFilters() {
    var form = document.getElementById('repFilterForm');
    return {
      from: form.from.value,
      to: form.to.value,
      branch_id: form.branch_id.value || '0',
      supplier_id: form.supplier_id.value || '0',
    };
  }

  function rowActionsHtml() {
    return '<div class="rep-row-actions" role="group" aria-label="Row actions">' +
      '<button type="button" class="btn btn-sm btn-outline-secondary rep-act-view" title="View"><i class="bi bi-eye"></i></button>' +
      '<button type="button" class="btn btn-sm btn-outline-secondary rep-act-print" title="Print"><i class="bi bi-printer"></i></button>' +
      '<button type="button" class="btn btn-sm btn-outline-secondary rep-act-export" title="Export"><i class="bi bi-download"></i></button>' +
      '</div>';
  }

  function setLoading(on) {
    var overlay = document.getElementById('repLoadingOverlay');
    if (overlay) overlay.classList.toggle('d-none', !on);
    document.querySelectorAll('[data-kpi]').forEach(function (el) {
      el.classList.toggle('rep-loading', on);
      if (on) el.textContent = '…';
    });
    var updated = document.getElementById('repUpdated');
    if (updated && on) updated.textContent = 'Loading…';
  }

  function showError(msg) {
    var el = document.getElementById('repAlert');
    var msgEl = document.getElementById('repAlertMsg');
    if (!el) return;
    if (msgEl) msgEl.textContent = msg;
    else el.textContent = msg;
    el.classList.remove('d-none');
  }

  function hideError() {
    var el = document.getElementById('repAlert');
    if (el) el.classList.add('d-none');
  }

  function showToast(msg) {
    var toastEl = document.getElementById('repToast');
    var msgEl = document.getElementById('repToastMsg');
    if (!toastEl || typeof bootstrap === 'undefined') return;
    if (msgEl) msgEl.textContent = msg || 'Report generated successfully.';
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2800 }).show();
  }

  function setKpi(key, val, signed) {
    var el = document.querySelector('[data-kpi="' + key + '"]');
    if (!el) return;
    el.classList.remove('rep-loading');
    var countKeys = ['parcels', 'active_', 'today_reports', 'monthly_reports', 'reports'];
    var isCount = countKeys.some(function (k) { return key.indexOf(k) !== -1; });
    if (isCount && val !== '—') {
      el.textContent = String(parseInt(val, 10) || 0);
      return;
    }
    if (val === '—') {
      el.textContent = '—';
      return;
    }
    el.textContent = money(val);
    if (signed) {
      var n = parseFloat(val) || 0;
      el.classList.toggle('positive', n >= 0);
      el.classList.toggle('negative', n < 0);
    }
  }

  function updateExtendedKpis(s, filters) {
    var today = new Date().toISOString().slice(0, 10);
    var monthStart = today.slice(0, 8) + '01';
    var isToday = filters.from === filters.to && filters.from === today;
    var isMonth = filters.from === monthStart && filters.to >= monthStart;
    setKpi('today_reports', isToday ? (s.total_parcels || 0) : '—');
    setKpi('monthly_reports', isMonth ? (s.total_parcels || 0) : (s.total_parcels || 0));
  }

  function getActiveTable() {
    var pane = document.querySelector('.tab-pane.active');
    if (!pane) return null;
    return pane.querySelector('table');
  }

  function getActiveDt() {
    var table = getActiveTable();
    if (!table || typeof jQuery === 'undefined' || !jQuery.fn.DataTable) return null;
    try {
      if (jQuery.fn.DataTable.isDataTable(table)) return jQuery(table).DataTable();
    } catch (e) { /* ignore */ }
    return null;
  }

  function updateRowCount() {
    var el = document.getElementById('repRowCount');
    var table = getActiveTable();
    if (!el || !table) return;
    var dt = getActiveDt();
    if (dt) {
      var info = dt.page.info();
      el.textContent = info.recordsDisplay + ' of ' + info.recordsTotal + ' rows';
      return;
    }
    var rows = table.querySelectorAll('tbody tr').length;
    el.textContent = rows + ' rows';
  }

  function destroyChart(key) {
    if (charts[key]) {
      charts[key].destroy();
      charts[key] = null;
    }
  }

  function renderBranchChart(rows) {
    destroyChart('branch');
    var canvas = document.getElementById('repBranchChart');
    if (!canvas || typeof Chart === 'undefined') return;
    charts.branch = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: rows.map(function (r) { return r.branch_name; }),
        datasets: [{
          label: 'Revenue',
          data: rows.map(function (r) { return parseFloat(r.revenue) || 0; }),
          backgroundColor: 'rgba(37, 99, 235, 0.75)',
          borderRadius: 8,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: function (c) { return money(c.parsed.y); } } },
        },
        scales: {
          y: { beginAtZero: true, ticks: { callback: function (v) { return money(v); } } },
        },
      },
    });
  }

  function renderExpenseChart(buckets) {
    destroyChart('expense');
    var canvas = document.getElementById('repExpenseChart');
    if (!canvas || typeof Chart === 'undefined') return;
    var colors = ['#2563eb', '#059669', '#dc2626', '#d97706', '#7c3aed', '#0891b2', '#64748b'];
    charts.expense = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: buckets.map(function (b) { return b.label; }),
        datasets: [{
          data: buckets.map(function (b) { return parseFloat(b.total) || 0; }),
          backgroundColor: buckets.map(function (_, i) { return colors[i % colors.length]; }),
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: { callbacks: { label: function (c) { return c.label + ': ' + money(c.parsed); } } },
        },
      },
    });
  }

  function renderMonthlyChart(points) {
    destroyChart('monthly');
    var canvas = document.getElementById('repMonthlyChart');
    if (!canvas || typeof Chart === 'undefined') return;
    charts.monthly = new Chart(canvas, {
      type: 'line',
      data: {
        labels: points.map(function (p) { return p.label; }),
        datasets: [{
          label: 'Revenue',
          data: points.map(function (p) { return parseFloat(p.revenue) || 0; }),
          borderColor: '#059669',
          backgroundColor: 'rgba(5, 150, 105, 0.12)',
          fill: true,
          tension: 0.35,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: function (c) { return money(c.parsed.y); } } },
        },
        scales: {
          y: { beginAtZero: true, ticks: { callback: function (v) { return money(v); } } },
        },
      },
    });
  }

  function renderSupplierChart(rows) {
    destroyChart('supplier');
    var canvas = document.getElementById('repSupplierChart');
    if (!canvas || typeof Chart === 'undefined') return;
    var top = rows.slice(0, 8);
    charts.supplier = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: top.map(function (r) { return r.supplier_name; }),
        datasets: [{
          label: 'Parcels',
          data: top.map(function (r) { return parseInt(r.parcels_count, 10) || 0; }),
          backgroundColor: 'rgba(124, 58, 237, 0.75)',
          borderRadius: 8,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } },
      },
    });
  }

  function fillTable(tableId, html, emptyColspan) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    if (table.dataset.dtInit === '1' && typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
      try {
        jQuery(table).DataTable().destroy();
      } catch (e) { /* ignore */ }
      table.dataset.dtInit = '';
    }

    tbody.innerHTML = html;

    function initDt(attempts) {
      if (typeof window.TMS_initDataTables === 'function') {
        window.TMS_initDataTables();
        bindRowActions(table);
        updateRowCount();
        return;
      }
      if (typeof DataTable !== 'undefined' && table.dataset.dtInit !== '1') {
        table.dataset.dtInit = '1';
        new DataTable(table, { paging: true, searching: true, order: [] });
        bindRowActions(table);
        updateRowCount();
        return;
      }
      if (attempts > 0) {
        setTimeout(function () { initDt(attempts - 1); }, 120);
      }
    }
    initDt(25);
  }

  function bindRowActions(table) {
    if (!table) return;
    table.querySelectorAll('.rep-act-print').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        window.print();
      });
    });
    table.querySelectorAll('.rep-act-export').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        triggerExport('summary');
      });
    });
    table.querySelectorAll('.rep-act-view').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var tr = btn.closest('tr');
        if (tr) tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });
  }

  function renderTables(d) {
    var actions = rowActionsHtml();
    var revRows = d.revenue_by_branch || [];
    fillTable('repRevenueTable', revRows.length ? revRows.map(function (r) {
      return '<tr><td>' + escapeHtml(r.branch_name) + '</td><td class="text-end">' + money(r.accounting_revenue) +
        '</td><td class="text-end">' + money(r.freight_revenue) + '</td><td class="text-end"><strong>' + money(r.revenue) +
        '</strong></td><td class="text-end">' + escapeHtml(r.percentage) + '%</td><td class="text-end">' + escapeHtml(r.txn_count) +
        '</td><td class="text-end">' + actions + '</td></tr>';
    }).join('') : '<tr><td colspan="7" class="text-center text-muted py-4">No revenue in this period.</td></tr>');

    var expRows = (d.tables && d.tables.expenses) || [];
    fillTable('repExpenseTable', expRows.length ? expRows.map(function (r) {
      return '<tr><td>' + escapeHtml(r.expense_date) + '</td><td><code class="small">' + escapeHtml(r.expense_number) +
        '</code></td><td>' + escapeHtml(r.category_name) + '</td><td>' + escapeHtml(r.branch_name) +
        '</td><td>' + escapeHtml(r.supplier_name) + '</td><td class="text-end">' + money(r.amount) +
        '</td><td>' + escapeHtml(r.status) + '</td><td class="text-end">' + actions + '</td></tr>';
    }).join('') : '<tr><td colspan="8" class="text-center text-muted py-4">No approved expenses in this period.</td></tr>');

    var supRows = d.parcels_by_supplier || [];
    fillTable('repSupplierTable', supRows.length ? supRows.map(function (r) {
      return '<tr><td>' + escapeHtml(r.supplier_name) + '</td><td class="text-end">' + escapeHtml(r.parcels_count) +
        '</td><td class="text-end">' + money(r.revenue) + '</td><td class="text-end">' + escapeHtml(r.pending_parcels) +
        '</td><td class="text-end">' + escapeHtml(r.delivered_parcels) + '</td><td class="text-end">' + escapeHtml(r.cancelled_parcels) +
        '</td><td class="text-end">' + actions + '</td></tr>';
    }).join('') : '<tr><td colspan="7" class="text-center text-muted py-4">No parcel data for suppliers.</td></tr>');

    var parRows = (d.tables && d.tables.parcels) || [];
    fillTable('repParcelTable', parRows.length ? parRows.map(function (r) {
      return '<tr><td>' + escapeHtml(r.parcel_date) + '</td><td><code class="small">' + escapeHtml(r.tracking_number) +
        '</code></td><td>' + escapeHtml(r.customer_name) + '</td><td>' + escapeHtml(r.supplier_name) +
        '</td><td>' + escapeHtml(r.from_branch) + '</td><td>' + escapeHtml(r.to_branch) +
        '</td><td>' + escapeHtml(r.status) + '</td><td class="text-end">' + money(r.amount) +
        '</td><td class="text-end">' + actions + '</td></tr>';
    }).join('') : '<tr><td colspan="9" class="text-center text-muted py-4">No parcels in this period.</td></tr>');
  }

  function toggleEmptyState(d) {
    var empty = document.getElementById('repEmptyState');
    if (!empty) return;
    var s = d.summary || {};
    var hasData = (parseInt(s.total_parcels, 10) || 0) > 0
      || (parseFloat(s.total_revenue) || 0) > 0
      || (parseFloat(s.total_expenses) || 0) > 0;
    empty.classList.toggle('d-none', hasData);
  }

  function renderDashboard(d) {
    lastData = d;
    var s = d.summary || {};
    var filters = d.filters || getFilters();

    setKpi('total_revenue', s.total_revenue);
    setKpi('total_expenses', s.total_expenses);
    setKpi('net_profit', s.net_profit, true);
    setKpi('total_parcels', s.total_parcels);
    setKpi('delivered_parcels', s.delivered_parcels);
    setKpi('pending_parcels', s.pending_parcels);
    setKpi('cancelled_parcels', s.cancelled_parcels);
    setKpi('active_suppliers', s.active_suppliers);
    setKpi('active_customers', s.active_customers);
    updateExtendedKpis(s, filters);

    renderBranchChart(d.revenue_by_branch || []);
    var buckets = (d.expense_buckets && d.expense_buckets.length)
      ? d.expense_buckets
      : (d.expense_summary || []).map(function (r) {
          return { label: r.category_name, total: r.total };
        });
    renderExpenseChart(buckets.length ? buckets : [{ label: 'No expenses', total: 0 }]);
    renderMonthlyChart(d.monthly_revenue || []);
    renderSupplierChart(d.parcels_by_supplier || []);
    renderTables(d);
    toggleEmptyState(d);

    var updated = document.getElementById('repUpdated');
    if (updated) {
      var ts = d.generated_at ? new Date(d.generated_at) : new Date();
      updated.textContent = 'Last updated: ' + ts.toLocaleString();
    }

    var printDate = document.getElementById('repPrintDate');
    if (printDate) printDate.textContent = new Date().toLocaleString();
  }

  function loadDashboard() {
    hideError();
    setLoading(true);
    var filters = getFilters();
    return fetchJson(Object.assign({ rep_action: 'dashboard', _t: String(Date.now()) }, filters))
      .then(function (res) {
        if (!res.ok && !res.success) throw new Error(res.message || res.error || 'Load failed');
        renderDashboard(res.data || {});
        showToast('Reports loaded successfully.');
      })
      .catch(function (err) {
        showError(err.message || 'Could not load reports');
        var updated = document.getElementById('repUpdated');
        if (updated) updated.textContent = 'Failed to load';
      })
      .finally(function () {
        setLoading(false);
        document.querySelectorAll('[data-kpi]').forEach(function (el) {
          el.classList.remove('rep-loading');
        });
      });
  }

  function scheduleLoad() {
    if (loadTimer) clearTimeout(loadTimer);
    loadTimer = setTimeout(loadDashboard, 280);
  }

  function populateSuppliers(list) {
    var sel = document.getElementById('repSupplier');
    if (!sel) return;
    var current = sel.value || '0';
    sel.innerHTML = '<option value="0">All Suppliers</option>';
    (list || []).forEach(function (s) {
      var opt = document.createElement('option');
      opt.value = String(s.id);
      opt.textContent = s.name;
      sel.appendChild(opt);
    });
    sel.value = current;
  }

  function resetFilters() {
    var form = document.getElementById('repFilterForm');
    if (!form) return;
    if (bootDefaults.from) form.from.value = bootDefaults.from;
    if (bootDefaults.to) form.to.value = bootDefaults.to;
    form.branch_id.value = '0';
    form.supplier_id.value = '0';
    var search = document.getElementById('repGlobalSearch');
    if (search) search.value = '';
    var tableSearch = document.getElementById('repTableSearch');
    if (tableSearch) tableSearch.value = '';
    scheduleLoad();
  }

  function triggerExport(type) {
    var filters = getFilters();
    var q = new URLSearchParams(Object.assign({ page: 'reports', rep_action: 'export', type: type || 'summary' }, filters));
    window.location.href = apiBase.split('?')[0] + '?' + q.toString();
    showToast('Export started — download will begin shortly.');
    var modal = document.getElementById('repExportModal');
    if (modal && typeof bootstrap !== 'undefined') {
      var inst = bootstrap.Modal.getInstance(modal);
      if (inst) inst.hide();
    }
  }

  function searchActiveTable(term) {
    var dt = getActiveDt();
    if (dt) {
      dt.search(term).draw();
      updateRowCount();
      return;
    }
    var table = getActiveTable();
    if (!table) return;
    var q = (term || '').toLowerCase();
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      if (tr.cells.length === 1 && tr.cells[0].colSpan > 1) return;
      var text = tr.textContent.toLowerCase();
      tr.style.display = !q || text.indexOf(q) !== -1 ? '' : 'none';
    });
    updateRowCount();
  }

  function copyActiveTable() {
    var table = getActiveTable();
    if (!table) return;
    var lines = [];
    table.querySelectorAll('tr').forEach(function (tr) {
      if (tr.style.display === 'none') return;
      var cells = Array.from(tr.cells).filter(function (td) {
        return !td.classList.contains('rep-col-actions');
      });
      lines.push(cells.map(function (td) { return td.textContent.trim(); }).join('\t'));
    });
    if (navigator.clipboard) {
      navigator.clipboard.writeText(lines.join('\n')).then(function () {
        showToast('Copied to clipboard.');
      });
    }
  }

  function toggleFullscreen(target) {
    var el = target === 'table' ? document.querySelector('.rep-tab-content') : app;
    if (!el) return;
    if (target === 'page') {
      app.classList.toggle('rep-is-fullscreen');
      return;
    }
    if (!document.fullscreenElement) {
      el.requestFullscreen?.();
    } else {
      document.exitFullscreen?.();
    }
  }

  function boot() {
    return fetchJson({ rep_action: 'boot' }).then(function (res) {
      var data = res.data || {};
      populateSuppliers(data.suppliers || []);
      if (data.default_from) {
        document.getElementById('repFrom').value = data.default_from;
        bootDefaults.from = data.default_from;
      }
      if (data.default_to) {
        document.getElementById('repTo').value = data.default_to;
        bootDefaults.to = data.default_to;
      }

      var qs = new URLSearchParams(window.location.search);
      if (qs.get('from')) document.getElementById('repFrom').value = qs.get('from');
      if (qs.get('to')) document.getElementById('repTo').value = qs.get('to');
      if (qs.get('branch_id')) document.getElementById('repBranch').value = qs.get('branch_id');
      if (qs.get('supplier_id')) document.getElementById('repSupplier').value = qs.get('supplier_id');
    });
  }

  function bindUi() {
    var form = document.getElementById('repFilterForm');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        scheduleLoad();
      });
    }

    ['repFrom', 'repTo', 'repBranch', 'repSupplier'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('change', scheduleLoad);
    });

    document.getElementById('repBtnRefresh')?.addEventListener('click', loadDashboard);
    document.getElementById('repBtnRetry')?.addEventListener('click', loadDashboard);
    document.getElementById('repBtnResetFilters')?.addEventListener('click', resetFilters);
    document.getElementById('repEmptyReset')?.addEventListener('click', resetFilters);
    document.getElementById('repBtnPrint')?.addEventListener('click', function () { window.print(); });
    document.getElementById('repExportPrint')?.addEventListener('click', function () { window.print(); });
    document.getElementById('repBtnFullscreen')?.addEventListener('click', function () { toggleFullscreen('page'); });
    document.getElementById('repTbFullscreen')?.addEventListener('click', function () { toggleFullscreen('table'); });
    document.getElementById('repTbRefresh')?.addEventListener('click', loadDashboard);
    document.getElementById('repTbPrint')?.addEventListener('click', function () { window.print(); });
    document.getElementById('repTbPdf')?.addEventListener('click', function () { window.print(); });
    document.getElementById('repTbCopy')?.addEventListener('click', copyActiveTable);
    document.getElementById('repOpenExportModal')?.addEventListener('click', function (e) {
      e.preventDefault();
      var modal = document.getElementById('repExportModal');
      if (modal && typeof bootstrap !== 'undefined') bootstrap.Modal.getOrCreateInstance(modal).show();
    });

    document.querySelectorAll('.rep-export').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        triggerExport(link.getAttribute('data-type') || 'summary');
      });
    });

    document.getElementById('repGlobalSearch')?.addEventListener('input', function (e) {
      searchActiveTable(e.target.value);
    });
    document.getElementById('repTableSearch')?.addEventListener('input', function (e) {
      searchActiveTable(e.target.value);
    });

    document.querySelectorAll('.rep-density').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var density = btn.getAttribute('data-density');
        document.querySelectorAll('.rep-data-table').forEach(function (tbl) {
          tbl.classList.toggle('rep-density-compact', density === 'compact');
        });
      });
    });

    document.querySelectorAll('[data-rep-tab-target]').forEach(function (card) {
      card.addEventListener('click', function (e) {
        var target = card.getAttribute('data-rep-tab-target');
        if (!target) return;
        e.preventDefault();
        var tabBtn = document.querySelector('[data-bs-target="#' + target + '"]');
        if (tabBtn && typeof bootstrap !== 'undefined') {
          bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        }
        document.getElementById(target)?.scrollIntoView({ behavior: 'smooth' });
        setTimeout(updateRowCount, 400);
      });
    });

    document.querySelectorAll('.rep-tabs .nav-link').forEach(function (tab) {
      tab.addEventListener('shown.bs.tab', function () {
        updateRowCount();
        var search = document.getElementById('repTableSearch');
        if (search && search.value) searchActiveTable(search.value);
      });
    });

    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible') scheduleLoad();
    });
  }

  function init() {
    bindUi();
    boot().then(loadDashboard).catch(function (err) {
      showError(err.message || 'Could not initialize reports');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
