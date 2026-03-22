/**
 * Parcels list: filters, quick edit, bulk print, debounced search, toast feedback.
 */
(function () {
  var cfg = window.TMS_PARCELS || {};
  var indexUrl = cfg.indexUrl || cfg.baseUrl || 'index.php';
  var csrf = cfg.csrf || '';
  var quickUrl = cfg.quickUpdateUrl || '';

  function debounce(fn, ms) {
    var t;
    return function () {
      var a = arguments;
      var th = this;
      clearTimeout(t);
      t = setTimeout(function () {
        fn.apply(th, a);
      }, ms);
    };
  }

  function resolveUrl(queryPairs) {
    var u = new URL(indexUrl, window.location.href);
    Object.keys(queryPairs).forEach(function (k) {
      u.searchParams.set(k, queryPairs[k]);
    });
    return u.pathname + (u.search || '');
  }

  function showToast(message, isError) {
    var host = document.getElementById('parcelsToastHost');
    if (!host || typeof bootstrap === 'undefined' || !bootstrap.Toast) {
      if (message) window.alert(message);
      return;
    }
    var id = 'pt' + Date.now();
    var cls = isError ? 'text-bg-danger' : 'text-bg-success';
    host.insertAdjacentHTML(
      'beforeend',
      '<div id="' +
        id +
        '" class="toast align-items-center ' +
        cls +
        ' border-0" role="alert"><div class="d-flex">' +
        '<div class="toast-body"></div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>'
    );
    var el = document.getElementById(id);
    if (el) {
      el.querySelector('.toast-body').textContent = message;
      var t = new bootstrap.Toast(el, { delay: 2200 });
      t.show();
      el.addEventListener('hidden.bs.toast', function () {
        try {
          el.remove();
        } catch (e) {
          /* ignore */
        }
      });
    }
  }

  function postQuickUpdate(id, field, value) {
    var fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('id', String(id));
    fd.append('field', field);
    fd.append('value', value);
    return fetch(quickUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      body: fd,
    }).then(function (r) {
      return r.text().then(function (text) {
        var j = null;
        try {
          j = JSON.parse(text);
        } catch (e) {
          j = null;
        }
        return { ok: r.ok, json: j };
      });
    });
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function escapeAttr(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;');
  }

  function bindQuickEdit() {
    document.addEventListener('click', function (e) {
      var cell = e.target.closest('.pf-qe');
      if (!cell || cell.querySelector('select, input')) return;
      if (cell.classList.contains('pf-qe-busy')) return;
      var id = parseInt(cell.getAttribute('data-parcel-id') || '0', 10);
      var field = cell.getAttribute('data-qe-field') || '';
      if (!id || !field) return;

      var cur = cell.getAttribute('data-qe-value') || '';
      var wrap = document.createElement('div');
      wrap.className = 'pf-qe-editor';

      if (field === 'status') {
        var sel = document.createElement('select');
        sel.className = 'form-select form-select-sm';
        var opts = cfg.statusOptions || {};
        Object.keys(opts).forEach(function (k) {
          var o = document.createElement('option');
          o.value = k;
          o.textContent = opts[k];
          if (k === cur) o.selected = true;
          sel.appendChild(o);
        });
        wrap.appendChild(sel);
        cell.innerHTML = '';
        cell.appendChild(wrap);
        sel.focus();
        sel.addEventListener('change', function () {
          var v = sel.value;
          cell.classList.add('pf-qe-busy');
          postQuickUpdate(id, 'status', v).then(function (res) {
            cell.classList.remove('pf-qe-busy');
            if (!res.json || !res.json.ok) {
              showToast((res.json && res.json.error) || 'Could not save', true);
              cell.innerHTML = cell.getAttribute('data-qe-html-backup') || '';
              return;
            }
            var badgeClass = res.json.badgeClass || 'badge-soft-secondary';
            var label = res.json.label || v;
            cell.setAttribute('data-qe-value', res.json.value || v);
            cell.innerHTML =
              '<span class="badge badge-soft ' +
              badgeClass +
              '">' +
              escapeHtml(label) +
              '</span>';
            cell.setAttribute('data-qe-html-backup', cell.innerHTML);
            showToast('Status updated');
          });
        });
        return;
      }

      if (field === 'delivery_route') {
        var inp = document.createElement('input');
        inp.type = 'text';
        inp.className = 'form-control form-control-sm';
        inp.value = cur;
        inp.setAttribute('list', 'pfParcelRouteDatalist');
        wrap.appendChild(inp);
        cell.innerHTML = '';
        cell.appendChild(wrap);
        inp.focus();
        inp.select();
        var routeSaved = false;
        function saveRoute() {
          if (routeSaved || cell.dataset.qeSaving === '1') return;
          routeSaved = true;
          cell.dataset.qeSaving = '1';
          var v = inp.value.trim();
          cell.classList.add('pf-qe-busy');
          postQuickUpdate(id, 'delivery_route', v).then(function (res) {
            cell.classList.remove('pf-qe-busy');
            delete cell.dataset.qeSaving;
            if (!res.json || !res.json.ok) {
              showToast((res.json && res.json.error) || 'Could not save', true);
              cell.innerHTML = cell.getAttribute('data-qe-html-backup') || '';
              return;
            }
            var disp = res.json.display || (v || '—');
            cell.setAttribute('data-qe-value', res.json.value != null ? res.json.value : v);
            cell.innerHTML =
              '<span class="cell-ellipsis" title="' +
              escapeAttr(disp) +
              '">' +
              escapeHtml(disp) +
              '</span>';
            cell.setAttribute('data-qe-html-backup', cell.innerHTML);
            showToast('Route updated');
          });
        }
        inp.addEventListener('keydown', function (ev) {
          if (ev.key === 'Enter') {
            ev.preventDefault();
            saveRoute();
          }
          if (ev.key === 'Escape') {
            routeSaved = true;
            cell.innerHTML = cell.getAttribute('data-qe-html-backup') || '';
          }
        });
        inp.addEventListener('blur', function () {
          setTimeout(saveRoute, 150);
        });
        return;
      }

      if (field === 'vehicle_no') {
        var vin = document.createElement('input');
        vin.type = 'text';
        vin.className = 'form-control form-control-sm';
        vin.value = cur;
        vin.setAttribute('list', 'pfParcelVehicleDatalist');
        wrap.appendChild(vin);
        cell.innerHTML = '';
        cell.appendChild(wrap);
        vin.focus();
        vin.select();
        var vehSaved = false;
        function saveVeh() {
          if (vehSaved || cell.dataset.qeSaving === '1') return;
          vehSaved = true;
          cell.dataset.qeSaving = '1';
          var v = vin.value.trim();
          cell.classList.add('pf-qe-busy');
          postQuickUpdate(id, 'vehicle_no', v).then(function (res) {
            cell.classList.remove('pf-qe-busy');
            delete cell.dataset.qeSaving;
            if (!res.json || !res.json.ok) {
              showToast((res.json && res.json.error) || 'Could not save', true);
              cell.innerHTML = cell.getAttribute('data-qe-html-backup') || '';
              return;
            }
            var disp = res.json.display || (v || '—');
            var val = res.json.value != null ? res.json.value : v;
            cell.setAttribute('data-qe-value', val);
            if (val) {
              var href = resolveUrl({ page: 'parcels', vehicle_no: val });
              cell.innerHTML =
                '<a href="' +
                escapeAttr(href) +
                '" class="text-decoration-none"><span class="cell-ellipsis" title="' +
                escapeAttr(disp) +
                '">' +
                escapeHtml(disp) +
                '</span></a>';
            } else {
              cell.innerHTML = '—';
            }
            cell.setAttribute('data-qe-html-backup', cell.innerHTML);
            showToast('Vehicle updated');
          });
        }
        vin.addEventListener('keydown', function (ev) {
          if (ev.key === 'Enter') {
            ev.preventDefault();
            saveVeh();
          }
          if (ev.key === 'Escape') {
            vehSaved = true;
            cell.innerHTML = cell.getAttribute('data-qe-html-backup') || '';
          }
        });
        vin.addEventListener('blur', function () {
          setTimeout(saveVeh, 150);
        });
      }
    });
  }

  function bindFilterCollapse() {
    var col = document.getElementById('parcelsFiltersBody');
    if (!col || !window.bootstrap || !bootstrap.Collapse) return;
    var key = 'tms_parcels_filters_open';
    var saved = localStorage.getItem(key);
    if (saved === '0') {
      try {
        bootstrap.Collapse.getOrCreateInstance(col, { toggle: false }).hide();
      } catch (e) {
        /* ignore */
      }
    }
    col.addEventListener('hidden.bs.collapse', function () {
      localStorage.setItem(key, '0');
    });
    col.addEventListener('shown.bs.collapse', function () {
      localStorage.setItem(key, '1');
    });
  }

  function bindDebouncedSearch() {
    var form = document.getElementById('parcelsFilterForm');
    var q = document.getElementById('filter_q');
    if (!form || !q) return;
    var go = debounce(function () {
      if (q.value.trim().length >= 2 || q.value.trim() === '') {
        form.requestSubmit();
      }
    }, 450);
    q.addEventListener('input', go);
  }

  function bindAutoSubmitFilters() {
    var form = document.getElementById('parcelsFilterForm');
    var toggle = document.getElementById('parcelsAutoApplyToggle');
    if (!form || !toggle) return;
    try {
      var stored = localStorage.getItem('tms_parcels_auto_apply');
      if (stored === '1') toggle.checked = true;
    } catch (e) {
      /* ignore */
    }
    toggle.addEventListener('change', function () {
      try {
        localStorage.setItem('tms_parcels_auto_apply', toggle.checked ? '1' : '0');
      } catch (e2) {
        /* ignore */
      }
    });
    function maybeSubmit(ev) {
      if (!toggle.checked) return;
      var t = ev.target;
      if (!t || !t.name) return;
      if (t.id === 'filter_q') return;
      if (t.type === 'hidden') return;
      form.requestSubmit();
    }
    form.addEventListener('change', maybeSubmit);
  }

  function bindSelectAll() {
    var all = document.getElementById('parcelsSelectAll');
    if (!all) return;
    all.addEventListener('change', function () {
      document.querySelectorAll('.parcel-row-check').forEach(function (c) {
        c.checked = all.checked;
      });
    });
  }

  function selectedIds() {
    var ids = [];
    document.querySelectorAll('.parcel-row-check:checked').forEach(function (c) {
      var id = parseInt(c.value || '0', 10);
      if (id) ids.push(id);
    });
    return ids;
  }

  /**
   * Invoice preview in Bootstrap modal (iframe ?embed=1). No full-page navigation.
   */
  function bindParcelPrintModal() {
    var modalEl = document.getElementById('parcelPrintModal');
    var frame = document.getElementById('parcelPrintFrame');
    var loading = document.getElementById('parcelPrintLoading');
    var printBtn = document.getElementById('parcelPrintModalPrintBtn');
    if (!modalEl || !frame || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
      return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var loadGen = 0;

    function parcelPrintUrl(id) {
      var u = new URL(indexUrl, window.location.href);
      u.searchParams.set('page', 'parcel_print');
      u.searchParams.set('id', String(id));
      u.searchParams.set('embed', '1');
      u.searchParams.set('_pv', String(Date.now()));
      return u.href;
    }

    function openParcelPrint(id) {
      if (!id) return;
      var myGen = ++loadGen;
      var titleEl = document.getElementById('parcelPrintModalLabel');
      if (titleEl) titleEl.textContent = 'Invoice preview #' + id;
      if (printBtn) printBtn.disabled = true;
      if (loading) loading.classList.remove('d-none');
      frame.classList.add('d-none');

      function finishLoad() {
        if (myGen !== loadGen) {
          return;
        }
        if (loading) loading.classList.add('d-none');
        frame.classList.remove('d-none');
        if (printBtn) printBtn.disabled = false;
      }

      function beginLoad() {
        if (myGen !== loadGen) {
          return;
        }
        frame.addEventListener('load', function onLoad() {
          finishLoad();
        }, { once: true });
        frame.src = parcelPrintUrl(id);
        window.setTimeout(function () {
          if (myGen !== loadGen) return;
          if (loading && !loading.classList.contains('d-none')) {
            showToast('Invoice did not finish loading. Check the URL or try again.', true);
          }
        }, 15000);
      }

      /* Iframes in display:none often do not navigate until visible; wait for modal shown. */
      if (modalEl.classList.contains('show')) {
        beginLoad();
      } else {
        modalEl.addEventListener(
          'shown.bs.modal',
          function onModalShown() {
            if (myGen !== loadGen) return;
            beginLoad();
          },
          { once: true }
        );
        modal.show();
      }
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.btn-parcel-print');
      if (!btn) return;
      e.preventDefault();
      var id = btn.getAttribute('data-parcel-id');
      if (!id) return;
      openParcelPrint(id);
    });

    if (printBtn) {
      printBtn.addEventListener('click', function () {
        try {
          var w = frame.contentWindow;
          if (!w) {
            showToast('Invoice not ready yet.', true);
            return;
          }
          w.focus();
          w.print();
        } catch (err) {
          showToast('Could not print. Wait for the invoice to finish loading.', true);
        }
      });
    }

    modalEl.addEventListener('hidden.bs.modal', function () {
      if (printBtn) printBtn.disabled = true;
      frame.classList.add('d-none');
      if (loading) loading.classList.add('d-none');
      try {
        frame.src = 'about:blank';
      } catch (e2) {
        /* ignore */
      }
    });
  }

  function bindPrintList() {
    var btn = document.getElementById('parcelsPrintListBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
      window.print();
    });
  }

  function bindPrintSelected() {
    var btn = document.getElementById('parcelsPrintSelectedBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var ids = selectedIds();
      if (!ids.length) {
        showToast('Select at least one parcel', true);
        return;
      }
      var doc = document.querySelector('.parcels-print-document');
      var blocks = doc ? doc.querySelectorAll('.ppd-block') : [];
      if (!blocks.length) {
        showToast('Nothing to print on this page', true);
        return;
      }
      var idSet = {};
      ids.forEach(function (id) {
        idSet[id] = true;
      });
      blocks.forEach(function (el) {
        var pid = parseInt(el.getAttribute('data-parcel-id') || '0', 10);
        if (!idSet[pid]) el.classList.add('ppd-print-hide');
      });
      function cleanup() {
        document.querySelectorAll('.parcels-print-document .ppd-print-hide').forEach(function (el) {
          el.classList.remove('ppd-print-hide');
        });
        window.removeEventListener('afterprint', cleanup);
      }
      window.addEventListener('afterprint', cleanup);
      window.print();
    });
  }

  function backupQeHtml() {
    document.querySelectorAll('.pf-qe').forEach(function (cell) {
      if (!cell.getAttribute('data-qe-html-backup')) {
        cell.setAttribute('data-qe-html-backup', cell.innerHTML);
      }
    });
  }

  var itemHtmlCache = {};

  function escHtmlJs(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function fmtMoneyVal(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '—';
    return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function buildItemsTableHtml(items) {
    if (!items || !items.length) {
      return '<p class="small text-muted mb-0">No line items recorded for this parcel.</p>';
    }
    var sumLine = 0;
    var sumAdd = 0;
    var rows = items
      .map(function (it) {
        var amt = it.amount != null && !isNaN(it.amount) ? Number(it.amount) : 0;
        var add = it.additional != null && !isNaN(it.additional) ? Number(it.additional) : 0;
        sumLine += amt;
        sumAdd += add;
        var addCell = '';
        if (it.additionalTags && it.additionalTags.length) {
          addCell = it.additionalTags
            .map(function (t) {
              return (
                '<span class="badge pf-add-tag bg-secondary bg-opacity-10 text-dark border me-1 mb-1">+' +
                fmtMoneyVal(t) +
                '</span>'
              );
            })
            .join(' ');
        } else if (it.additional != null && it.additional > 0) {
          addCell =
            '<span class="badge pf-add-tag bg-secondary bg-opacity-10 text-dark border">+' +
            fmtMoneyVal(it.additional) +
            '</span>';
        } else {
          addCell = '—';
        }
        return (
          '<tr><td class="text-muted">' +
          it.no +
          '</td><td>' +
          escHtmlJs(it.description) +
          '</td><td class="text-end">' +
          fmtMoneyVal(it.qty) +
          '</td><td class="text-end">' +
          (it.rate != null ? fmtMoneyVal(it.rate) : '—') +
          '</td><td class="text-end">' +
          (it.amount != null ? fmtMoneyVal(it.amount) : '—') +
          '</td><td>' +
          addCell +
          '</td></tr>'
        );
      })
      .join('');
    var grand = sumLine + sumAdd;
    return (
      '<table class="table table-sm table-bordered parcel-items-nested mb-0"><thead><tr><th style="width:2.5rem">No</th><th>Description</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th>Additional</th></tr></thead><tbody>' +
      rows +
      '</tbody><tfoot><tr><td colspan="4" class="text-end text-muted small">Subtotals</td><td class="text-end fw-semibold">' +
      fmtMoneyVal(sumLine) +
      '</td><td>' +
      (sumAdd > 0 ? '<span class="fw-semibold">+' + fmtMoneyVal(sumAdd) + '</span>' : '—') +
      '</td></tr><tr><td colspan="6" class="text-end"><span class="text-muted small me-2">Lines + additional</span><span class="fw-bold">' +
      fmtMoneyVal(grand) +
      '</span></td></tr></tfoot></table>'
    );
  }

  function fillParcelItemHosts(pid, html) {
    document.querySelectorAll('.parcel-items-host[data-parcel-id="' + pid + '"]').forEach(function (h) {
      h.innerHTML = html;
    });
  }

  function loadAndFillParcelItems(pid) {
    var hosts = document.querySelectorAll('.parcel-items-host[data-parcel-id="' + pid + '"]');
    if (!hosts.length) return;
    if (Object.prototype.hasOwnProperty.call(itemHtmlCache, pid)) {
      fillParcelItemHosts(pid, itemHtmlCache[pid]);
      return;
    }
    fillParcelItemHosts(
      pid,
      '<div class="small text-muted py-2 parcel-items-loading"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading…</div>'
    );
    var url = resolveUrl({ page: 'parcels', action: 'parcel_items_json', id: String(pid) });
    fetch(url, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (r) {
        return r.text().then(function (text) {
          try {
            return JSON.parse(text);
          } catch (e) {
            return null;
          }
        });
      })
      .then(function (data) {
        if (!data || !data.ok) {
          fillParcelItemHosts(pid, '<div class="text-danger small">Could not load line items.</div>');
          return;
        }
        var html = buildItemsTableHtml(data.items || []);
        itemHtmlCache[pid] = html;
        fillParcelItemHosts(pid, html);
      })
      .catch(function () {
        fillParcelItemHosts(pid, '<div class="text-danger small">Network error.</div>');
      });
  }

  function bindParcelItemsExpand() {
    if (typeof bootstrap === 'undefined') {
      return;
    }
    document.querySelectorAll('.parcel-items-collapse').forEach(function (col) {
      col.addEventListener('shown.bs.collapse', function () {
        var pid = col.getAttribute('data-parcel-id');
        if (!pid) return;
        document.querySelectorAll('.parcel-expand-btn[data-parcel-id="' + pid + '"]').forEach(function (b) {
          b.setAttribute('aria-expanded', 'true');
        });
        loadAndFillParcelItems(pid);
      });
      col.addEventListener('hidden.bs.collapse', function () {
        var pid = col.getAttribute('data-parcel-id');
        if (!pid) return;
        document.querySelectorAll('.parcel-expand-btn[data-parcel-id="' + pid + '"]').forEach(function (b) {
          b.setAttribute('aria-expanded', 'false');
        });
      });
    });
    document.addEventListener('click', function (e) {
      if (e.target.closest('.parcel-expand-btn')) {
        e.stopPropagation();
      }
    });
  }

  function bootParcelsPage() {
    bindQuickEdit();
    bindFilterCollapse();
    bindDebouncedSearch();
    bindAutoSubmitFilters();
    bindSelectAll();
    bindParcelPrintModal();
    bindPrintList();
    bindPrintSelected();
    bindParcelItemsExpand();
    backupQeHtml();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootParcelsPage);
  } else {
    bootParcelsPage();
  }
})();
