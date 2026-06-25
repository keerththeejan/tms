(function () {
  'use strict';

  var app = document.getElementById('reportsApp');
  if (!app) return;

  var apiBase = app.getAttribute('data-api-base') || '';
  var charts = { branch: null, expense: null, monthly: null, supplier: null };
  var loadTimer = null;

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

  function setLoading(on) {
    document.querySelectorAll('[data-kpi]').forEach(function (el) {
      el.classList.toggle('rep-loading', on);
      if (on) el.textContent = '…';
    });
    var updated = document.getElementById('repUpdated');
    if (updated && on) updated.textContent = 'Loading…';
  }

  function showError(msg) {
    var el = document.getElementById('repAlert');
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('d-none');
  }

  function hideError() {
    var el = document.getElementById('repAlert');
    if (el) el.classList.add('d-none');
  }

  function setKpi(key, val, signed) {
    var el = document.querySelector('[data-kpi="' + key + '"]');
    if (!el) return;
    el.classList.remove('rep-loading');
    if (key.indexOf('parcels') !== -1 || key.indexOf('active_') === 0) {
      el.textContent = String(parseInt(val, 10) || 0);
      return;
    }
    el.textContent = money(val);
    if (signed) {
      var n = parseFloat(val) || 0;
      el.classList.toggle('positive', n >= 0);
      el.classList.toggle('negative', n < 0);
    }
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
          borderRadius: 6,
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
          borderRadius: 6,
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

  function fillTable(tableId, html) {
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
        return;
      }
      if (typeof DataTable !== 'undefined' && table.dataset.dtInit !== '1') {
        table.dataset.dtInit = '1';
        new DataTable(table, { paging: true, searching: true, order: [] });
        return;
      }
      if (attempts > 0) {
        setTimeout(function () { initDt(attempts - 1); }, 120);
      }
    }
    initDt(25);
  }

  function renderTables(d) {
    var revRows = d.revenue_by_branch || [];
    fillTable('repRevenueTable', revRows.length ? revRows.map(function (r) {
      return '<tr><td>' + escapeHtml(r.branch_name) + '</td><td class="text-end">' + money(r.accounting_revenue) +
        '</td><td class="text-end">' + money(r.freight_revenue) + '</td><td class="text-end"><strong>' + money(r.revenue) +
        '</strong></td><td class="text-end">' + escapeHtml(r.percentage) + '%</td><td class="text-end">' + escapeHtml(r.txn_count) + '</td></tr>';
    }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">No revenue in this period.</td></tr>');

    var expRows = (d.tables && d.tables.expenses) || [];
    fillTable('repExpenseTable', expRows.length ? expRows.map(function (r) {
      return '<tr><td>' + escapeHtml(r.expense_date) + '</td><td><code class="small">' + escapeHtml(r.expense_number) +
        '</code></td><td>' + escapeHtml(r.category_name) + '</td><td>' + escapeHtml(r.branch_name) +
        '</td><td>' + escapeHtml(r.supplier_name) + '</td><td class="text-end">' + money(r.amount) +
        '</td><td>' + escapeHtml(r.status) + '</td></tr>';
    }).join('') : '<tr><td colspan="7" class="text-center text-muted py-4">No approved expenses in this period.</td></tr>');

    var supRows = d.parcels_by_supplier || [];
    fillTable('repSupplierTable', supRows.length ? supRows.map(function (r) {
      return '<tr><td>' + escapeHtml(r.supplier_name) + '</td><td class="text-end">' + escapeHtml(r.parcels_count) +
        '</td><td class="text-end">' + money(r.revenue) + '</td><td class="text-end">' + escapeHtml(r.pending_parcels) +
        '</td><td class="text-end">' + escapeHtml(r.delivered_parcels) + '</td><td class="text-end">' + escapeHtml(r.cancelled_parcels) + '</td></tr>';
    }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">No parcel data for suppliers.</td></tr>');

    var parRows = (d.tables && d.tables.parcels) || [];
    fillTable('repParcelTable', parRows.length ? parRows.map(function (r) {
      return '<tr><td>' + escapeHtml(r.parcel_date) + '</td><td><code class="small">' + escapeHtml(r.tracking_number) +
        '</code></td><td>' + escapeHtml(r.customer_name) + '</td><td>' + escapeHtml(r.supplier_name) +
        '</td><td>' + escapeHtml(r.from_branch) + '</td><td>' + escapeHtml(r.to_branch) +
        '</td><td>' + escapeHtml(r.status) + '</td><td class="text-end">' + money(r.amount) + '</td></tr>';
    }).join('') : '<tr><td colspan="8" class="text-center text-muted py-4">No parcels in this period.</td></tr>');
  }

  function renderDashboard(d) {
    var s = d.summary || {};
    setKpi('total_revenue', s.total_revenue);
    setKpi('total_expenses', s.total_expenses);
    setKpi('net_profit', s.net_profit, true);
    setKpi('total_parcels', s.total_parcels);
    setKpi('delivered_parcels', s.delivered_parcels);
    setKpi('pending_parcels', s.pending_parcels);
    setKpi('cancelled_parcels', s.cancelled_parcels);
    setKpi('active_suppliers', s.active_suppliers);
    setKpi('active_customers', s.active_customers);

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

    var updated = document.getElementById('repUpdated');
    if (updated) {
      var ts = d.generated_at ? new Date(d.generated_at) : new Date();
      updated.textContent = 'Last updated: ' + ts.toLocaleString();
    }
  }

  function loadDashboard() {
    hideError();
    setLoading(true);
    var filters = getFilters();
    return fetchJson(Object.assign({ rep_action: 'dashboard', _t: String(Date.now()) }, filters))
      .then(function (res) {
        if (!res.ok && !res.success) throw new Error(res.message || res.error || 'Load failed');
        renderDashboard(res.data || {});
      })
      .catch(function (err) {
        showError(err.message || 'Could not load reports');
        var updated = document.getElementById('repUpdated');
        if (updated) updated.textContent = 'Failed to load';
      })
      .finally(function () {
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

  function boot() {
    return fetchJson({ rep_action: 'boot' }).then(function (res) {
      var data = res.data || {};
      populateSuppliers(data.suppliers || []);
      if (data.default_from) document.getElementById('repFrom').value = data.default_from;
      if (data.default_to) document.getElementById('repTo').value = data.default_to;

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

    var refreshBtn = document.getElementById('repBtnRefresh');
    if (refreshBtn) refreshBtn.addEventListener('click', loadDashboard);

    var printBtn = document.getElementById('repBtnPrint');
    if (printBtn) printBtn.addEventListener('click', function () { window.print(); });

    document.querySelectorAll('.rep-export').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        var type = link.getAttribute('data-type') || 'summary';
        var filters = getFilters();
        var q = new URLSearchParams(Object.assign({ page: 'reports', rep_action: 'export', type: type }, filters));
        window.location.href = apiBase.split('?')[0] + '?' + q.toString();
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
