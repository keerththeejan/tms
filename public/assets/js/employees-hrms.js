(function () {
  'use strict';

  var app = document.getElementById('hrmsApp');
  if (!app) return;

  var apiBase = app.getAttribute('data-api-base') || '';
  var csrf = app.getAttribute('data-csrf') || '';
  var payrollUrl = app.getAttribute('data-payroll-url') || '';
  var editId = parseInt(app.getAttribute('data-edit-id') || '0', 10);
  var openNew = app.getAttribute('data-open-new') === '1';

  var state = { boot: null, page: 1, limit: 25, rows: [], total: 0, profileId: 0 };

  function $(id) { return document.getElementById(id); }

  function money(n) {
    var cfg = (window.TMS_CURRENCY || (state.boot && state.boot.currency)) || { symbol: 'Rs.', decimals: 2, locale: 'en-LK' };
    var v = Number(n) || 0;
    try {
      return cfg.symbol + ' ' + v.toLocaleString(cfg.locale || 'en-LK', { minimumFractionDigits: cfg.decimals || 2, maximumFractionDigits: cfg.decimals || 2 });
    } catch (e) {
      return cfg.symbol + ' ' + v.toFixed(2);
    }
  }

  function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function apiUrl(action, params) {
    var u = new URL(apiBase, window.location.origin);
    u.searchParams.set('emp_action', action);
    if (params) Object.keys(params).forEach(function (k) { if (params[k] !== '' && params[k] != null) u.searchParams.set(k, params[k]); });
    return u.toString();
  }

  function getFilters() {
    var fd = new FormData($('hrmsFilterForm'));
    var f = {};
    fd.forEach(function (v, k) { if (String(v).trim() !== '') f[k] = v; });
    return f;
  }

  function showAlert(msg, type) {
    var el = $('hrmsAlert');
    if (!el) return;
    el.className = 'alert alert-' + (type || 'info');
    el.textContent = msg;
    el.classList.remove('d-none');
    setTimeout(function () { el.classList.add('d-none'); }, 5000);
  }

  function fillSelect(el, items, valKey, labelKey, emptyLabel) {
    if (!el) return;
    el.innerHTML = '<option value="">' + (emptyLabel || '—') + '</option>';
    (items || []).forEach(function (it) {
      var o = document.createElement('option');
      o.value = it[valKey];
      o.textContent = typeof labelKey === 'function' ? labelKey(it) : it[labelKey];
      el.appendChild(o);
    });
  }

  function loadBoot() {
    return fetch(apiUrl('boot')).then(function (r) { return r.json(); }).then(function (res) {
      state.boot = res.data;
      if (res.data.csrf_token) csrf = res.data.csrf_token;
      $('empFormCsrf').value = csrf;
      fillSelect($('fDept'), res.data.departments, 'id', 'name', 'All');
      fillSelect($('fDesig'), res.data.designations, 'id', 'name', 'All');
      fillSelect($('empFormBranch'), res.data.branches, 'id', 'name', 'Select branch');
      fillSelect($('empFormDept'), res.data.departments, 'id', 'name', '—');
      fillSelect($('empFormDesig'), res.data.designations, 'id', 'name', '—');
      fillSelect($('empFormSupervisor'), res.data.supervisors, 'id', function (s) { return (s.emp_code ? s.emp_code + ' — ' : '') + s.name; }, '—');
    });
  }

  function statusBadge(s) {
    var map = { active: 'success', inactive: 'secondary', suspended: 'warning' };
    var k = (s || 'inactive').toLowerCase();
    return '<span class="badge bg-' + (map[k] || 'secondary') + '">' + esc(k) + '</span>';
  }

  function photoCell(row) {
    if (row.photo_url) return '<img src="' + esc(row.photo_url) + '" class="hrms-photo" alt="">';
    var ini = (row.name || '?').charAt(0).toUpperCase();
    return '<span class="hrms-photo d-inline-flex align-items-center justify-content-center small fw-bold text-muted">' + esc(ini) + '</span>';
  }

  function actionBtns(id) {
    return '<div class="btn-group btn-group-sm hrms-actions">' +
      '<button type="button" class="btn btn-outline-secondary" data-act="view" data-id="' + id + '" title="View"><i class="bi bi-eye"></i></button>' +
      '<button type="button" class="btn btn-outline-primary" data-act="edit" data-id="' + id + '" title="Edit"><i class="bi bi-pencil"></i></button>' +
      '<button type="button" class="btn btn-outline-danger" data-act="delete" data-id="' + id + '" title="Archive"><i class="bi bi-trash"></i></button>' +
      '</div>';
  }

  function renderTable() {
    var tbody = $('hrmsTableBody');
    var cards = $('hrmsCards');
    if (!state.rows.length) {
      tbody.innerHTML = '';
      cards.innerHTML = '';
      $('hrmsEmpty').classList.remove('d-none');
      return;
    }
    $('hrmsEmpty').classList.add('d-none');
    tbody.innerHTML = state.rows.map(function (r) {
      return '<tr><td><code class="small">' + esc(r.emp_code) + '</code></td><td>' + photoCell(r) + '</td>' +
        '<td class="fw-semibold">' + esc(r.name) + '</td><td>' + esc(r.nic_passport || '—') + '</td>' +
        '<td>' + esc(r.phone || r.mobile || '—') + '</td><td class="small">' + esc(r.email || '—') + '</td>' +
        '<td>' + esc(r.department_name || '—') + '</td><td>' + esc(r.designation_name || r.position || '—') + '</td>' +
        '<td>' + esc(r.branch_name || '—') + '</td><td class="text-end">' + money(r.salary_display) + '</td>' +
        '<td class="text-capitalize small">' + esc(r.employment_type || '—') + '</td><td>' + esc(r.join_date || '—') + '</td>' +
        '<td>' + statusBadge(r.status) + '</td><td class="text-end">' + actionBtns(r.id) + '</td></tr>';
    }).join('');

    cards.innerHTML = state.rows.map(function (r) {
      return '<div class="hrms-mobile-card"><div class="d-flex gap-2">' + photoCell(r) +
        '<div class="flex-grow-1 min-w-0"><div class="fw-semibold">' + esc(r.name) + '</div>' +
        '<div class="small text-muted">' + esc(r.emp_code) + ' · ' + esc(r.branch_name || '') + '</div>' +
        '<div class="small mt-1">' + money(r.salary_display) + ' · ' + statusBadge(r.status) + '</div></div></div>' +
        '<div class="mt-2 text-end">' + actionBtns(r.id) + '</div></div>';
    }).join('');
  }

  function renderPagination(data) {
    var pages = data.pages || 1;
    var page = data.page || 1;
    var from = state.total ? (page - 1) * state.limit + 1 : 0;
    var to = Math.min(page * state.limit, state.total);
    $('hrmsPageInfo').textContent = 'Showing ' + from + '–' + to + ' of ' + state.total;
    var html = '';
    for (var i = Math.max(1, page - 2); i <= Math.min(pages, page + 2); i++) {
      html += '<li class="page-item' + (i === page ? ' active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
    }
    $('hrmsPagination').innerHTML = html;
  }

  function renderStats(d) {
    document.querySelectorAll('[data-stat]').forEach(function (el) {
      el.textContent = el.getAttribute('data-stat') === 'total' || el.getAttribute('data-stat').indexOf('salary') >= 0
        ? (d[el.getAttribute('data-stat')] || 0)
        : (d[el.getAttribute('data-stat')] || 0);
    });
    document.querySelectorAll('[data-stat]').forEach(function (el) {
      var k = el.getAttribute('data-stat');
      el.textContent = ['total','active','inactive','new_this_month','permanent','contract','temporary','intern','male','female'].indexOf(k) >= 0
        ? String(d[k] || 0) : el.textContent;
    });
  }

  function loadList() {
    $('hrmsLoading').classList.remove('d-none');
    var params = Object.assign({}, getFilters(), { page_num: state.page, limit: state.limit });
    return fetch(apiUrl('list', params)).then(function (r) { return r.json(); }).then(function (res) {
      var data = res.data || res;
      state.rows = data.rows || res.employees || [];
      state.total = data.total || res.count || 0;
      renderTable();
      renderPagination(data);
    }).catch(function (e) { showAlert(e.message || 'Load failed', 'danger'); })
      .finally(function () { $('hrmsLoading').classList.add('d-none'); });
  }

  function loadStats() {
    return fetch(apiUrl('stats', getFilters())).then(function (r) { return r.json(); }).then(function (res) {
      renderStats(res.data || {});
    }).catch(function () {});
  }

  function refresh() { return Promise.all([loadList(), loadStats()]); }

  function calcSalary() {
    var fd = new FormData();
    fd.append('basic_salary', $('empFormBasic').value || 0);
    fd.append('allowance_amount', $('empFormAllow').value || 0);
    fd.append('tax_amount', $('empFormTax').value || 0);
    return fetch(apiUrl('calc_salary'), { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (res) {
      var d = res.data || {};
      $('empFormEpfE').value = d.epf_employee || 0;
      $('empFormEpfEr').value = d.epf_employer || 0;
      $('empFormEtf').value = d.etf_amount || 0;
      $('empFormNet').value = d.net_salary || 0;
    });
  }

  function resetForm() {
    $('employeeForm').reset();
    $('empFormId').value = '0';
    $('employeeModalLabel').textContent = 'New Employee';
    $('empCodeAuto').checked = true;
    $('empFormCode').readOnly = true;
    $('empFormCode').value = '';
    $('empPhotoPreview').classList.add('d-none');
    fetch(apiUrl('next_code')).then(function (r) { return r.json(); }).then(function (res) {
      if (res.data && res.data.emp_code) $('empFormCode').value = res.data.emp_code;
    });
    calcSalary();
  }

  function setField(id, val) { var el = $(id); if (el) el.value = val != null ? val : ''; }

  function openEdit(id) {
    fetch(apiUrl('get', { id: id })).then(function (r) { return r.json(); }).then(function (res) {
      var e = res.data;
      $('employeeModalLabel').textContent = 'Edit Employee';
      $('empFormId').value = e.id;
      setField('empFormCode', e.emp_code);
      setField('empFormName', e.name);
      setField('empFormFn', e.first_name);
      setField('empFormLn', e.last_name);
      setField('empFormNic', e.nic_passport);
      setField('empFormDob', e.date_of_birth);
      setField('empFormGender', e.gender);
      setField('empFormMarital', e.marital_status);
      setField('empFormNationality', e.nationality || 'Sri Lankan');
      setField('empFormBlood', e.blood_group);
      setField('empFormReligion', e.religion);
      setField('empFormAddress', e.address);
      setField('empFormDistrict', e.district);
      setField('empFormProvince', e.province);
      setField('empFormPostal', e.postal_code);
      setField('empFormPhone', e.phone);
      setField('empFormMobile', e.mobile);
      setField('empFormEmail', e.email);
      setField('empFormEcName', e.emergency_contact);
      setField('empFormEcPhone', e.emergency_phone);
      setField('empFormBranch', e.branch_id);
      setField('empFormDept', e.department_id);
      setField('empFormDesig', e.designation_id);
      setField('empFormPosition', e.position);
      setField('empFormJobTitle', e.job_title);
      setField('empFormEmpType', e.employment_type || 'permanent');
      setField('empFormRole', e.role);
      setField('empFormSupervisor', e.supervisor_id);
      setField('empFormJoin', e.join_date);
      setField('empFormConfirm', e.confirmation_date);
      setField('empFormStatus', e.status || 'active');
      setField('empFormLicense', e.license_number);
      setField('empFormLicenseExp', e.license_expiry);
      setField('empFormBasic', e.basic_salary);
      setField('empFormAllow', e.allowance_amount);
      setField('empFormOt', e.overtime_rate);
      setField('empFormTax', e.tax_amount);
      setField('empFormEpfE', e.epf_employee);
      setField('empFormEpfEr', e.epf_employer);
      setField('empFormEtf', e.etf_amount);
      setField('empFormNet', e.net_salary);
      setField('empFormBank', e.bank_name);
      setField('empFormBankBr', e.bank_branch);
      setField('empFormBankAcc', e.bank_account_no);
      setField('empFormBankHolder', e.bank_account_holder);
      setField('empFormUsername', e.system_username);
      setField('empFormRemarks', e.remarks);
      if (e.photo_url) { $('empPhotoPreview').src = e.photo_url; $('empPhotoPreview').classList.remove('d-none'); }
      bootstrap.Modal.getOrCreateInstance($('employeeModal')).show();
    }).catch(function (err) { showAlert(err.message || 'Failed to load', 'danger'); });
  }

  function openProfile(id) {
    state.profileId = id;
    fetch(apiUrl('get', { id: id })).then(function (r) { return r.json(); }).then(function (res) {
      var e = res.data;
      $('empProfileTitle').textContent = e.name + ' (' + e.emp_code + ')';
      var rows = [
        ['Department', e.department_name || '—'], ['Designation', e.designation_name || e.position || '—'],
        ['Branch', e.branch_name || '—'], ['NIC', e.nic_passport || '—'], ['Phone', e.phone || e.mobile || '—'],
        ['Email', e.email || '—'], ['Joined', e.join_date || '—'], ['Type', e.employment_type || '—'],
        ['Basic Salary', money(e.basic_salary)], ['Net Salary', money(e.net_salary)], ['Bank', (e.bank_name || '—') + ' ' + (e.bank_account_no || '')],
        ['Status', e.status || '—'], ['Remarks', e.remarks || '—']
      ];
      var photo = e.photo_url ? '<img src="' + esc(e.photo_url) + '" class="hrms-photo-lg mb-3" alt="">' : '';
      $('empProfileBody').innerHTML = photo + '<table class="table table-sm"><tbody>' +
        rows.map(function (r) { return '<tr><th class="text-muted fw-normal w-35">' + esc(r[0]) + '</th><td>' + esc(String(r[1])) + '</td></tr>'; }).join('') + '</tbody></table>';
      $('empProfileEdit').onclick = function () { bootstrap.Modal.getInstance($('employeeProfileModal')).hide(); openEdit(id); };
      bootstrap.Modal.getOrCreateInstance($('employeeProfileModal')).show();
    });
  }

  $('hrmsFilterForm').addEventListener('submit', function (e) { e.preventDefault(); state.page = 1; refresh(); });
  $('hrmsBtnReset').addEventListener('click', function () { $('hrmsFilterForm').reset(); state.page = 1; refresh(); });
  $('hrmsPageSize').addEventListener('change', function () { state.limit = parseInt(this.value, 10); state.page = 1; loadList(); });
  $('hrmsPagination').addEventListener('click', function (e) {
    var a = e.target.closest('[data-page]'); if (!a) return; e.preventDefault();
    state.page = parseInt(a.getAttribute('data-page'), 10); loadList();
  });

  app.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-act]'); if (!btn) return;
    var id = btn.getAttribute('data-id');
    var act = btn.getAttribute('data-act');
    if (act === 'view') openProfile(id);
    else if (act === 'edit') openEdit(id);
    else if (act === 'delete' && confirm('Archive this employee?')) {
      var fd = new FormData(); fd.append('csrf_token', csrf); fd.append('emp_action', 'delete'); fd.append('id', id);
      fetch(apiBase, { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function () { showAlert('Employee archived.', 'success'); refresh(); });
    }
  });

  $('hrmsBtnNew').addEventListener('click', resetForm);
  document.querySelectorAll('.hrms-salary-inp').forEach(function (el) { el.addEventListener('input', calcSalary); });
  $('empCodeAuto').addEventListener('change', function () {
    $('empFormCode').readOnly = true;
    fetch(apiUrl('next_code')).then(function (r) { return r.json(); }).then(function (res) { $('empFormCode').value = res.data.emp_code || ''; });
  });
  $('empCodeManual').addEventListener('change', function () { $('empFormCode').readOnly = false; $('empFormCode').value = ''; $('empFormCode').focus(); });

  $('employeeForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(this);
    fd.set('csrf_token', csrf);
    fd.set('emp_action', 'save');
    fetch(apiBase, { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (res) {
      if (!res.success && !res.ok) throw new Error(res.message || 'Save failed');
      bootstrap.Modal.getInstance($('employeeModal')).hide();
      showAlert('Employee saved.', 'success');
      refresh();
    }).catch(function (err) {
      var errEl = $('empFormError');
      errEl.textContent = err.message; errEl.classList.remove('d-none');
    });
  });

  $('hrmsBtnExport').addEventListener('click', function () { window.location.href = apiUrl('export_csv', getFilters()); });
  $('hrmsBtnPrint').addEventListener('click', function () { window.print(); });
  $('empProfilePrint').addEventListener('click', function () { window.print(); });

  var searchDebounce;
  $('fSearch').addEventListener('input', function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(function () { state.page = 1; refresh(); }, 350);
  });

  loadBoot().then(function () {
    refresh();
    if (editId > 0) openEdit(editId);
    else if (openNew) { resetForm(); bootstrap.Modal.getOrCreateInstance($('employeeModal')).show(); }
  });
})();
