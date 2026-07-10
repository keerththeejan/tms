(function () {
  'use strict';

  var form = document.getElementById('userFormMain');
  if (!form) return;

  var cfg = window.TMS_USER_FORM || {};
  var isNew = !!cfg.isNew;
  var existingUsernames = (cfg.existingUsernames || []).map(function (u) {
    return String(u).toLowerCase().trim();
  });
  var currentUsername = String(cfg.currentUsername || '').toLowerCase().trim();
  var checkTimer = null;

  var fields = {
    full_name: form.querySelector('[name="full_name"]'),
    username: form.querySelector('[name="username"]'),
    email: document.getElementById('usrEmail'),
    mobile: document.getElementById('usrMobile'),
    password: form.querySelector('[name="password"]'),
    password_confirm: document.getElementById('usrPasswordConfirm'),
    role: form.querySelector('[name="role"]'),
    branch_id: form.querySelector('[name="branch_id"]'),
    department: document.getElementById('usrDepartment'),
    designation: document.getElementById('usrDesignation'),
    active: document.getElementById('activeChk')
  };

  var summary = {
    username: document.getElementById('usrSumUsername'),
    role: document.getElementById('usrSumRole'),
    branch: document.getElementById('usrSumBranch'),
    status: document.getElementById('usrSumStatus'),
    userId: document.getElementById('usrSumUserId'),
    avatar: document.getElementById('usrSumAvatar')
  };

  var PERMS = [
    { key: 'dashboard', title: 'Dashboard', desc: 'Overview & KPIs', icon: 'bi-speedometer2' },
    { key: 'accounting', title: 'Accounting', desc: 'Ledgers & vouchers', icon: 'bi-calculator' },
    { key: 'sales', title: 'Sales', desc: 'Parcels & billing', icon: 'bi-cart3' },
    { key: 'hr', title: 'HR', desc: 'Employees & payroll', icon: 'bi-people' },
    { key: 'inventory', title: 'Inventory', desc: 'Fleet & routes', icon: 'bi-truck' },
    { key: 'reports', title: 'Reports', desc: 'Analytics & exports', icon: 'bi-bar-chart-line' },
    { key: 'administration', title: 'Administration', desc: 'Users & branches', icon: 'bi-shield-check' },
    { key: 'settings', title: 'Settings', desc: 'System configuration', icon: 'bi-gear' }
  ];

  var ROLE_PERMS = {
    admin: ['dashboard', 'accounting', 'sales', 'hr', 'inventory', 'reports', 'administration', 'settings'],
    accountant: ['dashboard', 'accounting', 'reports'],
    cashier: ['dashboard', 'accounting', 'sales'],
    collector: ['dashboard', 'sales', 'reports'],
    parcel_user: ['dashboard', 'sales', 'inventory'],
    staff: ['dashboard', 'inventory', 'sales']
  };

  var ROLE_META = {
    admin: { department: 'administration', designation: 'administrator' },
    accountant: { department: 'finance', designation: 'officer' },
    cashier: { department: 'finance', designation: 'clerk' },
    collector: { department: 'operations', designation: 'officer' },
    parcel_user: { department: 'logistics', designation: 'officer' },
    staff: { department: 'operations', designation: 'clerk' }
  };

  function readSelectValue(sel) {
    if (!sel) return '';
    try {
      if (sel._choices) {
        var v = sel._choices.getValue(true);
        if (Array.isArray(v)) {
          var first = v[0];
          return String((first && first.value !== undefined) ? first.value : (first || ''));
        }
        return String(v || '');
      }
    } catch (_) { /* ignore */ }
    return String(sel.value || '');
  }

  function syncNativeSelect(sel) {
    if (!sel || !sel._choices) return;
    var val = readSelectValue(sel);
    if (val !== '') sel.value = val;
  }

  function refreshChoices(sel) {
    if (!sel || !sel._choices) return;
    try { sel.dispatchEvent(new Event('refresh-choices')); } catch (_) { /* ignore */ }
  }

  function setFieldState(el, hintEl, valid, message) {
    if (!el) return;
    el.classList.remove('is-valid', 'is-invalid');
    if (valid === true) el.classList.add('is-valid');
    if (valid === false) el.classList.add('is-invalid');
    if (hintEl) {
      hintEl.textContent = message || '';
      hintEl.classList.remove('valid', 'invalid');
      if (valid === true) hintEl.classList.add('valid');
      if (valid === false) hintEl.classList.add('invalid');
    }
  }

  function validateEmail() {
    if (!fields.email) return true;
    var val = (fields.email.value || '').trim();
    if (val === '') {
      setFieldState(fields.email, document.getElementById('usrEmailHint'), null, '');
      return true;
    }
    var ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    setFieldState(fields.email, document.getElementById('usrEmailHint'), ok, ok ? 'Valid email format' : 'Enter a valid email address');
    return ok;
  }

  function validateMobile() {
    if (!fields.mobile) return true;
    var val = (fields.mobile.value || '').replace(/\s/g, '');
    if (val === '') {
      setFieldState(fields.mobile, document.getElementById('usrMobileHint'), null, '');
      return true;
    }
    var ok = /^\+?[0-9]{8,15}$/.test(val);
    setFieldState(fields.mobile, document.getElementById('usrMobileHint'), ok, ok ? 'Valid mobile number' : 'Use 8–15 digits (optional + prefix)');
    return ok;
  }

  function validateUsername() {
    if (!fields.username) return true;
    var val = (fields.username.value || '').trim();
    if (val === '') {
      setFieldState(fields.username, document.getElementById('usrUsernameHint'), false, 'Username is required');
      return false;
    }
    var norm = val.toLowerCase();
    var taken = existingUsernames.indexOf(norm) !== -1 && norm !== currentUsername;
    if (taken) {
      setFieldState(fields.username, document.getElementById('usrUsernameHint'), false, 'Username is already taken');
      return false;
    }
    setFieldState(fields.username, document.getElementById('usrUsernameHint'), true, 'Username is available');
    return true;
  }

  function debouncedUsernameCheck() {
    if (checkTimer) window.clearTimeout(checkTimer);
    checkTimer = window.setTimeout(validateUsername, 350);
  }

  function passwordRules(pw) {
    return {
      length: pw.length >= 8,
      upper: /[A-Z]/.test(pw),
      lower: /[a-z]/.test(pw),
      number: /[0-9]/.test(pw),
      special: /[^A-Za-z0-9]/.test(pw)
    };
  }

  function updatePasswordStrength() {
    var pw = fields.password ? fields.password.value : '';
    var bar = document.getElementById('usrStrengthBar');
    var label = document.getElementById('usrStrengthLabel');
    var rules = passwordRules(pw);
    var score = 0;
    Object.keys(rules).forEach(function (k) { if (rules[k]) score++; });

    var labels = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Excellent'];
    var level = pw.length === 0 ? -1 : Math.min(4, Math.max(0, score - 1));

    if (bar) {
      bar.className = 'usr-strength-bar usr-strength-' + Math.max(0, level);
      bar.style.width = pw.length === 0 ? '0%' : ((level + 1) * 20) + '%';
    }
    if (label) {
      label.textContent = pw.length === 0 ? 'Enter a password' : labels[level];
      label.style.color = level < 1 ? '#dc2626' : level < 3 ? '#ca8a04' : '#16a34a';
    }

    document.querySelectorAll('[data-pwd-rule]').forEach(function (li) {
      var rule = li.getAttribute('data-pwd-rule');
      var met = rules[rule];
      li.classList.toggle('met', !!met);
      var icon = li.querySelector('i');
      if (icon) icon.className = met ? 'bi bi-check-circle-fill' : 'bi bi-circle';
    });
  }

  function validatePasswordConfirm() {
    if (!fields.password || !fields.password_confirm) return true;
    if (!isNew && !fields.password.value) {
      setFieldState(fields.password_confirm, document.getElementById('usrPwdConfirmHint'), null, '');
      return true;
    }
    var match = fields.password.value === fields.password_confirm.value && fields.password_confirm.value !== '';
    if (isNew && !fields.password.value) {
      setFieldState(fields.password, document.getElementById('usrPwdHint'), false, 'Password is required');
      return false;
    }
    if (fields.password_confirm.value === '' && isNew) {
      setFieldState(fields.password_confirm, document.getElementById('usrPwdConfirmHint'), false, 'Please confirm your password');
      return false;
    }
    if (fields.password.value && fields.password_confirm.value) {
      setFieldState(fields.password_confirm, document.getElementById('usrPwdConfirmHint'), match, match ? 'Passwords match' : 'Passwords do not match');
      return match;
    }
    return true;
  }

  function getRoleLabel() {
    var sel = fields.role;
    if (!sel) return '—';
    var val = readSelectValue(sel);
    var opt = sel.querySelector('option[value="' + val.replace(/"/g, '\\"') + '"]');
    return opt ? opt.textContent.trim() : (val || '—');
  }

  function getBranchLabel() {
    var sel = fields.branch_id;
    if (!sel) return '—';
    var val = readSelectValue(sel);
    if (val === '0' || val === '') return 'None';
    var opt = sel.querySelector('option[value="' + val.replace(/"/g, '\\"') + '"]');
    return opt ? opt.textContent.trim() : '—';
  }

  function updatePermissions() {
    var role = readSelectValue(fields.role) || 'staff';
    var allowed = ROLE_PERMS[role] || ROLE_PERMS.staff;
    document.querySelectorAll('[data-perm-key]').forEach(function (card) {
      var key = card.getAttribute('data-perm-key');
      var on = allowed.indexOf(key) !== -1;
      card.classList.toggle('active', on);
      var toggle = card.querySelector('.form-check-input');
      if (toggle) toggle.checked = on;
    });

    var meta = ROLE_META[role] || ROLE_META.staff;
    if (fields.department && meta.department) fields.department.value = meta.department;
    if (fields.designation && meta.designation) fields.designation.value = meta.designation;
  }

  function updateSummary() {
    var name = fields.full_name ? fields.full_name.value.trim() : '';
    var username = fields.username ? fields.username.value.trim() : '—';
    var initials = 'U';
    if (name) {
      var parts = name.split(/\s+/).filter(Boolean);
      initials = parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : name.substring(0, 2).toUpperCase();
    } else if (username && username !== '—') {
      initials = username.substring(0, 2).toUpperCase();
    }

    if (summary.avatar) summary.avatar.textContent = initials;
    if (summary.username) summary.username.textContent = username || '—';
    if (summary.role) summary.role.textContent = getRoleLabel();
    if (summary.branch) summary.branch.textContent = getBranchLabel();

    var active = fields.active ? fields.active.checked : true;
    if (summary.status) {
      summary.status.innerHTML = active
        ? '<span class="badge usr-status-badge text-bg-success">Active</span>'
        : '<span class="badge usr-status-badge text-bg-secondary">Inactive</span>';
    }

    var statusBadge = document.getElementById('usrStatusBadge');
    if (statusBadge) {
      statusBadge.className = 'badge usr-status-badge ' + (active ? 'text-bg-success' : 'text-bg-secondary');
      statusBadge.textContent = active ? 'Active' : 'Inactive';
    }

    if (summary.userId) {
      if (cfg.userId > 0) {
        summary.userId.textContent = 'USR-' + String(cfg.userId).padStart(5, '0');
      } else if (username && username !== '—') {
        summary.userId.textContent = 'USR-' + username.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 8) + ' (preview)';
      } else {
        summary.userId.textContent = 'Auto-assigned on save';
      }
    }
  }

  function togglePasswordVisibility(btn) {
    var targetId = btn.getAttribute('data-target');
    var input = document.getElementById(targetId);
    if (!input) return;
    var isPwd = input.type === 'password';
    input.type = isPwd ? 'text' : 'password';
    var icon = btn.querySelector('i');
    if (icon) icon.className = isPwd ? 'bi bi-eye-slash' : 'bi bi-eye';
    btn.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
  }

  function resetForm() {
    form.reset();
    if (fields.active) fields.active.checked = true;
    form.querySelectorAll('.is-valid, .is-invalid').forEach(function (el) {
      el.classList.remove('is-valid', 'is-invalid');
    });
    document.querySelectorAll('.usr-field-hint').forEach(function (el) {
      el.textContent = '';
      el.classList.remove('valid', 'invalid');
    });
    updatePasswordStrength();
    updatePermissions();
    updateSummary();
    refreshChoices(fields.role);
    refreshChoices(fields.branch_id);
  }

  function setLoading(loading) {
    var btns = form.querySelectorAll('[type="submit"], .usr-form-submit-btn');
    btns.forEach(function (btn) {
      btn.disabled = loading;
      btn.classList.toggle('usr-btn-loading', loading);
    });
  }

  function highlightServerErrors() {
    var alert = document.getElementById('usrFormAlert');
    if (!alert) return;
    var text = alert.textContent.toLowerCase();
    if (text.indexOf('username') !== -1 && fields.username) {
      fields.username.classList.add('is-invalid');
      fields.username.focus();
    }
    if (text.indexOf('password') !== -1 && fields.password) {
      fields.password.classList.add('is-invalid');
    }
    if (text.indexOf('full name') !== -1 && fields.full_name) {
      fields.full_name.classList.add('is-invalid');
      fields.full_name.focus();
    }
  }

  form.addEventListener('submit', function (e) {
    syncNativeSelect(fields.role);
    syncNativeSelect(fields.branch_id);

    var ok = validateUsername() & validateEmail() & validateMobile() & validatePasswordConfirm();
    if (!ok) {
      e.preventDefault();
      var firstInvalid = form.querySelector('.is-invalid');
      if (firstInvalid) firstInvalid.focus();
      return;
    }

    if (isNew && (!fields.password || !fields.password.value)) {
      e.preventDefault();
      setFieldState(fields.password, document.getElementById('usrPwdHint'), false, 'Password is required');
      fields.password.focus();
      return;
    }

    setLoading(true);
  });

  document.querySelectorAll('.usr-pwd-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () { togglePasswordVisibility(btn); });
  });

  document.getElementById('usrFormReset')?.addEventListener('click', resetForm);
  document.getElementById('usrFormResetSidebar')?.addEventListener('click', resetForm);

  fields.full_name?.addEventListener('input', updateSummary);
  fields.username?.addEventListener('input', function () {
    debouncedUsernameCheck();
    updateSummary();
  });
  fields.email?.addEventListener('input', validateEmail);
  fields.mobile?.addEventListener('input', validateMobile);
  fields.password?.addEventListener('input', function () {
    updatePasswordStrength();
    validatePasswordConfirm();
  });
  fields.password_confirm?.addEventListener('input', validatePasswordConfirm);
  fields.role?.addEventListener('change', function () {
    updatePermissions();
    updateSummary();
  });
  fields.branch_id?.addEventListener('change', updateSummary);
  fields.active?.addEventListener('change', function () {
    var label = form.querySelector('label[for="activeChk"]');
    if (label) label.textContent = fields.active.checked ? 'Active' : 'Inactive';
    updateSummary();
  });

  var roleBtn = document.getElementById('ur_submit');
  var roleSelect = fields.role;
  roleBtn?.addEventListener('click', function () {
    var input = document.getElementById('ur_name');
    if (!input || !roleSelect) return;
    var raw = (input.value || '').trim();
    if (!raw) {
      var hint = document.getElementById('usrRoleHint');
      if (hint) {
        hint.textContent = 'Enter a role name';
        hint.classList.add('invalid');
      }
      return;
    }
    var key = raw.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    if (!key) key = raw.toLowerCase();
    var exists = false;
    Array.from(roleSelect.options).forEach(function (opt) {
      if (opt.value === key) exists = true;
    });
    if (!exists) {
      var opt = document.createElement('option');
      opt.value = key;
      opt.textContent = raw;
      roleSelect.appendChild(opt);
    }
    var wasDisabled = roleSelect.disabled;
    if (wasDisabled) roleSelect.disabled = false;
    roleSelect.value = key;
    roleSelect.dispatchEvent(new Event('change'));
    roleSelect.dispatchEvent(new Event('input'));
    refreshChoices(roleSelect);
    if (wasDisabled) roleSelect.disabled = true;
    input.value = '';
    updatePermissions();
    updateSummary();
    var collapseEl = document.getElementById('quickAddRole');
    if (collapseEl && window.bootstrap) {
      bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).hide();
    }
  });

  updatePasswordStrength();
  updatePermissions();
  updateSummary();
  highlightServerErrors();
})();
