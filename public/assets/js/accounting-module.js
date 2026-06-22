/**
 * Unified Accounting Module — shared helpers, toasts, API client.
 */
(function () {
  'use strict';

  var cfg = window.TMS_ACCOUNTING || {};
  var baseUrl = cfg.baseUrl || '';

  function apiUrl(params) {
    var q = new URLSearchParams(Object.assign({ page: 'api_accounting' }, params));
    return baseUrl + 'index.php?' + q.toString();
  }

  function money(n) {
    return (parseFloat(n) || 0).toLocaleString('en-IN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function toast(message, type) {
    type = type || 'success';
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'success',
        title: message,
        showConfirmButton: false,
        timer: 2600,
        timerProgressBar: true,
      });
      return;
    }
    var host = document.getElementById('accToastHost');
    if (!host || typeof bootstrap === 'undefined') {
      alert(message);
      return;
    }
    var el = document.createElement('div');
    el.className = 'toast align-items-center text-bg-' + (type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'success') + ' border-0';
    el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
    el.querySelector('.toast-body').textContent = message;
    host.appendChild(el);
    new bootstrap.Toast(el, { delay: 2600 }).show();
  }

  function initThemeToggle() {
    var btn = document.getElementById('accThemeToggle');
    var root = document.getElementById('accModule');
    if (!btn || !root) return;
    var saved = localStorage.getItem('tms_acc_theme');
    if (saved === 'dark') {
      root.setAttribute('data-theme', 'dark');
      btn.innerHTML = '<i class="bi bi-sun" aria-hidden="true"></i> Light mode';
    }
    btn.addEventListener('click', function () {
      var dark = root.getAttribute('data-theme') === 'dark';
      root.setAttribute('data-theme', dark ? 'light' : 'dark');
      localStorage.setItem('tms_acc_theme', dark ? 'light' : 'dark');
      btn.innerHTML = dark
        ? '<i class="bi bi-moon-stars" aria-hidden="true"></i> Dark mode'
        : '<i class="bi bi-sun" aria-hidden="true"></i> Light mode';
    });
  }

  function initSelect2(scope) {
    if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
    jQuery(scope || document).find('select.acc-select2').each(function () {
      if (this.id === 'accAccountGroup') return;
      if (jQuery(this).data('select2')) return;
      jQuery(this).select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
      });
    });
  }

  function refreshSelect2(selectEl) {
    if (typeof jQuery === 'undefined' || !jQuery.fn.select2 || !selectEl) return;
    var $el = jQuery(selectEl);
    if ($el.data('select2')) {
      $el.select2('destroy');
    }
    $el.select2({
      theme: 'bootstrap-5',
      width: '100%',
      allowClear: true,
      placeholder: $el.attr('data-placeholder') || 'Select…',
    });
  }

  function getSelectValue(selectEl) {
    if (!selectEl) return '';
    if (typeof jQuery !== 'undefined' && jQuery(selectEl).data('select2')) {
      return jQuery(selectEl).val() || '';
    }
    return selectEl.value || '';
  }

  function setSelectValue(selectEl, value) {
    if (!selectEl) return;
    if (typeof jQuery !== 'undefined' && jQuery(selectEl).data('select2')) {
      jQuery(selectEl).val(value).trigger('change');
      return;
    }
    selectEl.value = value;
  }

  window.AccModule = {
    apiUrl: apiUrl,
    money: money,
    escapeHtml: escapeHtml,
    toast: toast,
    initSelect2: initSelect2,
    refreshSelect2: refreshSelect2,
    getSelectValue: getSelectValue,
    setSelectValue: setSelectValue,
    cfg: cfg,
    fetchJson: function (params, options) {
      options = options || {};
      return fetch(apiUrl(params), options).then(function (r) { return r.json(); });
    },
    postJson: function (params, body) {
      return fetch(apiUrl(params), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(Object.assign({ csrf_token: cfg.csrf || '' }, body || {})),
      }).then(function (r) { return r.json(); });
    },
  };

  document.addEventListener('DOMContentLoaded', function () {
    initThemeToggle();
    initSelect2(document.getElementById('accModule'));
  });
})();
