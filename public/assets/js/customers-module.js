(function () {
  'use strict';

  var app = document.getElementById('customersApp');
  if (!app) return;

  var cfg = window.TMS_CUSTOMERS || {};
  var baseUrl = cfg.baseUrl || '';
  var csrf = cfg.csrf || '';

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

  function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('crmToastContainer');
    if (!container || !window.bootstrap) return;
    var id = 'crmToast' + Date.now();
    var html =
      '<div id="' + id + '" class="toast align-items-center text-bg-' + type + ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
      '<div class="d-flex"><div class="toast-body">' + escapeHtml(message) + '</div>' +
      '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>';
    container.insertAdjacentHTML('beforeend', html);
    var el = document.getElementById(id);
    var toast = new bootstrap.Toast(el, { delay: 4500 });
    toast.show();
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
  }

  function animateCounters() {
    document.querySelectorAll('[data-count]').forEach(function (el) {
      var target = parseFloat(el.getAttribute('data-count')) || 0;
      var isMoney = el.getAttribute('data-count-money') === '1';
      var start = 0;
      var dur = 700;
      var t0 = performance.now();
      function frame(t) {
        var p = Math.min(1, (t - t0) / dur);
        var val = start + (target - start) * (1 - Math.pow(1 - p, 3));
        if (isMoney) {
          el.textContent = money(val);
        } else if (target % 1 !== 0) {
          el.textContent = val.toFixed(2);
        } else {
          el.textContent = Math.round(val).toLocaleString();
        }
        if (p < 1) requestAnimationFrame(frame);
      }
      requestAnimationFrame(frame);
    });
  }

  function summaryUrl(id) {
    return baseUrl + 'index.php?page=customers&action=summary&id=' + encodeURIComponent(String(id));
  }

  function openProfile(id) {
    var drawer = document.getElementById('crmProfileDrawer');
    var body = document.getElementById('crmProfileBody');
    if (!drawer || !body || !window.bootstrap) return;
    body.innerHTML =
      '<div class="text-center py-5 text-muted">' +
      '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>' +
      '<div class="mt-2 small">Loading customer profile…</div></div>';
    var oc = bootstrap.Offcanvas.getOrCreateInstance(drawer);
    oc.show();
    fetch(summaryUrl(id), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.error) throw new Error(data.error);
        var row = document.querySelector('tr[data-customer-id="' + id + '"]');
        var email = row ? (row.getAttribute('data-email') || '') : '';
        var city = row ? (row.getAttribute('data-city') || '') : '';
        var code = row ? (row.getAttribute('data-code') || '') : '';
        var group = row ? (row.getAttribute('data-group') || '') : '';
        var created = row ? (row.getAttribute('data-created') || '') : '';
        var initials = row ? (row.getAttribute('data-initials') || '?') : '?';
        var ledgerUrl = row && row.getAttribute('data-ledger-url');
        var editUrl = baseUrl + 'index.php?page=customers&action=edit&id=' + id;
        var invUrl = data.last_delivery_note_id
          ? baseUrl + 'index.php?page=delivery_notes&action=show&id=' + data.last_delivery_note_id
          : '';
        body.innerHTML =
          '<div class="crm-drawer-avatar" aria-hidden="true">' + escapeHtml(initials) + '</div>' +
          '<h5 class="text-center mb-0">' + escapeHtml(data.name) + '</h5>' +
          '<p class="text-center text-muted small mb-3">' + escapeHtml(code) + '</p>' +
          '<div class="crm-drawer-stat"><span>Phone</span><strong>' + escapeHtml(data.phone || '—') + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>Email</span><strong>' + escapeHtml(email || '—') + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>City</span><strong>' + escapeHtml(city || '—') + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>Group</span><strong>' + escapeHtml(group || '—') + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>Outstanding</span><strong class="text-warning-emphasis">' + money(data.due) + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>Total Sales</span><strong>' + money(data.total_amount) + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>Total Paid</span><strong class="text-success">' + money(data.total_paid) + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>Parcels</span><strong>' + (data.total_parcels || 0) + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>Delivery Notes</span><strong>' + (data.total_delivery_notes || 0) + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>Last Delivery</span><strong>' + escapeHtml(data.last_delivery_date || '—') + '</strong></div>' +
          '<div class="crm-drawer-stat"><span>Registered</span><strong>' + escapeHtml(created || '—') + '</strong></div>' +
          '<div class="d-flex flex-wrap gap-2 mt-3">' +
          '<a class="btn btn-primary btn-sm" href="' + editUrl + '"><i class="bi bi-pencil-square me-1"></i>Edit</a>' +
          (ledgerUrl ? '<a class="btn btn-outline-primary btn-sm" href="' + escapeHtml(ledgerUrl) + '"><i class="bi bi-journal-text me-1"></i>Ledger</a>' : '') +
          (invUrl ? '<a class="btn btn-outline-secondary btn-sm" href="' + invUrl + '"><i class="bi bi-receipt me-1"></i>Last Invoice</a>' : '') +
          '</div>';
      })
      .catch(function (err) {
        body.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(err.message || 'Failed to load profile') + '</div>';
      });
  }

  function getTableRows() {
    var tbl = document.getElementById('crmCustomersTable');
    if (!tbl || !tbl.tBodies[0]) return [];
    return Array.from(tbl.tBodies[0].rows);
  }

  function exportCsv(rows) {
    rows = rows || getTableRows();
    var headers = ['Code', 'Name', 'Phone', 'Email', 'City', 'Group', 'Outstanding', 'Status', 'Created'];
    var lines = [headers.join(',')];
    rows.forEach(function (tr) {
      lines.push([
        tr.getAttribute('data-code'),
        tr.getAttribute('data-name'),
        tr.getAttribute('data-phone'),
        tr.getAttribute('data-email'),
        tr.getAttribute('data-city'),
        tr.getAttribute('data-group'),
        tr.getAttribute('data-outstanding'),
        tr.getAttribute('data-status'),
        tr.getAttribute('data-created'),
      ].map(function (v) {
        v = String(v == null ? '' : v);
        if (/[",\n]/.test(v)) return '"' + v.replace(/"/g, '""') + '"';
        return v;
      }).join(','));
    });
    var blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'customers_export_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
    showToast('Export downloaded successfully.', 'primary');
  }

  function initDataTable() {
    var tbl = document.getElementById('crmCustomersTable');
    if (!tbl || typeof DataTable === 'undefined') return null;
    if (tbl.dataset.dtInit === '1') return tbl._crmDt || null;
    tbl.dataset.dtInit = '1';
    var lenSel = document.getElementById('crmPageSize');
    var pageLen = lenSel ? parseInt(lenSel.value, 10) || 25 : 25;
    var dt = new DataTable(tbl, {
      paging: true,
      searching: true,
      order: [[10, 'desc']],
      pageLength: pageLen,
      lengthChange: false,
      scrollX: true,
      columnDefs: [
        { orderable: false, targets: [0, 11] },
        { className: 'text-end', targets: [8] },
      ],
      language: {
        emptyTable: 'No customers found.',
        search: '',
        searchPlaceholder: 'Quick filter in table…',
      },
    });
    tbl._crmDt = dt;
    if (lenSel) {
      lenSel.addEventListener('change', function () {
        dt.page.len(parseInt(lenSel.value, 10) || 25).draw();
      });
    }
    var globalQ = (cfg.globalQ || '').trim();
    if (globalQ) {
      dt.search(globalQ).draw();
      highlightSearch(globalQ);
    }
    dt.on('draw', function () {
      var q = dt.search();
      if (q) highlightSearch(q);
    });
    return dt;
  }

  function highlightSearch(term) {
    term = String(term || '').trim();
    if (!term) return;
    var re;
    try {
      re = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
    } catch (_) {
      return;
    }
    document.querySelectorAll('#crmCustomersTable tbody td[data-highlight]').forEach(function (td) {
      var text = td.getAttribute('data-text') || td.textContent;
      if (!re.test(text)) {
        td.innerHTML = escapeHtml(text);
        return;
      }
      re.lastIndex = 0;
      td.innerHTML = escapeHtml(text).replace(re, '<mark class="crm-mark">$1</mark>');
    });
  }

  function initColumnToggle(dt) {
    var menu = document.getElementById('crmColToggleMenu');
    if (!menu || !dt) return;
    menu.querySelectorAll('input[type="checkbox"]').forEach(function (chk) {
      chk.addEventListener('change', function () {
        var col = parseInt(chk.getAttribute('data-col'), 10);
        var colApi = dt.column(col);
        colApi.visible(chk.checked);
      });
    });
  }

  function initImportZone() {
    var zone = document.getElementById('crmImportZone');
    var input = document.getElementById('crmImportFile');
    var nameEl = document.getElementById('crmImportFileName');
    var bar = document.getElementById('crmImportProgress');
    var form = document.getElementById('crmImportForm');
    if (!zone || !input) return;
    function setFile(file) {
      if (!file) return;
      if (nameEl) nameEl.textContent = file.name;
      if (bar) {
        bar.classList.remove('d-none');
        bar.querySelector('.progress-bar').style.width = '100%';
      }
      var dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
    }
    zone.addEventListener('dragover', function (e) {
      e.preventDefault();
      zone.classList.add('dragover');
    });
    zone.addEventListener('dragleave', function () { zone.classList.remove('dragover'); });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      zone.classList.remove('dragover');
      if (e.dataTransfer.files && e.dataTransfer.files[0]) setFile(e.dataTransfer.files[0]);
    });
    zone.addEventListener('click', function () { input.click(); });
    input.addEventListener('change', function () {
      if (input.files && input.files[0]) setFile(input.files[0]);
    });
    if (form) {
      form.addEventListener('submit', function () {
        if (bar) {
          bar.classList.remove('d-none');
          bar.querySelector('.progress-bar').style.width = '35%';
        }
      });
    }
  }

  function initGlobalSearch() {
    var form = document.getElementById('crmFilterForm');
    var global = document.getElementById('crmGlobalSearch');
    if (!form || !global) return;
    form.addEventListener('submit', function () {
      var q = global.value.trim();
      if (q) {
        ['name', 'phone', 'email', 'address', 'delivery_location'].forEach(function (n) {
          var el = form.querySelector('[name="' + n + '"]');
          if (el) el.value = '';
        });
        var typeEl = form.querySelector('[name="type"]');
        if (typeEl) typeEl.value = '';
      }
    });
  }

  function bindActions(dt) {
    app.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-crm-action]');
      if (!btn) return;
      var action = btn.getAttribute('data-crm-action');
      var id = btn.getAttribute('data-id');
      var tr = btn.closest('tr[data-customer-id]');
      if (action === 'view' && id) {
        e.preventDefault();
        openProfile(id);
      } else if (action === 'export-row' && tr) {
        e.preventDefault();
        exportCsv([tr]);
      } else if (action === 'print-row' && tr) {
        e.preventDefault();
        var w = window.open('', '_blank');
        if (!w) return;
        w.document.write(
          '<html><head><title>' + escapeHtml(tr.getAttribute('data-name')) + '</title>' +
          '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body class="p-4">' +
          '<h4>' + escapeHtml(tr.getAttribute('data-name')) + '</h4>' +
          '<p><strong>Code:</strong> ' + escapeHtml(tr.getAttribute('data-code')) + '</p>' +
          '<p><strong>Phone:</strong> ' + escapeHtml(tr.getAttribute('data-phone')) + '</p>' +
          '<p><strong>Email:</strong> ' + escapeHtml(tr.getAttribute('data-email')) + '</p>' +
          '<p><strong>Outstanding:</strong> ' + escapeHtml(tr.getAttribute('data-outstanding')) + '</p>' +
          '</body></html>'
        );
        w.document.close();
        w.print();
      } else if (action === 'refresh') {
        e.preventDefault();
        window.location.reload();
      } else if (action === 'export-all') {
        e.preventDefault();
        exportCsv(getTableRows());
      } else if (action === 'export-filtered' && dt) {
        e.preventDefault();
        var rows = [];
        dt.rows({ search: 'applied' }).every(function () {
          rows.push(this.node());
        });
        exportCsv(rows);
      } else if (action === 'print-table') {
        e.preventDefault();
        window.print();
      }
    });

    document.getElementById('crmBtnRefresh')?.addEventListener('click', function (e) {
      e.preventDefault();
      window.location.reload();
    });
  }

  function initAdvancedToggle() {
    var btn = document.getElementById('crmAdvToggle');
    var panel = document.getElementById('crmAdvPanel');
    if (!btn || !panel) return;
    btn.addEventListener('click', function () {
      panel.classList.toggle('d-none');
      btn.setAttribute('aria-expanded', panel.classList.contains('d-none') ? 'false' : 'true');
    });
  }

  if (cfg.flashMessage) showToast(cfg.flashMessage, cfg.flashType || 'success');

  animateCounters();
  initImportZone();
  initGlobalSearch();
  initAdvancedToggle();
  var dt = initDataTable();
  initColumnToggle(dt);
  bindActions(dt);

  app.querySelectorAll('[data-crm-action="view"]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      openProfile(btn.getAttribute('data-id'));
    });
  });
})();
