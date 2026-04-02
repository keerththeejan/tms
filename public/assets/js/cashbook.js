/**
 * TMS Cash Book — AJAX UI
 */
(function () {
  var cfg = window.TMS_CASHBOOK || {};
  var base = cfg.url || 'index.php?page=cashbook';
  var csrf = cfg.csrf || '';
  var customersSaveUrl = cfg.customersSaveUrl || '';

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

  function accountLabel(a) {
    if (!a) return '';
    var st = (a.status || 'active') === 'inactive' ? ' (Inactive)' : '';
    return a.name + st + ' — ' + money(a.balance);
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
    mgmtPage: 1,
    mgmtSort: 'default',
    mgmtSelectedId: null,
    mgmtFlashAccountId: null,
    mgmtLoadedAccount: null,
  };

  var searchTimer = null;
  var mgmtFilterTimer = null;
  var parcelTxnTimer = null;

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
      var ct = (r.headers.get('content-type') || '').toLowerCase();
      if (!ct.includes('application/json')) {
        return r.text().then(function (t) {
          throw new Error(t.slice(0, 240) || 'Server returned non-JSON.');
        });
      }
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

  /** Cash Book account CRUD (JSON API) */
  var AccountsCrud = {
    listPaged: function (page, q, typeF, statusF, perPage) {
      return get('accounts', {
        cb_page: page,
        per_page: perPage || 12,
        q: q || '',
        type: typeF || '',
        status: statusF || '',
      });
    },
    listForOps: function () {
      return get('accounts', { for_ops: '1' });
    },
    get: function (id) {
      return get('account_get', { id: id });
    },
    save: function (fields) {
      return postCashbook('account_save', fields);
    },
    remove: function (id) {
      return postCashbook('account_delete', { id: String(id) });
    },
  };

  function isCustomerLinkedAcc(acc) {
    return (
      acc &&
      acc.customer_id != null &&
      acc.customer_id !== '' &&
      String(acc.customer_id) !== '0'
    );
  }

  function isSystemAccount(acc) {
    return acc && parseInt(acc.is_system, 10) === 1;
  }

  function applyMgmtFormState(acc) {
    var note = $('cbMgmtCustomerNote');
    var sysNote = $('cbMgmtSystemNote');
    var cat = $('cbMgmtCategory');
    var sub = $('cbMgmtMainSubtype');
    var cust = $('cbMgmtCustomerId');
    var delBtn = $('cbMgmtDeleteBtn');
    var mainWrap = $('cbMgmtMainSubtypeWrap');
    var custWrap = $('cbMgmtCustomerWrap');
    var branchEl = $('cbMgmtBranch');
    var isNew = !acc || !acc.id;
    var linked = isCustomerLinkedAcc(acc);
    var sys = isSystemAccount(acc);
    if (note) note.classList.toggle('d-none', !linked);
    if (sysNote) sysNote.classList.toggle('d-none', !sys);
    if (cat) cat.disabled = !!linked || sys;
    if (sub) sub.disabled = !!linked || sys;
    if (cust) {
      cust.disabled = !!linked;
      cust.classList.remove('is-invalid');
    }
    if (mainWrap) mainWrap.classList.toggle('d-none', cat && cat.value === 'customer');
    if (custWrap) custWrap.classList.toggle('d-none', !cat || cat.value !== 'customer');
    if (branchEl) branchEl.disabled = (cat && cat.value === 'customer') || false;
    if (delBtn) {
      delBtn.disabled = isNew || linked || sys;
      delBtn.title = sys
        ? 'System accounts cannot be deleted'
        : linked
          ? 'Customer-linked accounts cannot be deleted here'
          : '';
    }
    syncMgmtSaveButtonAppearance(isNew);
  }

  function syncMgmtSaveButtonAppearance(isNew) {
    var btn = $('cbMgmtSaveBtn');
    if (!btn) return;
    var label = btn.querySelector('.cb-mgmt-save-label');
    btn.classList.remove('btn-primary', 'btn-success');
    if (isNew) {
      btn.classList.add('btn-primary');
      if (label) label.innerHTML = '<i class="bi bi-check2 me-1"></i>Save';
    } else {
      btn.classList.add('btn-success');
      if (label) label.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Update';
    }
  }

  function setMgmtFormError(msg) {
    var el = $('cbMgmtFormError');
    if (!el) return;
    if (!msg) {
      el.classList.add('d-none');
      el.textContent = '';
      return;
    }
    el.textContent = msg;
    el.classList.remove('d-none');
  }

  function clearMgmtFieldErrors() {
    setMgmtFormError('');
    var n = $('cbMgmtName');
    var c = $('cbMgmtCustomerId');
    if (n) n.classList.remove('is-invalid');
    if (c) c.classList.remove('is-invalid');
  }

  function setMgmtMobileView(mode) {
    var sp = $('cbMgmtSplit');
    if (!sp) return;
    sp.classList.toggle('cb-mgmt-mobile-list', mode === 'list');
    sp.classList.toggle('cb-mgmt-mobile-form', mode === 'form');
    var bl = $('cbMgmtMobileListBtn');
    var bf = $('cbMgmtMobileFormBtn');
    if (bl) {
      bl.classList.toggle('active', mode === 'list');
    }
    if (bf) {
      bf.classList.toggle('active', mode === 'form');
    }
  }

  function setMgmtSaveLoading(on) {
    var btn = $('cbMgmtSaveBtn');
    if (!btn) return;
    var spin = btn.querySelector('.cb-mgmt-save-spin');
    var lab = btn.querySelector('.cb-mgmt-save-label');
    btn.disabled = !!on;
    if (spin) spin.classList.toggle('d-none', !on);
    if (lab) lab.classList.toggle('opacity-50', !!on);
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
      var t = new bootstrap.Toast(el, { delay: 2800 });
      t.show();
      el.addEventListener('hidden.bs.toast', function () {
        try {
          el.remove();
        } catch (e) {}
      });
    }
  }

  function setLoading(on) {
    var el = $('cbLoading');
    if (!el) return;
    el.classList.toggle('d-none', !on);
  }

  function setAccountSelectOptions() {
    var sel = $('cbAccountSelect');
    if (!sel) return;
    sel.innerHTML = '';
    state.accounts.forEach(function (a) {
      var o = document.createElement('option');
      o.value = String(a.id);
      o.textContent = accountLabel(a);
      sel.appendChild(o);
    });
    if (state.accountId && state.accounts.some(function (a) { return String(a.id) === String(state.accountId); })) {
      sel.value = String(state.accountId);
    } else if (state.accounts[0]) {
      state.accountId = parseInt(state.accounts[0].id, 10);
      sel.value = String(state.accountId);
    }
  }

  function selectedAccountName() {
    var a = state.accounts.find(function (x) { return String(x.id) === String(state.accountId); });
    return a ? a.name : '—';
  }

  function loadAccounts() {
    return AccountsCrud.listForOps().then(function (j) {
      if (!j.ok) throw new Error(j.error || 'Load failed');
      state.accounts = j.accounts || [];
      setAccountSelectOptions();
      return state.accounts;
    });
  }

  function refreshAfterAccountMutation() {
    var prev = state.accountId;
    setLoading(true);
    return loadAccounts()
      .then(function () {
        fillTransferSelects();
        if (prev && !state.accounts.some(function (a) { return String(a.id) === String(prev); })) {
          state.accountId = state.accounts[0] ? parseInt(state.accounts[0].id, 10) : 0;
          var sel = $('cbAccountSelect');
          if (sel && state.accountId) sel.value = String(state.accountId);
        }
        return Promise.all([loadTotals(), loadEntries()]);
      })
      .catch(function (e) {
        toast(String(e.message || e), true);
      })
      .finally(function () {
        setLoading(false);
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
    var cI = $('cbCardIncome');
    var cE = $('cbCardExpense');
    var cB = $('cbCardBalance');
    if (!state.totals) return;
    var inc = money(state.totals.income || 0);
    var exp = money(state.totals.expense || 0);
    var bal = money(state.totals.balance || 0);
    if (el) el.textContent = inc;
    if (elE) elE.textContent = exp;
    if (elB) elB.textContent = bal;
    if (cI) cI.textContent = inc;
    if (cE) cE.textContent = exp;
    if (cB) cB.textContent = bal;
    if (elR) {
      elR.textContent =
        state.period === 'all'
          ? 'All time'
          : state.from && state.to
            ? state.from.slice(0, 10) + ' — ' + state.to.slice(0, 10)
            : '';
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

  function typeBadge(row) {
    var k = row.kind;
    if (k === 'income') return '<span class="badge rounded-pill text-bg-success">Income</span>';
    if (k === 'expense') return '<span class="badge rounded-pill text-bg-danger">Expense</span>';
    if (k === 'transfer_out' || k === 'transfer_in') return '<span class="badge rounded-pill text-bg-secondary">Transfer</span>';
    return '';
  }

  function renderEntries() {
    var host = $('cbEntryList');
    var tbody = $('cbEntryTableBody');
    var accName = escapeHtml(selectedAccountName());
    var emptyMsg =
      '<div class="text-center text-muted py-5 px-3"><i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i><div class="fw-semibold">No records found</div><div class="small mt-1">Try another period or account.</div></div>';

    if (!state.entries.length) {
      if (host) host.innerHTML = emptyMsg;
      if (tbody) {
        tbody.innerHTML =
          '<tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>No records found for this period.</td></tr>';
      }
      return;
    }

    var rowsHtml = '';
    state.entries.forEach(function (row) {
      var dt = (row.occurred_at || '').replace('T', ' ').slice(0, 16);
      var notes = (row.notes || '').trim();
      var parcel = row.parcel_id ? ' <span class="badge text-bg-light border">#' + row.parcel_id + '</span>' : '';
      var base = (cfg.baseUrl || '').replace(/\/?$/, '/');
      var att = row.attachment_path
        ? ' <a class="small" href="' +
          encodeURI(base + row.attachment_path.replace(/^\//, '')) +
          '" target="_blank" rel="noopener">File</a>'
        : '';
      var idStr = String(row.id);
      var tid = row.transfer_id != null && row.transfer_id !== '' ? String(row.transfer_id) : '';
      var canEdit = /^\d+$/.test(idStr);
      var editBtn = canEdit
        ? '<button type="button" class="btn btn-sm btn-outline-primary rounded-pill cb-edit-txn" data-id="' +
          idStr +
          '"><i class="bi bi-pencil"></i></button>'
        : '';
      var delBtn =
        '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill cb-del-entry" data-id="' +
        idStr +
        '" data-transfer="' +
        tid +
        '"><i class="bi bi-trash"></i></button>';
      var runBal =
        row.running_balance != null && row.running_balance !== ''
          ? '<span class="fw-semibold text-primary">' + money(row.running_balance) + '</span>'
          : '<span class="text-muted">—</span>';

      rowsHtml +=
        '<tr class="cashbook-table-row">' +
        '<td class="text-nowrap small text-muted">' +
        dt +
        '</td>' +
        '<td class="fw-medium">' +
        accName +
        '</td>' +
        '<td>' +
        typeBadge(row) +
        ' <span class="small">' +
        entryTitle(row) +
        '</span>' +
        parcel +
        '</td>' +
        '<td class="text-end">' +
        entryAmountHtml(row) +
        '</td>' +
        '<td class="text-end">' +
        runBal +
        '</td>' +
        '<td class="text-end text-nowrap">' +
        editBtn +
        ' ' +
        delBtn +
        '</td>' +
        '</tr>';

      /* mobile cards */
    });

    if (tbody) tbody.innerHTML = rowsHtml;

    if (host) {
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
          ? '<button type="button" class="btn btn-sm btn-outline-primary rounded-pill cb-edit-txn" data-id="' +
            idStr +
            '">Edit</button>'
          : '';
        var delBtn =
          '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill cb-del-entry" data-id="' +
          idStr +
          '" data-transfer="' +
          tid +
          '">Delete</button>';

        var runM =
          row.running_balance != null && row.running_balance !== ''
            ? '<div class="small text-muted">Balance <span class="fw-semibold text-primary">' + money(row.running_balance) + '</span></div>'
            : '';
        html +=
          '<div class="card cashbook-entry-card mb-2">' +
          '<div class="card-body py-2 px-3 d-flex flex-wrap align-items-start justify-content-between gap-2">' +
          '<div class="min-w-0">' +
          '<div class="small text-muted">' +
          dt +
          '</div>' +
          '<div class="fw-semibold">' +
          typeBadge(row) +
          ' ' +
          entryTitle(row) +
          parcel +
          '</div>' +
          (notes ? '<div class="small text-secondary mt-1">' + escapeHtml(notes) + '</div>' : '') +
          runM +
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
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function refresh() {
    setLoading(true);
    return loadAccounts()
      .then(function () {
        return Promise.all([loadTotals(), loadEntries()]);
      })
      .catch(function (e) {
        toast(String(e.message || e), true);
      })
      .finally(function () {
        setLoading(false);
      });
  }

  /** After any ledger change: refresh main view; if Accounts panel is open, refresh list + dashboard totals. */
  function refreshAfterLedgerChange(opts) {
    opts = opts || {};
    var chain = refresh();
    if (state.panel === 'accounts') {
      chain = chain.then(function () {
        return loadMgmtAccountsList().then(function () {
          if (opts.flashBalance) {
            flashMgmtBalances();
          }
        });
      });
    }
    return chain;
  }

  function applyTxnLinkedAccount(accId, customerName, quiet) {
    function trySet() {
      var sel = $('cbAccountSelect');
      if (!sel || !accId) return false;
      if (!state.accounts.some(function (a) { return String(a.id) === String(accId); })) {
        return false;
      }
      state.accountId = parseInt(accId, 10);
      sel.value = String(accId);
      if (!quiet) {
        toast(
          'Ledger switched to ' +
            (customerName ? "'" + customerName + "' — " : '') +
            'linked customer account.'
        );
      }
      loadTotals();
      loadEntries();
      return true;
    }
    if (trySet()) return Promise.resolve();
    return loadAccounts()
      .then(function () {
        fillTransferSelects();
        if (trySet()) return;
        if (!quiet) toast('Cash Book account could not be selected.', true);
      })
      .catch(function (e) {
        if (!quiet) toast(String(e.message || e), true);
      });
  }

  function syncTxnAccountFromParcelField(quiet) {
    var el = $('cbTxnParcel');
    if (!el) return Promise.resolve();
    var raw = (el.value || '').trim();
    if (!/^\d+$/.test(raw)) return Promise.resolve();
    var pid = parseInt(raw, 10);
    if (pid <= 0) return Promise.resolve();
    return get('parcel_customer_account', { parcel_id: pid }).then(function (j) {
      if (!j.ok) {
        if (!quiet) toast(j.error || 'Could not load parcel', true);
        return;
      }
      if (!j.customer_id) {
        if (!quiet) toast('This parcel has no customer linked.', true);
        return;
      }
      if (!j.cashbook_account_id) {
        if (!quiet) {
          toast(
            'No Cash Book account for this customer yet. Use Link customers or add the customer from Cash Book.',
            true
          );
        }
        return;
      }
      return applyTxnLinkedAccount(j.cashbook_account_id, j.customer_name, quiet);
    });
  }

  function openTxnModalForAccount(accountId, mode) {
    openTxnModal(mode, null, accountId);
  }

  function openTxnModal(mode, txn, accountIdOverride) {
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
    var aidRaw = accountIdOverride != null && accountIdOverride !== '' ? accountIdOverride : null;
    var txnAcc = txn && txn.account_id != null ? txn.account_id : null;
    var targetAcc = aidRaw != null ? aidRaw : txnAcc;
    var accEl = $('cbTxnAccountId');
    var hint = $('cbTxnAccountHint');
    var hintT = $('cbTxnAccountHintText');
    if (accEl) {
      if (targetAcc != null && String(targetAcc) !== '') {
        accEl.value = String(targetAcc);
        var aid = parseInt(String(targetAcc), 10) || 0;
        var accObj = state.accounts.find(function (x) {
          return String(x.id) === String(aid);
        });
        if (hint && hintT) {
          hintT.textContent = accObj ? 'Posting to: ' + accObj.name : 'Posting to account #' + aid;
          hint.classList.remove('d-none');
        }
        if ($('cbAccountSelect') && aid > 0) {
          $('cbAccountSelect').value = String(aid);
        }
        state.accountId = aid;
      } else {
        accEl.value = '';
        if (hint) hint.classList.add('d-none');
      }
    }
    var m = bootstrap.Modal.getOrCreateInstance(modalEl);
    m.show();
  }

  function saveTxn(continueFlag) {
    var fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('cb_action', 'transaction_save');
    fd.append('id', $('cbTxnId').value || '0');
    var accOverride = $('cbTxnAccountId') && $('cbTxnAccountId').value ? parseInt($('cbTxnAccountId').value, 10) : 0;
    fd.append('account_id', String(accOverride > 0 ? accOverride : state.accountId));
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
        if ($('cbTxnAccountId')) $('cbTxnAccountId').value = '';
        if ($('cbTxnAccountHint')) $('cbTxnAccountHint').classList.add('d-none');
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
        return refreshAfterLedgerChange({ flashBalance: true });
      })
      .catch(function (e) {
        toast(String(e.message || e), true);
      });
  }

  function deleteEntry(idStr, transferId) {
    if (!window.confirm('Delete this entry?')) return;
    if (transferId && String(transferId).trim() !== '') {
      postCashbook('transfer_delete', { transfer_id: transferId })
        .then(function (j) {
          if (!j.ok) throw new Error(j.error || 'Delete failed');
          toast('Deleted');
          return refreshAfterLedgerChange({ flashBalance: false });
        })
        .catch(function (e) {
          toast(String(e.message || e), true);
        });
      return;
    }
    postCashbook('transaction_delete', { id: idStr })
      .then(function (j) {
        if (!j.ok) throw new Error(j.error || 'Delete failed');
        toast('Deleted');
        return refreshAfterLedgerChange({ flashBalance: false });
      })
      .catch(function (e) {
        toast(String(e.message || e), true);
      });
  }

  function typeLabel(t) {
    var map = { cash: 'Cash', bank: 'Bank', branch: 'Branch', customer: 'Customer' };
    return map[t] || t;
  }

  /** Main + kind; Customer = primary (blue) */
  function accountLedgerTypeBadge(t, kind) {
    return accountTypeBadges(t, kind);
  }

  function accountTypeBadges(t, kind) {
    if (t === 'customer') {
      var showRecv = kind === 'receivable' || kind === '' || kind == null;
      var recv = showRecv
        ? '<span class="badge rounded-pill bg-info-subtle text-primary border ms-1">Receivable</span>'
        : '';
      return '<span class="badge rounded-pill text-bg-primary">Customer account</span>' + recv;
    }
    if (t === 'cash' || t === 'bank' || t === 'branch') {
      var sub = { cash: 'Cash', bank: 'Bank', branch: 'Digital' }[t] || typeLabel(t);
      return (
        '<span class="badge rounded-pill text-bg-success me-1">Main</span>' +
        '<span class="badge rounded-pill bg-light text-dark border">' +
        escapeHtml(sub) +
        '</span>'
      );
    }
    return '<span class="badge rounded-pill text-bg-secondary">' + escapeHtml(typeLabel(t)) + '</span>';
  }

  function balanceHighlightClass(bal) {
    var n = Number(bal) || 0;
    return n < 0 ? 'cb-mgmt-balance-neg' : '';
  }

  function accountTypeBadgeHTML(t) {
    var type = (t || '').toString();
    if (type === 'customer') {
      return '<span class="badge rounded-pill cb-badge-type-customer">Customer</span>';
    }
    if (type === 'bank') {
      return '<span class="badge rounded-pill cb-badge-type-bank">Bank</span>';
    }
    // cash + branch = Main
    return '<span class="badge rounded-pill cb-badge-type-main">Main</span>';
  }

  function accountKindBadgeHTML(kind) {
    var k = (kind || '').toString();
    var map = { cash: 'Cash', bank: 'Bank', digital: 'Digital', receivable: 'Receivable' };
    var label = map[k] || (k ? String(k) : '—');
    return '<span class="badge rounded-pill text-bg-light border">' + escapeHtml(label) + '</span>';
  }

  function accountStatusBadgeHTML(status) {
    var s = (status || 'active') === 'inactive' ? 'inactive' : 'active';
    var label = s === 'inactive' ? 'Inactive' : 'Active';
    var cls = s === 'inactive' ? 'text-bg-secondary' : 'text-bg-success';
    return '<span class="badge rounded-pill ' + cls + '">' + label + '</span>';
  }

  function accountBalanceHTML(balance) {
    var b = Number(balance);
    if (isNaN(b)) b = 0;
    var cls = b < 0 ? 'text-danger' : 'text-success';
    return '<span class="fw-bold ' + cls + '">' + money(b) + '</span>';
  }

  function shouldUseMgmtFormModal() {
    return typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 991.98px)').matches;
  }

  function mountMgmtFormToModal(show) {
    var card = $('cbMgmtFormCard');
    var ph = $('cbMgmtFormPlaceholder');
    var mb = $('cbMgmtFormModalBody');
    if (!card || !ph || !mb) return;
    if (show) {
      if (card.parentElement !== mb) mb.appendChild(card);
    } else if (card.parentElement !== ph) {
      ph.appendChild(card);
    }
  }

  function openMgmtFormModal() {
    mountMgmtFormToModal(true);
    var el = $('cbMgmtFormModal');
    if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      bootstrap.Modal.getOrCreateInstance(el).show();
      setTimeout(function () {
        if ($('cbMgmtName')) $('cbMgmtName').focus();
      }, 350);
    }
  }

  function closeMgmtFormModalIfAny() {
    var el = $('cbMgmtFormModal');
    if (!el || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
    var inst = bootstrap.Modal.getInstance(el);
    if (inst) inst.hide();
  }

  function openMgmtAccountEditor(id) {
    AccountsCrud.get(id)
      .then(function (j) {
        if (!j.ok || !j.account) throw new Error(j.error || 'Load failed');
        fillMgmtFormFromAccount(j.account);
        setMgmtMobileView('form');
        if (shouldUseMgmtFormModal()) {
          openMgmtFormModal();
        } else if ($('cbMgmtName')) {
          $('cbMgmtName').focus();
        }
      })
      .catch(function (e) {
        toast(String(e.message || e), true);
      });
  }

  function startNewAccountUi() {
    clearMgmtForm();
    setMgmtMobileView('form');
    if (shouldUseMgmtFormModal()) {
      openMgmtFormModal();
    } else if ($('cbMgmtName')) {
      $('cbMgmtName').focus();
    }
  }

  function flashMgmtBalances() {
    document.querySelectorAll('.cb-mgmt-balance-cell, .cb-mgmt-dash-value').forEach(function (el) {
      el.classList.add('cb-mgmt-value-flash');
      setTimeout(function () {
        el.classList.remove('cb-mgmt-value-flash');
      }, 700);
    });
  }

  function refreshMgmtTotals() {
    return get('mgmt_dashboard', { anchor: state.anchor })
      .then(function (j) {
        if (!j.ok) return;
        var sum = Number(j.total_balance) || 0;
        var el = $('cbMgmtTotalBalance');
        if (el) {
          el.textContent = money(sum);
          el.classList.toggle('text-danger', sum < 0);
          el.classList.toggle('text-body', sum >= 0);
        }
        var inc = $('cbMgmtDashIncome');
        var exp = $('cbMgmtDashExpense');
        if (inc) inc.textContent = money(j.period_income || 0);
        if (exp) exp.textContent = money(j.period_expense || 0);
        var ym = (state.anchor || '').slice(0, 7);
        var hi = $('cbMgmtDashIncomeHint');
        var he = $('cbMgmtDashExpenseHint');
        if (hi && ym) hi.textContent = 'Calendar month ' + ym;
        if (he && ym) he.textContent = 'Calendar month ' + ym;
      })
      .catch(function () {});
  }

  function openTransferFromAccount(fromId) {
    setLoading(true);
    loadAccounts()
      .then(function () {
        fillTransferSelects();
        var from = $('cbTrFrom');
        var to = $('cbTrTo');
        if (from) from.value = String(fromId);
        if (to && to.options && to.options.length) {
          var pick = '';
          for (var i = 0; i < to.options.length; i++) {
            if (to.options[i].value && to.options[i].value !== String(fromId)) {
              pick = to.options[i].value;
              break;
            }
          }
          to.value = pick;
        }
        if ($('cbTrDate') && (!$('cbTrDate').value || $('cbTrDate').value === '')) {
          $('cbTrDate').value = state.anchor;
        }
        if ($('cbTrTime') && (!$('cbTrTime').value || $('cbTrTime').value === '')) {
          $('cbTrTime').value = new Date().toTimeString().slice(0, 5);
        }
        var mel = $('cashbookTransferModal');
        if (mel && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          bootstrap.Modal.getOrCreateInstance(mel).show();
        }
      })
      .catch(function (e) {
        toast(String(e.message || e), true);
      })
      .finally(function () {
        setLoading(false);
      });
  }

  function highlightMgmtRows(selectedId) {
    document.querySelectorAll('.cashbook-mgmt-table tbody tr.cb-mgmt-row').forEach(function (tr) {
      var rid = parseInt(tr.getAttribute('data-id'), 10);
      tr.classList.toggle('table-active', selectedId && rid === parseInt(selectedId, 10));
    });
    document.querySelectorAll('.cb-mgmt-acc-card').forEach(function (card) {
      var rid = parseInt(card.getAttribute('data-id'), 10);
      card.classList.toggle('cb-mgmt-acc-card-active', selectedId && rid === parseInt(selectedId, 10));
    });
  }

  function fillMgmtFormFromAccount(acc) {
    if (!$('cbMgmtId')) return;
    state.mgmtLoadedAccount = acc || null;
    clearMgmtFieldErrors();
    $('cbMgmtId').value = acc && acc.id ? String(acc.id) : '';
    $('cbMgmtName').value = acc && acc.name ? acc.name : '';
    var t = acc && acc.type ? acc.type : 'cash';
    if (t === 'customer') {
      if ($('cbMgmtCategory')) $('cbMgmtCategory').value = 'customer';
      if ($('cbMgmtCustomerId') && acc.customer_id) $('cbMgmtCustomerId').value = String(acc.customer_id);
    } else {
      if ($('cbMgmtCategory')) $('cbMgmtCategory').value = 'main';
      if ($('cbMgmtMainSubtype')) $('cbMgmtMainSubtype').value = t;
      if ($('cbMgmtCustomerId')) $('cbMgmtCustomerId').value = '';
    }
    if ($('cbMgmtStatus')) $('cbMgmtStatus').checked = (acc && acc.status) !== 'inactive';
    if ($('cbMgmtBranch')) $('cbMgmtBranch').value = acc && acc.branch_id != null ? String(acc.branch_id) : '';
    if ($('cbMgmtBalance')) $('cbMgmtBalance').value = money(acc && acc.balance != null ? acc.balance : 0);
    state.mgmtSelectedId = acc && acc.id ? parseInt(acc.id, 10) : null;
    applyMgmtFormState(acc);
    highlightMgmtRows(acc && acc.id ? acc.id : null);
  }

  function clearMgmtForm() {
    state.mgmtLoadedAccount = null;
    fillMgmtFormFromAccount(null);
    $('cbMgmtId').value = '';
    $('cbMgmtName').value = '';
    if ($('cbMgmtCategory')) $('cbMgmtCategory').value = 'main';
    if ($('cbMgmtMainSubtype')) $('cbMgmtMainSubtype').value = 'cash';
    if ($('cbMgmtCustomerId')) $('cbMgmtCustomerId').value = '';
    if ($('cbMgmtStatus')) $('cbMgmtStatus').checked = true;
    if ($('cbMgmtBranch')) $('cbMgmtBranch').value = '';
    if ($('cbMgmtBalance')) $('cbMgmtBalance').value = money(0);
    state.mgmtSelectedId = null;
    clearMgmtFieldErrors();
    applyMgmtFormState(null);
    highlightMgmtRows(null);
  }

  function collectMgmtPayload() {
    var id = parseInt($('cbMgmtId').value, 10) || 0;
    var name = ($('cbMgmtName').value || '').trim();
    var cat = ($('cbMgmtCategory') && $('cbMgmtCategory').value) || 'main';
    var type = 'cash';
    if (cat === 'customer') {
      type = 'customer';
    } else {
      type = ($('cbMgmtMainSubtype') && $('cbMgmtMainSubtype').value) || 'cash';
    }
    var status = $('cbMgmtStatus') && $('cbMgmtStatus').checked ? 'active' : 'inactive';
    var branchRaw = $('cbMgmtBranch') ? $('cbMgmtBranch').value.trim() : '';
    var branchId = branchRaw !== '' ? branchRaw : '';
    var customerId = '';
    if (cat === 'customer' && id === 0) {
      customerId = ($('cbMgmtCustomerId') && $('cbMgmtCustomerId').value) || '';
    }
    return { id: id, name: name, type: type, status: status, branch_id: branchId, customer_id: customerId };
  }

  function validateMgmtForm(payload) {
    clearMgmtFieldErrors();
    var ok = true;
    if (!payload.name) {
      var n = $('cbMgmtName');
      if (n) n.classList.add('is-invalid');
      setMgmtFormError('Account name is required.');
      ok = false;
    }
    if (payload.id === 0 && payload.type === 'customer') {
      var cid = parseInt(payload.customer_id, 10) || 0;
      if (cid <= 0) {
        var c = $('cbMgmtCustomerId');
        if (c) c.classList.add('is-invalid');
        setMgmtFormError('Select a customer for customer accounts.');
        ok = false;
      }
    }
    return ok;
  }

  function renderMgmtCards(accounts) {
    var host = $('cbMgmtCards');
    var empty = $('cbMgmtEmpty');
    var wrap = document.querySelector('.cb-mgmt-table-wrap');
    if (!host) return;
    if (!accounts.length) {
      host.innerHTML = '';
      if (empty) empty.classList.remove('d-none');
      if (wrap) wrap.classList.add('d-none');
      return;
    }
    if (empty) empty.classList.add('d-none');
    if (wrap) wrap.classList.remove('d-none');
    var html = '';
    accounts.forEach(function (a) {
      var canDel = !isCustomerLinkedAcc(a) && !isSystemAccount(a);
      var sel = state.mgmtSelectedId && parseInt(a.id, 10) === state.mgmtSelectedId;
      var customerHtml = a.customer_name ? escapeHtml(String(a.customer_name)) : '<span class="text-muted">—</span>';
      var typeBadge = accountTypeBadgeHTML(a.type);
      var kindBadge = accountKindBadgeHTML(a.account_kind);
      var statusBadge = accountStatusBadgeHTML(a.status);
      var delBtn = canDel
        ? '<button type="button" class="dropdown-item cb-mgmt-act-del" data-id="' +
          a.id +
          '"><i class="bi bi-trash me-2"></i>Delete</button>'
        : '<button type="button" class="dropdown-item cb-mgmt-act-del" data-id="' +
          a.id +
          '" disabled><i class="bi bi-trash me-2"></i>Delete</button>';

      var actionsDropdown =
        '<div class="dropdown d-inline cb-mgmt-row-actions">' +
        '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false" title="Actions"><i class="bi bi-three-dots-vertical"></i></button>' +
        '<ul class="dropdown-menu dropdown-menu-end">' +
        '<li><button type="button" class="dropdown-item cb-mgmt-act-edit" data-id="' +
        a.id +
        '"><i class="bi bi-pencil-square me-2"></i>Edit</button></li>' +
        '<li>' +
        delBtn +
        '</li>' +
        '<li><hr class="dropdown-divider"></li>' +
        '<li><button type="button" class="dropdown-item cb-mgmt-act-inc" data-id="' +
        a.id +
        '"><i class="bi bi-plus-circle me-2 text-success"></i>Add Income</button></li>' +
        '<li><button type="button" class="dropdown-item cb-mgmt-act-exp" data-id="' +
        a.id +
        '"><i class="bi bi-dash-circle me-2 text-danger"></i>Add Expense</button></li>' +
        '<li><button type="button" class="dropdown-item cb-mgmt-act-xfer" data-id="' +
        a.id +
        '"><i class="bi bi-shuffle me-2"></i>Transfer</button></li>' +
        '</ul>' +
        '</div>';

      html +=
        '<div class="card border-0 shadow-sm rounded-4 cb-mgmt-acc-card' +
        (sel ? ' cb-mgmt-acc-card-active' : '') +
        '" data-id="' +
        a.id +
        '" role="button" tabindex="0">' +
        '<div class="card-body py-3">' +
        '<div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
        '<div class="min-w-0">' +
        '<div class="fw-bold text-truncate" title="' +
        escapeHtml(String(a.name || '')) +
        '">' +
        escapeHtml(String(a.name || '')) +
        '</div>' +
        '<div class="small text-muted mt-1">' +
        customerHtml +
        '</div>' +
        '<div class="d-flex flex-wrap gap-2 mt-2">' +
        typeBadge +
        kindBadge +
        '</div>' +
        '</div>' +
        '<div class="text-end">' +
        statusBadge +
        '</div>' +
        '</div>' +
        '<div class="d-flex justify-content-between align-items-end gap-2">' +
        '<div class="fs-5 font-monospace cb-mgmt-balance-cell">' +
        accountBalanceHTML(a.balance) +
        '</div>' +
        actionsDropdown +
        '</div>' +
        '</div>' +
        '</div>';
    });
    host.innerHTML = html;
    wireMgmtListInteractions(host);
  }

  function renderMgmtTable(accounts) {
    var tb = $('cbMgmtTableBody');
    var empty = $('cbMgmtEmpty');
    var wrap = document.querySelector('.cb-mgmt-table-wrap');
    if (!tb) return;
    if (!accounts.length) {
      tb.innerHTML = '';
      renderMgmtCards([]);
      return;
    }
    if (empty) empty.classList.add('d-none');
    if (wrap) wrap.classList.remove('d-none');
    var html = '';
    accounts.forEach(function (a, idx) {
      var canDel = !isCustomerLinkedAcc(a) && !isSystemAccount(a);
      var sel = state.mgmtSelectedId && parseInt(a.id, 10) === state.mgmtSelectedId;
      var customerHtml = a.customer_name
        ? '<span class="text-body"><span class="cb-mgmt-cell-truncate" title="' +
          escapeHtml(String(a.customer_name)) +
          '">' +
          escapeHtml(String(a.customer_name)) +
          '</span></span>'
        : '<span class="text-muted">—</span>';
      var typeBadge = accountTypeBadgeHTML(a.type);
      var kindBadge = accountKindBadgeHTML(a.account_kind);
      var statusBadge = accountStatusBadgeHTML(a.status);
      var delBtn = canDel
        ? '<button type="button" class="dropdown-item cb-mgmt-act-del" data-id="' +
          a.id +
          '"><i class="bi bi-trash me-2"></i>Delete</button>'
        : '<button type="button" class="dropdown-item cb-mgmt-act-del" data-id="' +
          a.id +
          '" disabled><i class="bi bi-trash me-2"></i>Delete</button>';

      var actionsDropdown =
        '<div class="dropdown d-inline cb-mgmt-row-actions">' +
        '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false" title="Actions"><i class="bi bi-three-dots-vertical"></i></button>' +
        '<ul class="dropdown-menu dropdown-menu-end">' +
        '<li><button type="button" class="dropdown-item cb-mgmt-act-edit" data-id="' +
        a.id +
        '"><i class="bi bi-pencil-square me-2"></i>Edit</button></li>' +
        '<li>' +
        delBtn +
        '</li>' +
        '<li><hr class="dropdown-divider"></li>' +
        '<li><button type="button" class="dropdown-item cb-mgmt-act-inc" data-id="' +
        a.id +
        '"><i class="bi bi-plus-circle me-2 text-success"></i>Add Income</button></li>' +
        '<li><button type="button" class="dropdown-item cb-mgmt-act-exp" data-id="' +
        a.id +
        '"><i class="bi bi-dash-circle me-2 text-danger"></i>Add Expense</button></li>' +
        '<li><button type="button" class="dropdown-item cb-mgmt-act-xfer" data-id="' +
        a.id +
        '"><i class="bi bi-shuffle me-2"></i>Transfer</button></li>' +
        '</ul>' +
        '</div>';

      html +=
        '<tr class="cb-mgmt-row' +
        (sel ? ' table-active' : '') +
        '" data-id="' +
        a.id +
        '" role="button" tabindex="0">' +
        '<td class="text-muted col-num">' +
        (idx + 1) +
        '</td>' +
        '<td class="fw-semibold"><span class="cb-mgmt-cell-truncate" title="' +
        escapeHtml(String(a.name || '')) +
        '">' +
        escapeHtml(String(a.name || '')) +
        '</span></td>' +
        '<td class="small">' +
        customerHtml +
        '</td>' +
        '<td>' +
        typeBadge +
        '</td>' +
        '<td>' +
        kindBadge +
        '</td>' +
        '<td class="text-end font-monospace cb-mgmt-balance-cell">' +
        accountBalanceHTML(a.balance) +
        '</td>' +
        '<td>' +
        statusBadge +
        '</td>' +
        '<td class="text-end">' +
        actionsDropdown +
        '</td></tr>';
    });
    tb.innerHTML = html;
    wireMgmtListInteractions(tb);
    renderMgmtCards(accounts);
  }

  function wireMgmtListInteractions(root) {
    if (!root) return;
    root.querySelectorAll('.cb-mgmt-row').forEach(function (tr) {
      tr.addEventListener('click', function (ev) {
        if (ev.target.closest('.cb-mgmt-row-actions')) {
          return;
        }
        var id = parseInt(tr.getAttribute('data-id'), 10);
        if (id) openMgmtAccountEditor(id);
      });
      tr.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') {
          ev.preventDefault();
          var id = parseInt(tr.getAttribute('data-id'), 10);
          if (id) openMgmtAccountEditor(id);
        }
      });
    });
    root.querySelectorAll('.cb-mgmt-acc-card').forEach(function (card) {
      card.addEventListener('click', function (ev) {
        if (ev.target.closest('.cb-mgmt-row-actions')) {
          return;
        }
        var id = parseInt(card.getAttribute('data-id'), 10);
        if (id) openMgmtAccountEditor(id);
      });
      card.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') {
          ev.preventDefault();
          var id = parseInt(card.getAttribute('data-id'), 10);
          if (id) openMgmtAccountEditor(id);
        }
      });
    });

    root.querySelectorAll('.cb-mgmt-act-edit').forEach(function (btn) {
      btn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        var id = parseInt(btn.getAttribute('data-id'), 10);
        if (id) openMgmtAccountEditor(id);
      });
    });
    root.querySelectorAll('.cb-mgmt-act-xfer').forEach(function (btn) {
      btn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        var id = parseInt(btn.getAttribute('data-id'), 10);
        if (id) openTransferFromAccount(id);
      });
    });
    root.querySelectorAll('.cb-mgmt-act-inc').forEach(function (btn) {
      btn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        var id = parseInt(btn.getAttribute('data-id'), 10);
        if (id) openTxnModalForAccount(id, 'income');
      });
    });
    root.querySelectorAll('.cb-mgmt-act-exp').forEach(function (btn) {
      btn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        var id = parseInt(btn.getAttribute('data-id'), 10);
        if (id) openTxnModalForAccount(id, 'expense');
      });
    });
    root.querySelectorAll('.cb-mgmt-act-del').forEach(function (btn) {
      btn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        if (btn.disabled) return;
        var id = parseInt(btn.getAttribute('data-id'), 10);
        if (!id || !window.confirm('Delete this account? This cannot be undone.')) return;
        AccountsCrud.remove(id)
          .then(function (j) {
            if (!j.ok) throw new Error(j.error || 'Delete failed');
            toast('Account deleted');
            clearMgmtForm();
            closeMgmtFormModalIfAny();
            return refreshAfterAccountMutation().then(function () {
              loadMgmtAccountsList();
            });
          })
          .catch(function (e) {
            toast(String(e.message || e), true);
          });
      });
    });
  }

  function applyMgmtFlashHighlight() {
    var fid = state.mgmtFlashAccountId;
    if (!fid) return;
    requestAnimationFrame(function () {
      var el =
        document.querySelector('tr.cb-mgmt-row[data-id="' + fid + '"]') ||
        document.querySelector('.cb-mgmt-acc-card[data-id="' + fid + '"]');
      if (!el) return;
      el.classList.add('cb-mgmt-flash');
      try {
        el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      } catch (e) {}
      setTimeout(function () {
        el.classList.remove('cb-mgmt-flash');
        state.mgmtFlashAccountId = null;
      }, 2400);
    });
  }

  function loadMgmtAccountsList() {
    var loading = $('cbMgmtListLoading');
    if (loading) loading.classList.remove('d-none');
    var empty = $('cbMgmtEmpty');
    var wrap = document.querySelector('.cb-mgmt-table-wrap');
    var q = ($('cbMgmtFilterQ') && $('cbMgmtFilterQ').value.trim()) || '';
    var typeF = ($('cbMgmtFilterType') && $('cbMgmtFilterType').value) || '';
    var statusF = ($('cbMgmtFilterStatus') && $('cbMgmtFilterStatus').value) || '';
    var sortEl = $('cbMgmtSort');
    var sort = (sortEl && sortEl.value) || state.mgmtSort || 'default';
    state.mgmtSort = sort;
    // Show "ALL" matching accounts in one table (no pagination).
    // (If you have extremely large datasets, increase per_page or switch to client-side filtering.)
    var per = 10000;
    return get('accounts', {
      cb_page: 1,
      per_page: per,
      q: q,
      type: typeF,
      status: statusF,
      sort: sort,
    })
      .then(function (j) {
        if (!j.ok) throw new Error(j.error || 'Load failed');
        var list = j.accounts || [];
        renderMgmtTable(list);
        var pg = $('cbMgmtPager');
        if (pg) pg.classList.add('d-none');
        applyMgmtFlashHighlight();
        return refreshMgmtTotals();
      })
      .catch(function (e) {
        var msg = String((e && e.message) || e || 'Load failed');
        toast(msg, true);
        // Ensure the user sees a visible error state instead of a blank table.
        try {
          var tb = $('cbMgmtTableBody');
          if (tb) tb.innerHTML = '';
          if (wrap) wrap.classList.add('d-none');
          if (empty) {
            empty.classList.remove('d-none');
            empty.innerHTML =
              '<i class="bi bi-exclamation-triangle fs-1 d-block mb-2 opacity-50"></i>' +
              '<div class="fw-semibold">Could not load accounts</div>' +
              '<div class="small"> ' +
              escapeHtml(msg).slice(0, 220) +
              '</div>';
          }
        } catch (err) {}
      })
      .finally(function () {
        if (loading) loading.classList.add('d-none');
      });
  }

  function renderPagination(ulId, page, perPage, total, onPage) {
    var ul = $(ulId);
    if (!ul) return;
    var pages = Math.max(1, Math.ceil(total / perPage));
    if (pages <= 1) {
      ul.innerHTML = '';
      return;
    }
    var html = '';
    html +=
      '<li class="page-item' +
      (page <= 1 ? ' disabled' : '') +
      '"><a class="page-link rounded-pill" href="#" data-page="' +
      Math.max(1, page - 1) +
      '">Prev</a></li>';
    html +=
      '<li class="page-item disabled"><span class="page-link">' +
      page +
      ' / ' +
      pages +
      '</span></li>';
    html +=
      '<li class="page-item' +
      (page >= pages ? ' disabled' : '') +
      '"><a class="page-link rounded-pill" href="#" data-page="' +
      Math.min(pages, page + 1) +
      '">Next</a></li>';
    ul.innerHTML = html;
    ul.querySelectorAll('a[data-page]').forEach(function (a) {
      a.addEventListener('click', function (ev) {
        ev.preventDefault();
        var np = parseInt(a.getAttribute('data-page'), 10);
        if (!isNaN(np)) onPage(np);
      });
    });
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
        o.textContent = accountLabel(acc);
        sel.appendChild(o);
      });
      if (v) sel.value = v;
    });
  }

  function setNavActive(panel) {
    document.querySelectorAll('.cb-nav-panel').forEach(function (el) {
      var p = el.getAttribute('data-panel');
      el.classList.toggle('active', p === panel);
    });
  }

  function syncCashbookAccountsChrome() {
    var app = document.querySelector('.cashbook-app');
    if (!app) return;
    app.classList.toggle('cashbook-mode-accounts', state.panel === 'accounts');
  }

  function bind() {
    var txnModalEl = $('cashbookTxnModal');
    if (txnModalEl) {
      txnModalEl.addEventListener('hidden.bs.modal', function () {
        if ($('cbTxnAccountId')) $('cbTxnAccountId').value = '';
        if ($('cbTxnAccountHint')) $('cbTxnAccountHint').classList.add('d-none');
      });
    }

    var mgmtModal = $('cbMgmtFormModal');
    if (mgmtModal) {
      mgmtModal.addEventListener('hidden.bs.modal', function () {
        mountMgmtFormToModal(false);
      });
    }

    document.querySelectorAll('.cb-mgmt-mob-income').forEach(function (b) {
      b.addEventListener('click', function () {
        openTxnModal('income', null);
      });
    });
    document.querySelectorAll('.cb-mgmt-mob-expense').forEach(function (b) {
      b.addEventListener('click', function () {
        openTxnModal('expense', null);
      });
    });

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
        if (state.panel === 'accounts') {
          refreshMgmtTotals();
        }
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

    var parcelEl = $('cbTxnParcel');
    if (parcelEl) {
      parcelEl.addEventListener('blur', function () {
        syncTxnAccountFromParcelField(false);
      });
      parcelEl.addEventListener('input', function () {
        clearTimeout(parcelTxnTimer);
        parcelTxnTimer = setTimeout(function () {
          syncTxnAccountFromParcelField(true);
        }, 650);
      });
    }

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
        var fromId = ($('cbTrFrom') && $('cbTrFrom').value) || '';
        var toId = ($('cbTrTo') && $('cbTrTo').value) || '';
        if (fromId && toId && fromId === toId) {
          toast('Choose a different destination account.', true);
          return;
        }
        var preventNeg = $('cbTrPreventNeg') && $('cbTrPreventNeg').checked;
        postCashbook('transfer_save', {
          from_account_id: fromId,
          to_account_id: toId,
          amount: $('cbTrAmount').value,
          occurred_at: $('cbTrDate').value + ' ' + ($('cbTrTime').value || '12:00') + ':00',
          notes: $('cbTrNotes').value,
          prevent_negative: preventNeg ? '1' : '0',
        })
          .then(function (j) {
            if (!j.ok) throw new Error(j.error || 'Failed');
            toast('Transfer saved');
            var m = bootstrap.Modal.getInstance($('cashbookTransferModal'));
            if (m) m.hide();
            return refreshAfterLedgerChange({ flashBalance: false });
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
        setNavActive(state.panel);
        syncCashbookAccountsChrome();
        document.querySelectorAll('[data-cb-panel]').forEach(function (sec) {
          sec.classList.toggle('cashbook-panel-hidden', sec.getAttribute('data-cb-panel') !== state.panel);
        });
        if (state.panel === 'reports') loadReports();
        if (state.panel === 'accounts') {
          state.mgmtPage = 1;
          setMgmtMobileView('list');
          closeMgmtFormModalIfAny();
          mountMgmtFormToModal(false);
          loadMgmtAccountsList();
        }
        try {
          var oc = bootstrap.Offcanvas.getInstance(document.getElementById('cashbookMenu'));
          if (oc) oc.hide();
        } catch (err) {}
      });
    });

    ['cbMgmtFilterQ', 'cbMgmtFilterType', 'cbMgmtFilterStatus', 'cbMgmtSort'].forEach(function (id) {
      var el = $(id);
      if (!el) return;
      el.addEventListener('change', function () {
        state.mgmtPage = 1;
        if (id === 'cbMgmtSort') {
          state.mgmtSort = el.value || 'default';
        }
        loadMgmtAccountsList();
      });
      el.addEventListener('input', function () {
        if (id !== 'cbMgmtFilterQ') return;
        clearTimeout(mgmtFilterTimer);
        mgmtFilterTimer = setTimeout(function () {
          state.mgmtPage = 1;
          loadMgmtAccountsList();
        }, 400);
      });
    });

    $('cbMgmtMobileListBtn') &&
      $('cbMgmtMobileListBtn').addEventListener('click', function () {
        setMgmtMobileView('list');
      });
    $('cbMgmtMobileFormBtn') &&
      $('cbMgmtMobileFormBtn').addEventListener('click', function () {
        setMgmtMobileView('form');
        closeMgmtFormModalIfAny();
        mountMgmtFormToModal(false);
      });

    $('cbMgmtCategory') &&
      $('cbMgmtCategory').addEventListener('change', function () {
        var id = parseInt($('cbMgmtId').value, 10) || 0;
        var custEl = $('cbMgmtCustomerId');
        var accStub = null;
        if (id > 0 && custEl && custEl.disabled) {
          accStub = { id: id, customer_id: custEl.value || 1 };
        } else if (id > 0) {
          accStub = { id: id };
        }
        applyMgmtFormState(accStub);
      });

    $('cbMgmtCustomerId') &&
      $('cbMgmtCustomerId').addEventListener('change', function () {
        var sel = $('cbMgmtCustomerId');
        var id = parseInt($('cbMgmtId').value, 10) || 0;
        if (id > 0 || !sel || sel.disabled) return;
        var opt = sel.options[sel.selectedIndex];
        if (opt && opt.text && ($('cbMgmtName').value || '').trim() === '') {
          $('cbMgmtName').value = opt.text;
        }
      });

    $('cbMgmtNewBtn') &&
      $('cbMgmtNewBtn').addEventListener('click', function () {
        startNewAccountUi();
      });
    $('cbMgmtFab') &&
      $('cbMgmtFab').addEventListener('click', function () {
        startNewAccountUi();
      });

    $('cbMgmtResetBtn') &&
      $('cbMgmtResetBtn').addEventListener('click', function () {
        clearMgmtForm();
      });

    $('cbMgmtSaveBtn') &&
      $('cbMgmtSaveBtn').addEventListener('click', function () {
        var payload = collectMgmtPayload();
        if (!validateMgmtForm(payload)) return;
        setMgmtSaveLoading(true);
        var fields = {
          id: String(payload.id || 0),
          name: payload.name,
          type: payload.type,
          status: payload.status,
          branch_id: payload.branch_id,
        };
        if (payload.id === 0 && payload.type === 'customer') {
          fields.customer_id = String(payload.customer_id);
        }
        AccountsCrud.save(fields)
          .then(function (j) {
            if (!j.ok) throw new Error(j.error || 'Failed');
            toast(payload.id ? 'Account updated' : 'Account saved');
            return refreshAfterAccountMutation().then(function () {
              return loadMgmtAccountsList().then(function () {
                closeMgmtFormModalIfAny();
                if (j.id && !payload.id) {
                  return AccountsCrud.get(j.id).then(function (g) {
                    if (g.ok && g.account) fillMgmtFormFromAccount(g.account);
                  });
                }
              });
            });
          })
          .catch(function (e) {
            toast(String(e.message || e), true);
          })
          .finally(function () {
            setMgmtSaveLoading(false);
          });
      });

    $('cbMgmtDeleteBtn') &&
      $('cbMgmtDeleteBtn').addEventListener('click', function () {
        var id = parseInt($('cbMgmtId').value, 10);
        if (!id || !window.confirm('Delete this account? This cannot be undone.')) return;
        AccountsCrud.remove(id)
          .then(function (j) {
            if (!j.ok) throw new Error(j.error || 'Delete failed');
            toast('Account deleted');
            clearMgmtForm();
            closeMgmtFormModalIfAny();
            return refreshAfterAccountMutation().then(function () {
              loadMgmtAccountsList();
            });
          })
          .catch(function (e) {
            toast(String(e.message || e), true);
          });
      });

    $('cbLinkCustomersBtn') &&
      $('cbLinkCustomersBtn').addEventListener('click', function () {
        postCashbook('customer_accounts_sync', {})
          .then(function (j) {
            if (!j.ok) throw new Error(j.error || 'Sync failed');
            var n = typeof j.linked === 'number' ? j.linked : 0;
            toast(
              n > 0
                ? 'Linked ' + n + ' customer account(s).'
                : 'All customers already have Cash Book accounts.'
            );
            return refreshAfterAccountMutation().then(function () {
              loadMgmtAccountsList();
            });
          })
          .catch(function (e) {
            toast(String(e.message || e), true);
          });
      });

    $('cbReportLoad') &&
      $('cbReportLoad').addEventListener('click', function () {
        loadReports();
      });

    $('cbCustomerSave') &&
      $('cbCustomerSave').addEventListener('click', function () {
        var form = $('cbCustomerForm');
        if (!form || !customersSaveUrl) return;
        var fd = new FormData(form);
        fd.append('ajax', '1');
        fetch(customersSaveUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
          body: fd,
        })
          .then(function (r) {
            return r.json().then(function (data) {
              if (!r.ok) throw new Error((data && data.error) || 'Request failed');
              return data;
            });
          })
          .then(function (data) {
            if (data.error) throw new Error(data.error);
            var cbAcc = data.cashbook_account_id != null ? parseInt(data.cashbook_account_id, 10) : 0;
            if (cbAcc > 0) {
              state.mgmtFlashAccountId = cbAcc;
            }
            if (data.customer_created && cbAcc > 0) {
              toast('Customer and Cash Book account created successfully.');
            } else {
              toast('Customer saved — Cash Book account linked.');
            }
            form.reset();
            var m = bootstrap.Modal.getInstance($('cashbookCustomerModal'));
            if (m) m.hide();
            loadAccounts().then(function () {
              fillTransferSelects();
              if (state.panel === 'accounts') {
                state.mgmtPage = 1;
                loadMgmtAccountsList();
                refreshMgmtTotals();
              }
            });
          })
          .catch(function (e) {
            toast(String(e.message || e), true);
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
        var st = (r.acc.status || 'active') === 'inactive' ? 'secondary' : 'success';
        h +=
          '<div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body py-2">' +
          '<div class="fw-bold">' +
          escapeHtml(r.acc.name) +
          '</div>' +
          '<div class="mt-1">' +
          accountLedgerTypeBadge(r.acc.type, r.acc.account_kind) +
          ' <span class="badge rounded-pill text-bg-' +
          st +
          '">' +
          ((r.acc.status || 'active') === 'inactive' ? 'Inactive' : 'Active') +
          '</span></div>' +
          '<div class="small">Income <span class="text-success">' +
          money(t.income || 0) +
          '</span> · Expense <span class="text-danger">' +
          money(t.expense || 0) +
          '</span></div>' +
          '<div class="mt-1">Balance <strong class="text-primary">' +
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
        host.innerHTML =
          '<div class="text-center text-muted py-5"><i class="bi bi-graph-down fs-1 d-block mb-2 opacity-50"></i>No data in this range.</div>';
        return;
      }
      var html =
        '<table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Month</th><th class="text-end">Income</th><th class="text-end">Expense</th><th class="text-end">Net</th></tr></thead><tbody>';
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
    if (!$('cbEntryList') && !$('cbEntryTableBody')) return;
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
    setNavActive('transactions');
    syncCashbookAccountsChrome();
    var sortBoot = $('cbMgmtSort');
    if (sortBoot) state.mgmtSort = sortBoot.value || 'default';
    bind();
    loadAccounts()
      .then(function () {
        fillTransferSelects();
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
