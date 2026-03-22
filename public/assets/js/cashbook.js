/**
 * TMS Cash Book — AJAX UI
 */
(function () {
  var cfg = window.TMS_CASHBOOK || {};
  var base = cfg.url || 'index.php?page=cashbook';
  var csrf = cfg.csrf || '';

  function apiUrl(action, params) {
    var u = new URL(base, window.location.href);
    u.searchParams.set('cb_action', action);
    if (params) {
      Object.keys(params).forEach(function (k) {
        if (params[k] !== undefined && params[k] !== null && params[k] !== '') {
          u.searchParams.set(k, String(params[k]));
        }
      });
    }
    return u.pathname + u.search;
  }

  function money(n) {
    var x = Number(n) || 0;
    return x.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  var state = {
    accountId: 0,
    period: 'monthly',
    anchor: new Date().toISOString().slice(0, 10),
    q: '',
    panel: 'transactions',
    accounts: [],
    entries: [],
    totals: null,
    from: '',
    to: '',
  };

  var searchTimer = null;

  function $(id) {
    return document.getElementById(id);
  }

  function postCashbook(action, fields) {
    var fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('cb_action', action);
    Object.keys(fields || {}).forEach(function (k) {
      if (fields[k] !== undefined && fields[k] !== null) {
        fd.append(k, fields[k]);
      }
    });
    return fetch(apiUrl(action), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      body: fd,
    }).then(function (r) {
      return r.json();
    });
  }

  function get(action, params) {
    return fetch(apiUrl(action, params), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    }).then(function (r) {
      return r.json();
    });
  }

  function toast(msg, isErr) {
    var host = $('cashbookToastHost');
    if (!host || typeof bootstrap === 'undefined' || !bootstrap.Toast) {
      window.alert(msg);
      return;
    }
    var id = 'cbt' + Date.now();
    host.insertAdjacentHTML(
      'beforeend',
      '<div id="' +
        id +
        '" class="toast align-items-center ' +
        (isErr ? 'text-bg-danger' : 'text-bg-success') +
        ' border-0" role="alert"><div class="d-flex"><div class="toast-body"></div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>'
    );
    var el = document.getElementById(id);
    if (el) {
      el.querySelector('.toast-body').textContent = msg;
      var t = new bootstrap.Toast(el, { delay: 2400 });
      t.show();
      el.addEventListener('hidden.bs.toast', function () {
        try {
          el.remove();
        } catch (e) {}
      });
    }
  }

  function setAccountSelectOptions() {
    var sel = $('cbAccountSelect');
    if (!sel) return;
    sel.innerHTML = '';
    state.accounts.forEach(function (a) {
      var o = document.createElement('option');
      o.value = String(a.id);
      o.textContent = a.name + ' — ' + money(a.balance);
      sel.appendChild(o);
    });
    if (state.accountId && state.accounts.some(function (a) { return String(a.id) === String(state.accountId); })) {
      sel.value = String(state.accountId);
    } else if (state.accounts[0]) {
      state.accountId = parseInt(state.accounts[0].id, 10);
      sel.value = String(state.accountId);
    }
  }

  function loadAccounts() {
    return get('accounts').then(function (j) {
      if (!j.ok) throw new Error(j.error || 'Load failed');
      state.accounts = j.accounts || [];
      setAccountSelectOptions();
      return state.accounts;
    });
  }

  function loadTotals() {
    if (!state.accountId) return Promise.resolve();
    return get('totals', {
      account_id: state.accountId,
      period: state.period,
      anchor: state.anchor,
    }).then(function (j) {
      if (!j.ok) throw new Error(j.error || 'Load failed');
      state.totals = j.totals;
      state.from = j.from;
      state.to = j.to;
      renderTotals();
    });
  }

  function loadEntries() {
    if (!state.accountId) return Promise.resolve();
    return get('entries', {
      account_id: state.accountId,
      period: state.period,
      anchor: state.anchor,
      q: state.q,
    }).then(function (j) {
      if (!j.ok) throw new Error(j.error || 'Load failed');
      state.entries = j.entries || [];
      state.from = j.from;
      state.to = j.to;
      renderEntries();
    });
  }

  function renderTotals() {
    var el = $('cbSummaryIncome');
    var elE = $('cbSummaryExpense');
    var elB = $('cbSummaryBalance');
    var elR = $('cbRangeLabel');
    if (!el || !state.totals) return;
    el.textContent = money(state.totals.income || 0);
    elE.textContent = money(state.totals.expense || 0);
    elB.textContent = money(state.totals.balance || 0);
    if (elR) {
      elR.textContent =
        state.period === 'all'
          ? 'All time'
          : (state.from && state.to ? state.from.slice(0, 10) + ' — ' + state.to.slice(0, 10) : '');
    }
  }

  function entryTitle(row) {
    var k = row.kind;
    if (k === 'income') return 'Income';
    if (k === 'expense') return 'Expense';
    if (k === 'transfer_out') return 'Transfer → ' + (row.peer_name || '');
    if (k === 'transfer_in') return 'Transfer ← ' + (row.peer_name || '');
    return k;
  }

  function entryAmountHtml(row) {
    var k = row.kind;
    var a = Number(row.amount) || 0;
    if (k === 'income') return '<span class="cashbook-amt-in">+' + money(a) + '</span>';
    if (k === 'expense') return '<span class="cashbook-amt-ex">−' + money(a) + '</span>';
    if (k === 'transfer_out') return '<span class="cashbook-amt-tr">−' + money(a) + '</span>';
    if (k === 'transfer_in') return '<span class="cashbook-amt-tr">+' + money(a) + '</span>';
    return money(a);
  }

  function renderEntries() {
    var host = $('cbEntryList');
    if (!host) return;
    if (!state.entries.length) {
      host.innerHTML =
        '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>No entries for this period.</div>';
      return;
    }
    var html = '';
    state.entries.forEach(function (row) {
      var dt = (row.occurred_at || '').replace('T', ' ').slice(0, 16);
      var notes = (row.notes || '').trim();
      var parcel = row.parcel_id ? ' <span class="badge text-bg-light border">Parcel #' + row.parcel_id + '</span>' : '';
      var base = (cfg.baseUrl || '').replace(/\/?$/, '/');
      var att = row.attachment_path
        ? ' <a class="small" href="' +
          encodeURI(base + row.attachment_path.replace(/^\//, '')) +
          '" target="_blank" rel="noopener">Attachment</a>'
        : '';
      var idStr = String(row.id);
      var tid = row.transfer_id != null && row.transfer_id !== '' ? String(row.transfer_id) : '';
      var canEdit = /^\d+$/.test(idStr);
      var editBtn = canEdit
        ? '<button type="button" class="btn btn-sm btn-outline-secondary cb-edit-txn" data-id="' +
          idStr +
          '">Edit</button>'
        : '';
      var delBtn =
        '<button type="button" class="btn btn-sm btn-outline-danger cb-del-entry" data-id="' +
        idStr +
        '" data-transfer="' +
        tid +
        '">Delete</button>';

      html +=
        '<div class="card cashbook-entry-card mb-2">' +
        '<div class="card-body py-2 px-3 d-flex flex-wrap align-items-start justify-content-between gap-2">' +
        '<div class="min-w-0">' +
        '<div class="small text-muted">' +
        dt +
        '</div>' +
        '<div class="fw-semibold">' +
        entryTitle(row) +
        parcel +
        '</div>' +
        (notes ? '<div class="small text-secondary mt-1">' + escapeHtml(notes) + '</div>' : '') +
        att +
        '</div>' +
        '<div class="text-end ms-auto">' +
        '<div class="mb-1">' +
        entryAmountHtml(row) +
        '</div>' +
        '<div class="btn-group btn-group-sm">' +
        editBtn +
        delBtn +
        '</div></div></div></div>';
    });
    host.innerHTML = html;
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function refresh() {
    return loadAccounts()
      .then(function () {
        return Promise.all([loadTotals(), loadEntries()]);
      })
      .catch(function (e) {
        toast(String(e.message || e), true);
      });
  }

  function openTxnModal(mode, txn) {
    var modalEl = $('cashbookTxnModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    $('cbTxnId').value = txn && txn.id ? String(txn.id) : '';
    var tmode = mode || (txn && txn.kind) || 'income';
    if (txn && txn.kind && (txn.kind === 'income' || txn.kind === 'expense')) {
      tmode = txn.kind;
    }
    var ri = document.getElementById('cbTxnIncome');
    var re = document.getElementById('cbTxnExpense');
    if (ri && re) {
      if (tmode === 'expense') {
        re.checked = true;
      } else {
        ri.checked = true;
      }
    }
    $('cbTxnAmount').value = txn && txn.amount != null ? String(txn.amount) : '';
    var oa = txn && txn.occurred_at ? String(txn.occurred_at) : '';
    if (oa.length >= 16) {
      $('cbTxnDate').value = oa.slice(0, 10);
      $('cbTxnTime').value = oa.slice(11, 16);
    } else {
      $('cbTxnDate').value = state.anchor;
      $('cbTxnTime').value = new Date().toTimeString().slice(0, 5);
    }
    $('cbTxnNotes').value = txn && txn.notes ? txn.notes : '';
    $('cbTxnParcel').value = txn && txn.parcel_id ? String(txn.parcel_id) : '';
    $('cbTxnItems').value = txn && txn.items_json ? txn.items_json : '';
    var m = bootstrap.Modal.getOrCreateInstance(modalEl);
    m.show();
  }

  function saveTxn(continueFlag) {
    var fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('cb_action', 'transaction_save');
    fd.append('id', $('cbTxnId').value || '0');
    fd.append('account_id', String(state.accountId));
    var typEl = document.querySelector('input[name="cbTxnType"]:checked');
    fd.append('txn_type', typEl ? typEl.value : 'income');
    fd.append('amount', $('cbTxnAmount').value);
    var d = $('cbTxnDate').value;
    var t = $('cbTxnTime').value || '12:00';
    fd.append('occurred_at', d + ' ' + t + ':00');
    fd.append('notes', $('cbTxnNotes').value);
    var pid = $('cbTxnParcel').value.trim();
    fd.append('parcel_id', pid);
    fd.append('items_json', $('cbTxnItems').value.trim());
    var att = $('cbTxnFile');
    if (att && att.files && att.files[0]) {
      fd.append('attachment', att.files[0]);
    }

    fetch(apiUrl('transaction_save'), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      body: fd,
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (j) {
        if (!j.ok) throw new Error(j.error || 'Save failed');
        toast('Saved');
        if (!continueFlag) {
          var mx = bootstrap.Modal.getInstance($('cashbookTxnModal'));
          if (mx) mx.hide();
        } else {
          $('cbTxnId').value = '';
          $('cbTxnAmount').value = '';
          $('cbTxnNotes').value = '';
          $('cbTxnParcel').value = '';
          $('cbTxnItems').value = '';
          if (att) att.value = '';
        }
        refresh();
      })
      .catch(function (e) {
        toast(String(e.message || e), true);
      });
  }

  function deleteEntry(idStr, transferId) {
    if (!window.confirm('Delete this entry?')) return;
    if (transferId && String(transferId).trim() !== '') {
      postCashbook('transfer_delete', { transfer_id: transferId }).then(function (j) {
        if (!j.ok) throw new Error(j.error || 'Delete failed');
        toast('Deleted');
        refresh();
      }).catch(function (e) {
        toast(String(e.message || e), true);
      });
      return;
    }
    postCashbook('transaction_delete', { id: idStr }).then(function (j) {
      if (!j.ok) throw new Error(j.error || 'Delete failed');
      toast('Deleted');
      refresh();
    }).catch(function (e) {
      toast(String(e.message || e), true);
    });
  }

  function bind() {
    var acc = $('cbAccountSelect');
    if (acc) {
      acc.addEventListener('change', function () {
        state.accountId = parseInt(acc.value, 10) || 0;
        refresh();
      });
    }

    document.querySelectorAll('.cb-period-tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        state.period = btn.getAttribute('data-period') || 'monthly';
        document.querySelectorAll('.cb-period-tab').forEach(function (b) {
          b.classList.toggle('active', b === btn);
        });
        refresh();
      });
    });

    var anchor = $('cbAnchorDate');
    if (anchor) {
      anchor.addEventListener('change', function () {
        state.anchor = anchor.value;
        refresh();
      });
    }

    var sq = $('cbSearch');
    if (sq) {
      sq.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
          state.q = sq.value.trim();
          loadEntries();
        }, 320);
      });
    }

    function wireIncomeExpense(btnId, mode) {
      var b = $(btnId);
      if (b) b.addEventListener('click', function () { openTxnModal(mode, null); });
    }
    wireIncomeExpense('cbBtnIncome', 'income');
    wireIncomeExpense('cbBtnExpense', 'expense');
    wireIncomeExpense('cbBtnIncomeDesk', 'income');
    wireIncomeExpense('cbBtnExpenseDesk', 'expense');

    $('cbSaveTxn') &&
      $('cbSaveTxn').addEventListener('click', function () {
        saveTxn(false);
      });
    $('cbSaveTxnCont') &&
      $('cbSaveTxnCont').addEventListener('click', function () {
        saveTxn(true);
      });

    document.body.addEventListener('click', function (e) {
      var ed = e.target.closest('.cb-edit-txn');
      if (ed) {
        var id = ed.getAttribute('data-id');
        var row = state.entries.find(function (r) {
          return String(r.id) === id;
        });
        if (row) openTxnModal(row.kind, row);
      }
      var del = e.target.closest('.cb-del-entry');
      if (del) {
        var idStr = del.getAttribute('data-id');
        var tid = del.getAttribute('data-transfer');
        deleteEntry(idStr, tid && /^\d+$/.test(tid) ? tid : '');
      }
    });

    $('cbTransferSave') &&
      $('cbTransferSave').addEventListener('click', function () {
        postCashbook('transfer_save', {
          from_account_id: $('cbTrFrom').value,
          to_account_id: $('cbTrTo').value,
          amount: $('cbTrAmount').value,
          occurred_at: $('cbTrDate').value + ' ' + ($('cbTrTime').value || '12:00') + ':00',
          notes: $('cbTrNotes').value,
        })
          .then(function (j) {
            if (!j.ok) throw new Error(j.error || 'Failed');
            toast('Transfer saved');
            bootstrap.Modal.getInstance($('cashbookTransferModal')).hide();
            refresh();
          })
          .catch(function (e) {
            toast(String(e.message || e), true);
          });
      });

    $('cbAccountSave') &&
      $('cbAccountSave').addEventListener('click', function () {
        postCashbook('account_save', {
          id: $('cbAccId').value || '0',
          name: $('cbAccName').value,
          type: $('cbAccType').value,
          branch_id: $('cbAccBranch').value,
        })
          .then(function (j) {
            if (!j.ok) throw new Error(j.error || 'Failed');
            toast('Account saved');
            state.accounts = j.accounts || [];
            setAccountSelectOptions();
            fillTransferSelects();
            renderAccountList();
          })
          .catch(function (e) {
            toast(String(e.message || e), true);
          });
      });

    document.querySelectorAll('.cb-nav-panel').forEach(function (a) {
      a.addEventListener('click', function (ev) {
        ev.preventDefault();
        var p = a.getAttribute('data-panel');
        state.panel = p || 'transactions';
        document.querySelectorAll('[data-cb-panel]').forEach(function (sec) {
          sec.classList.toggle('cashbook-panel-hidden', sec.getAttribute('data-cb-panel') !== state.panel);
        });
        if (state.panel === 'summary') loadAccountSummary();
        if (state.panel === 'reports') loadReports();
        try {
          var oc = bootstrap.Offcanvas.getInstance(document.getElementById('cashbookMenu'));
          if (oc) oc.hide();
        } catch (err) {}
      });
    });

    $('cbReportLoad') &&
      $('cbReportLoad').addEventListener('click', function () {
        loadReports();
      });

    $('cbAccAddNew') &&
      $('cbAccAddNew').addEventListener('click', function () {
        $('cbAccId').value = '';
        $('cbAccName').value = '';
        $('cbAccType').value = 'cash';
        if ($('cbAccBranch')) $('cbAccBranch').value = '';
      });

    /* Two-register calculator */
    (function calc() {
      var disp = $('cbCalcDisplay');
      if (!disp) return;
      var cur = '0';
      var acc = null;
      var op = null;
      var fresh = false;
      function renderD() {
        disp.textContent = cur;
      }
      function readCur() {
        var v = parseFloat(cur);
        return isNaN(v) ? 0 : v;
      }
      function fold() {
        if (acc === null || op === null) return;
        var b = readCur();
        var a = acc;
        if (op === '+') a += b;
        else if (op === '-') a -= b;
        else if (op === '*') a *= b;
        else if (op === '/') a = b !== 0 ? a / b : a;
        cur = String(Math.round(a * 1e6) / 1e6);
        acc = null;
        op = null;
        fresh = true;
        renderD();
      }
      document.querySelectorAll('[data-calc]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var t = btn.getAttribute('data-calc');
          if (t >= '0' && t <= '9') {
            if (fresh) {
              cur = t;
              fresh = false;
            } else cur = cur === '0' ? t : cur + t;
            renderD();
            return;
          }
          if (t === '.') {
            if (fresh) {
              cur = '0.';
              fresh = false;
            } else if (cur.indexOf('.') < 0) cur += '.';
            renderD();
            return;
          }
          if (t === 'C') {
            cur = '0';
            acc = null;
            op = null;
            fresh = false;
            renderD();
            return;
          }
          if (t === '=') {
            fold();
            return;
          }
          if ('+-*/'.indexOf(t) >= 0) {
            if (acc !== null && op !== null && !fresh) fold();
            acc = readCur();
            op = t;
            fresh = true;
            cur = '0';
            renderD();
          }
        });
      });
    })();
  }

  function fillTransferSelects() {
    var a = ['cbTrFrom', 'cbTrTo'];
    a.forEach(function (id) {
      var sel = $(id);
      if (!sel) return;
      var v = sel.value;
      sel.innerHTML = '';
      state.accounts.forEach(function (acc) {
        var o = document.createElement('option');
        o.value = String(acc.id);
        o.textContent = acc.name;
        sel.appendChild(o);
      });
      if (v) sel.value = v;
    });
  }

  function renderAccountList() {
    var host = $('cbAccountList');
    if (!host) return;
    var html = '';
    state.accounts.forEach(function (a) {
      html +=
        '<div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">' +
        '<div><div class="fw-semibold">' +
        escapeHtml(a.name) +
        '</div><div class="small text-muted">' +
        a.type +
        ' · Balance ' +
        money(a.balance) +
        '</div></div>' +
        '<button type="button" class="btn btn-sm btn-outline-primary cb-edit-acc" data-id="' +
        a.id +
        '">Edit</button></div>';
    });
    host.innerHTML = html || '<p class="text-muted small mb-0">No accounts</p>';
    host.querySelectorAll('.cb-edit-acc').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var acc = state.accounts.find(function (x) {
          return parseInt(x.id, 10) === id;
        });
        if (!acc) return;
        $('cbAccId').value = String(acc.id);
        $('cbAccName').value = acc.name;
        $('cbAccType').value = acc.type;
        if ($('cbAccBranch')) $('cbAccBranch').value = acc.branch_id || '';
        var mel = $('cashbookAccountsModal');
        if (mel && typeof bootstrap !== 'undefined') bootstrap.Modal.getOrCreateInstance(mel).show();
      });
    });
  }

  function loadAccountSummary() {
    var host = $('cbAccountSummary');
    if (!host || !state.accounts.length) return;
    host.innerHTML = '<div class="text-muted small">Loading…</div>';
    Promise.all(
      state.accounts.map(function (a) {
        return get('totals', {
          account_id: a.id,
          period: state.period,
          anchor: state.anchor,
        }).then(function (j) {
          return { acc: a, t: j.ok ? j.totals : null };
        });
      })
    ).then(function (rows) {
      var h = '<div class="row g-2">';
      rows.forEach(function (r) {
        var t = r.t || {};
        h +=
          '<div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body py-2">' +
          '<div class="fw-bold">' +
          escapeHtml(r.acc.name) +
          '</div>' +
          '<div class="small">Income <span class="text-success">' +
          money(t.income || 0) +
          '</span> · Expense <span class="text-danger">' +
          money(t.expense || 0) +
          '</span></div>' +
          '<div class="mt-1">Balance <strong>' +
          money(r.acc.balance || 0) +
          '</strong></div>' +
          '</div></div></div>';
      });
      h += '</div>';
      host.innerHTML = h;
    });
  }

  function loadReports() {
    var host = $('cbReportTable');
    if (!host) return;
    var rf = $('cbReportFrom') ? $('cbReportFrom').value : '';
    var rt = $('cbReportTo') ? $('cbReportTo').value : '';
    get('report_months', { account_id: state.accountId, from: rf, to: rt }).then(function (j) {
      if (!j.ok) {
        host.innerHTML = '<p class="text-danger">Failed</p>';
        return;
      }
      var rows = j.rows || [];
      if (!rows.length) {
        host.innerHTML = '<p class="text-muted small">No data</p>';
        return;
      }
      var html =
        '<table class="table table-sm table-striped mb-0"><thead><tr><th>Month</th><th class="text-end">Income</th><th class="text-end">Expense</th><th class="text-end">Net</th></tr></thead><tbody>';
      rows.forEach(function (r) {
        var net = (r.income || 0) - (r.expense || 0);
        html +=
          '<tr><td>' +
          escapeHtml(r.period) +
          '</td><td class="text-end text-success">' +
          money(r.income) +
          '</td><td class="text-end text-danger">' +
          money(r.expense) +
          '</td><td class="text-end fw-semibold">' +
          money(net) +
          '</td></tr>';
      });
      html += '</tbody></table>';
      host.innerHTML = html;
    });
  }

  function boot() {
    if (!$('cbEntryList')) return;
    var ad = $('cbAnchorDate');
    if (ad) ad.value = state.anchor;
    var rf = $('cbReportFrom');
    var rt = $('cbReportTo');
    if (rf && rt) {
      var now = new Date();
      rf.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
      rt.value = now.toISOString().slice(0, 10);
    }
    var trd = $('cbTrDate');
    var trt = $('cbTrTime');
    if (trd && !trd.value) trd.value = state.anchor;
    if (trt && !trt.value) trt.value = new Date().toTimeString().slice(0, 5);
    cfg.baseUrl = cfg.baseUrl || '';
    bind();
    loadAccounts()
      .then(function () {
        fillTransferSelects();
        renderAccountList();
        return refresh();
      })
      .catch(function (e) {
        toast(String(e.message || e), true);
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
