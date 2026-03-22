/**
 * Employees index: debounced quick search, AJAX apply, DataTables re-init, URL sync.
 */
(function () {
  'use strict';

  function escapeHtml(s) {
    if (s == null) return '';
    const d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
  }

  function vehicleLabel(e) {
    var v = String(e.vehicle_no_join || '').trim();
    if (v) return v;
    var id = e.vehicle_id_join != null ? String(e.vehicle_id_join) : '';
    return id || '—';
  }

  function displayName(e) {
    var n = String(e.name || '').trim();
    if (n) return n;
    var fn = String(e.first_name || '').trim();
    var ln = String(e.last_name || '').trim();
    var x = (fn + ' ' + ln).trim();
    return x || '—';
  }

  function statusBadge(status) {
    var s = String(status || '');
    if (s === 'active') return '<span class="badge bg-success">Active</span>';
    if (s === 'suspended') return '<span class="badge bg-warning text-dark">Suspended</span>';
    return '<span class="badge bg-secondary">Inactive</span>';
  }

  function buildRowsHtml(employees, cfg) {
    var rows = [];
    for (var i = 0; i < employees.length; i++) {
      var e = employees[i];
      var id = parseInt(e.id, 10) || 0;
      var payload = encodeURIComponent(JSON.stringify(e));
      var name = escapeHtml(displayName(e));
      var code = escapeHtml(String(e.emp_code || '').trim());
      var phone = escapeHtml(String(e.phone || '').trim() || '—');
      var role = escapeHtml(String(e.role || '').trim() || '—');
      var branch = escapeHtml(String(e.branch_name || '').trim() || '—');
      var st = statusBadge(e.status);
      var editUrl = cfg.editBase + id;
      rows.push(
        '<tr data-emp-payload="' +
          payload +
          '">' +
          '<td class="text-muted small">' +
          id +
          '</td>' +
          '<td class="emp-name-cell">' +
          '<div class="emp-name-main emp-truncate" title="' +
          name +
          '">' +
          name +
          '</div>' +
          (code
            ? '<div class="emp-code-sub emp-truncate">' + code + '</div>'
            : '') +
          '</td>' +
          '<td class="emp-truncate">' +
          phone +
          '</td>' +
          '<td class="emp-truncate">' +
          role +
          '</td>' +
          '<td class="emp-truncate">' +
          branch +
          '</td>' +
          '<td>' +
          st +
          '</td>' +
          '<td class="text-end">' +
          '<div class="dropdown emp-actions-dd d-inline-block">' +
          '<button type="button" class="btn btn-sm btn-light border" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>' +
          '<ul class="dropdown-menu dropdown-menu-end">' +
          '<li><button type="button" class="dropdown-item emp-act-view" data-id="' +
          id +
          '"><i class="bi bi-eye me-2"></i>View</button></li>' +
          '<li><a class="dropdown-item" href="' +
          escapeHtml(editUrl) +
          '"><i class="bi bi-pencil-square me-2"></i>Edit</a></li>' +
          '<li><hr class="dropdown-divider"></li>' +
          '<li><form method="post" action="' +
          escapeHtml(cfg.deleteUrl) +
          '" class="px-0" onsubmit="return confirm(\'Delete this employee?\');">' +
          '<input type="hidden" name="csrf_token" value="' +
          escapeHtml(cfg.csrf) +
          '">' +
          '<input type="hidden" name="id" value="' +
          id +
          '">' +
          '<button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>' +
          '</form></li>' +
          '</ul></div></td></tr>'
      );
    }
    return rows.join('');
  }

  function buildCardsHtml(employees, cfg) {
    var parts = [];
    for (var i = 0; i < employees.length; i++) {
      var e = employees[i];
      var id = parseInt(e.id, 10) || 0;
      var payload = encodeURIComponent(JSON.stringify(e));
      var name = escapeHtml(displayName(e));
      var code = escapeHtml(String(e.emp_code || '').trim());
      var phone = escapeHtml(String(e.phone || '').trim() || '—');
      var role = escapeHtml(String(e.role || '').trim() || '—');
      var branch = escapeHtml(String(e.branch_name || '').trim() || '—');
      var st = statusBadge(e.status);
      var editUrl = cfg.editBase + id;
      parts.push(
        '<div class="card emp-card" data-emp-payload="' +
          payload +
          '">' +
          '<div class="card-body py-3 px-3">' +
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
          '<div class="min-w-0">' +
          '<div class="emp-card-title emp-truncate">' +
          name +
          '</div>' +
          (code ? '<div class="emp-card-meta">' + code + '</div>' : '') +
          '<div class="mt-2 small">' +
          '<div><i class="bi bi-telephone me-1"></i>' +
          phone +
          '</div>' +
          '<div><i class="bi bi-person-badge me-1"></i>' +
          role +
          '</div>' +
          '<div><i class="bi bi-building me-1"></i>' +
          branch +
          '</div>' +
          '</div></div>' +
          '<div class="flex-shrink-0 text-end">' +
          st +
          '<div class="dropdown emp-actions-dd mt-2">' +
          '<button type="button" class="btn btn-sm btn-light border" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>' +
          '<ul class="dropdown-menu dropdown-menu-end">' +
          '<li><button type="button" class="dropdown-item emp-act-view" data-id="' +
          id +
          '"><i class="bi bi-eye me-2"></i>View</button></li>' +
          '<li><a class="dropdown-item" href="' +
          escapeHtml(editUrl) +
          '"><i class="bi bi-pencil-square me-2"></i>Edit</a></li>' +
          '<li><hr class="dropdown-divider"></li>' +
          '<li><form method="post" action="' +
          escapeHtml(cfg.deleteUrl) +
          '" onsubmit="return confirm(\'Delete this employee?\');">' +
          '<input type="hidden" name="csrf_token" value="' +
          escapeHtml(cfg.csrf) +
          '">' +
          '<input type="hidden" name="id" value="' +
          id +
          '">' +
          '<button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>' +
          '</form></li>' +
          '</ul></div></div></div></div></div>'
      );
    }
    return parts.join('');
  }

  function fillViewModal(e, cfg) {
    var body = document.getElementById('empViewModalBody');
    var edit = document.getElementById('empViewModalEdit');
    if (!body || !edit) return;
    var id = parseInt(e.id, 10) || 0;
    edit.href = cfg.editBase + id;
    var rows = [
      ['Employee code', String(e.emp_code || '—')],
      ['Name', displayName(e)],
      ['Email', String(e.email || '—')],
      ['Phone', String(e.phone || '—')],
      ['Position', String(e.position || '—')],
      ['Role', String(e.role || '—')],
      ['Branch', String(e.branch_name || '—')],
      ['Join date', String(e.join_date || '—')],
      ['License #', String(e.license_number || '—')],
      ['License expiry', String(e.license_expiry || '—')],
      ['Vehicle', vehicleLabel(e)],
      ['Address', String(e.address || '—')],
      [
        'Status',
        e.status === 'active' ? 'Active' : e.status === 'suspended' ? 'Suspended' : 'Inactive',
      ],
    ];
    var html =
      '<div class="table-responsive"><table class="table table-sm table-borderless mb-0">' +
      rows
        .map(function (r) {
          return (
            '<tr><th class="text-muted fw-normal align-top" style="width:38%">' +
            escapeHtml(r[0]) +
            '</th><td>' +
            escapeHtml(r[1]) +
            '</td></tr>'
          );
        })
        .join('') +
      '</table></div>';
    body.innerHTML = html;
  }

  function parseEmployeeFromRow(el) {
    var raw = el.getAttribute('data-emp-payload');
    if (!raw) return null;
    try {
      return JSON.parse(decodeURIComponent(raw));
    } catch (err) {
      return null;
    }
  }

  function countActiveFilters(form) {
    var n = 0;
    var fd = new FormData(form);
    fd.forEach(function (val, key) {
      if (key === 'page') return;
      if (key === 'branch_id' && String(val) === '0') return;
      if (String(val).trim() !== '') n++;
    });
    return n;
  }

  function updateFilterBadge(form, badge) {
    if (!badge) return;
    var c = countActiveFilters(form);
    badge.textContent = String(c);
    badge.classList.toggle('bg-primary', c > 0);
    badge.classList.toggle('bg-secondary', c === 0);
  }

  function buildListUrl(form, listHref) {
    var u = new URL(listHref, window.location.href);
    u.search = '';
    var fd = new FormData(form);
    fd.forEach(function (v, k) {
      if (String(v).trim() !== '') u.searchParams.set(k, String(v));
    });
    u.searchParams.set('page', 'employees');
    u.searchParams.set('action', 'list_json');
    return u.toString();
  }

  function buildIndexUrl(form) {
    var u = new URL(window.location.href);
    u.hash = '';
    var keys = [];
    u.searchParams.forEach(function (_, k) {
      keys.push(k);
    });
    keys.forEach(function (k) {
      u.searchParams.delete(k);
    });
    var fd = new FormData(form);
    fd.forEach(function (v, k) {
      if (String(v).trim() !== '') u.searchParams.set(k, String(v));
    });
    u.searchParams.set('page', 'employees');
    u.searchParams.delete('action');
    return u.pathname + u.search;
  }

  function destroyDataTableIfAny() {
    if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.DataTable) return;
    if (jQuery.fn.DataTable.isDataTable('#employeesTable')) {
      jQuery('#employeesTable').DataTable().destroy();
    }
  }

  function initDataTable() {
    if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.DataTable) return;
    jQuery('#employeesTable').DataTable({
      responsive: false,
      pageLength: 25,
      order: [[0, 'desc']],
      columnDefs: [{ targets: [6], orderable: false, searchable: false }],
      scrollX: false,
      autoWidth: false,
      dom:
        '<"row g-2 mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
        '<"row"<"col-12"tr>>' +
        '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      language: {
        search: 'Search table:',
        searchPlaceholder: 'Filter…',
        lengthMenu: 'Show _MENU_ rows',
        emptyTable: 'No employees',
      },
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('empEmployeesRoot');
    if (!root) return;

    var listJsonBase = root.getAttribute('data-list-json') || '';
    var cfg = {
      csrf: root.getAttribute('data-csrf') || '',
      deleteUrl: root.getAttribute('data-delete-url') || '',
      editBase: root.getAttribute('data-edit-base') || '',
    };

    var form = document.getElementById('empFilterForm');
    var tbody = document.querySelector('#employeesTable tbody');
    var cardsEl = document.getElementById('empCards');
    var emptyEl = document.getElementById('empEmptyState');
    var badge = document.getElementById('empFilterBadge');
    var searchInput = document.getElementById('empLiveSearch');
    var modalEl = document.getElementById('empViewModal');

    if (!form || !tbody || !cardsEl) return;

    updateFilterBadge(form, badge);

    destroyDataTableIfAny();
    initDataTable();

    var debounceTimer;
    function scheduleSearch() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        loadEmployees(false);
      }, 320);
    }

    function loadEmployees(pushUrl) {
      var url = buildListUrl(form, listJsonBase);
      fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (r) {
          if (!r.ok) throw new Error('Request failed');
          return r.json();
        })
        .then(function (data) {
          if (!data || !data.ok || !Array.isArray(data.employees)) throw new Error('Bad response');
          var emps = data.employees;
          if (emps.length === 0) {
            tbody.innerHTML = '';
            cardsEl.innerHTML = '';
            if (emptyEl) emptyEl.classList.remove('d-none');
          } else {
            if (emptyEl) emptyEl.classList.add('d-none');
            tbody.innerHTML = buildRowsHtml(emps, cfg);
            cardsEl.innerHTML = buildCardsHtml(emps, cfg);
          }
          destroyDataTableIfAny();
          initDataTable();
          updateFilterBadge(form, badge);
          if (pushUrl) {
            try {
              history.pushState({}, '', buildIndexUrl(form));
            } catch (e) {}
          }
        })
        .catch(function () {
          alert('Could not load employees. Check your connection and try again.');
        });
    }

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        updateFilterBadge(form, badge);
        scheduleSearch();
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      loadEmployees(true);
    });

    document.addEventListener(
      'click',
      function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('.emp-act-view') : null;
        if (!btn) return;
        var tr = btn.closest('tr');
        var card = btn.closest('.emp-card');
        var el = tr || card;
        if (!el) return;
        var emp = parseEmployeeFromRow(el);
        if (!emp) return;
        e.preventDefault();
        fillViewModal(emp, cfg);
        if (modalEl && window.bootstrap) {
          var m = bootstrap.Modal.getOrCreateInstance(modalEl);
          m.show();
        }
      },
      true
    );

    window.addEventListener('popstate', function () {
      location.reload();
    });
  });
})();
