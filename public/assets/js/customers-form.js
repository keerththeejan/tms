(function () {
  'use strict';

  var form = document.getElementById('crmCustomerForm');
  if (!form) return;

  var cfg = window.TMS_CUSTOMER_FORM || {};
  var existingPhones = (cfg.existingPhones || []).map(function (p) { return String(p).replace(/\D/g, ''); });
  var existingEmails = (cfg.existingEmails || []).map(function (e) { return String(e).toLowerCase().trim(); });
  var currentId = parseInt(cfg.currentId, 10) || 0;

  var fields = {
    name: form.querySelector('[name="name"]'),
    phone: form.querySelector('[name="phone"]'),
    email: form.querySelector('[name="email"]'),
    address: form.querySelector('[name="address"]'),
    customer_type: form.querySelector('[name="customer_type"]'),
    delivery_location: document.getElementById('delivery_location_value'),
  };

  var summary = {
    name: document.getElementById('crmSumName'),
    code: document.getElementById('crmSumCode'),
    phone: document.getElementById('crmSumPhone'),
    group: document.getElementById('crmSumGroup'),
    balance: document.getElementById('crmSumBalance'),
    status: document.getElementById('crmSumStatus'),
    created: document.getElementById('crmSumCreated'),
    avatar: document.getElementById('crmSumAvatar'),
  };

  function initials(name) {
    name = String(name || '').trim();
    if (!name) return '?';
    var parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }

  function readSelectText(sel) {
    if (!sel) return '';
    var opt = sel.options[sel.selectedIndex];
    return opt ? opt.textContent.trim() : '';
  }

  function normalizePhone(v) {
    return String(v || '').replace(/\D/g, '');
  }

  function formatPhoneInput(el) {
    if (!el) return;
    var digits = normalizePhone(el.value).slice(0, 15);
    if (digits.length <= 3) {
      el.value = digits;
      return;
    }
    if (digits.length <= 7) {
      el.value = digits.slice(0, 3) + ' ' + digits.slice(3);
      return;
    }
    el.value = digits.slice(0, 3) + ' ' + digits.slice(3, 7) + ' ' + digits.slice(7);
  }

  function setHint(el, hintEl, valid, msg) {
    if (!el) return;
    el.classList.remove('is-valid', 'is-invalid');
    if (valid === true) el.classList.add('is-valid');
    if (valid === false) el.classList.add('is-invalid');
    if (hintEl) {
      hintEl.textContent = msg || '';
      hintEl.className = 'form-text crm-ui-hint' + (valid === false ? ' text-danger' : valid === true ? ' text-success' : '');
    }
  }

  function validateEmail() {
    var el = fields.email;
    var hint = document.getElementById('crmEmailHint');
    if (!el) return true;
    var v = el.value.trim();
    if (v === '') {
      setHint(el, hint, null, '');
      return true;
    }
    var ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    var dup = existingEmails.indexOf(v.toLowerCase()) >= 0;
    if (!ok) {
      setHint(el, hint, false, 'Enter a valid email address.');
      return false;
    }
    if (dup) {
      setHint(el, hint, false, 'This email may already exist.');
      return false;
    }
    setHint(el, hint, true, 'Email looks good.');
    return true;
  }

  function validatePhone() {
    var el = fields.phone;
    var hint = document.getElementById('crmPhoneHint');
    if (!el) return true;
    var v = normalizePhone(el.value);
    if (v === '') {
      setHint(el, hint, null, '');
      return true;
    }
    var dup = existingPhones.indexOf(v) >= 0;
    if (dup) {
      setHint(el, hint, false, 'This phone number may already exist.');
      return false;
    }
    setHint(el, hint, true, 'Phone number looks good.');
    return true;
  }

  function validateName() {
    var el = fields.name;
    var hint = document.getElementById('crmNameHint');
    if (!el) return false;
    var v = el.value.trim();
    if (v === '') {
      setHint(el, hint, false, 'Customer name is required.');
      return false;
    }
    setHint(el, hint, true, '');
    return true;
  }

  function updateSummary() {
    var name = fields.name ? fields.name.value.trim() : '';
    if (summary.name) summary.name.textContent = name || '—';
    if (summary.avatar) summary.avatar.textContent = initials(name);
    if (summary.code) summary.code.textContent = cfg.customerCode || ('CUS-' + String(currentId || 'NEW').padStart(5, '0'));
    if (summary.phone) summary.phone.textContent = (fields.phone && fields.phone.value.trim()) || '—';
    if (summary.group) {
      var t = fields.customer_type ? readSelectText(fields.customer_type) : '';
      summary.group.textContent = t || '—';
    }
    if (summary.status) {
      var type = fields.customer_type ? fields.customer_type.value : '';
      if (type === 'corporate') {
        summary.status.innerHTML = '<span class="badge crm-badge-vip">VIP</span>';
      } else {
        summary.status.innerHTML = '<span class="badge bg-success-subtle text-success">Active</span>';
      }
    }
    if (summary.created) summary.created.textContent = cfg.createdAt || 'On save';
    if (summary.balance) summary.balance.textContent = cfg.outstanding || 'LKR 0.00';
  }

  function focusFirstInvalid() {
    var bad = form.querySelector('.is-invalid');
    if (bad && typeof bad.focus === 'function') bad.focus();
  }

  if (fields.name) fields.name.addEventListener('input', function () { validateName(); updateSummary(); });
  if (fields.phone) {
    fields.phone.addEventListener('input', function () { formatPhoneInput(fields.phone); validatePhone(); updateSummary(); });
  }
  if (fields.email) fields.email.addEventListener('input', function () { validateEmail(); updateSummary(); });
  if (fields.address) fields.address.addEventListener('input', updateSummary);
  if (fields.customer_type) fields.customer_type.addEventListener('change', updateSummary);

  form.addEventListener('submit', function (e) {
    var ok = validateName() && validatePhone() && validateEmail();
    if (!ok) {
      e.preventDefault();
      focusFirstInvalid();
    }
  });

  updateSummary();
})();
