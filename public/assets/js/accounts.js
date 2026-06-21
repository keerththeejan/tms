/**
 * Accounts module (Sidebar → Accounts).
 * Uses Cash Book JSON API (page=api_cashbook&cb_action=...).
 */
(function () {
  var cfg = window.TMS_ACCOUNTS || {};
  var cashbookBase = cfg.cashbookApiUrl || 'index.php?page=api_cashbook';
  var csrf = cfg.csrf || '';

  function $(id) {
    return document.getElementById(id);
  }

  function apiUrl(action, params) {
    var u = new URL(cashbookBase, window.location.href);
    u.searchParams.set('cb_action', action);
    Object.keys(params || {}).forEach(function (k) {
      if (params[k] !== undefined && params[k] !== null && params[k] !== '') {
        u.searchParams.set(k, String(params[k]));
      }
    });
    return u.pathname + u.search;
  }

  function get(action, params) {
    return fetch(apiUrl(action, params), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    }).then(function (r) {
      return r.json();
    });
  }

  function post(action, fields) {
    var fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('cb_action', action);
    Object.keys(fields || {}).forEach(function (k) {
      if (fields[k] !== undefined && fields[k] !== null) fd.append(k, fields[k]);
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

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function money(n) {
    var x = Number(n) || 0;
    return x.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function parseMoney(s) {
    if (s == null) return 0;
    var raw = String(s).replace(/,/g, '').trim();
    if (raw === '') return 0;
    var n = Number(raw);
    return isNaN(n) ? NaN : n;
  }

  function typeBadge(type) {
    var t = String(type || '');
    var map = {
      cash: ['Cash', 'text-bg-success'],
      bank: ['Bank', 'text-bg-warning'],
      branch: ['Digital', 'text-bg-info'],
      customer: ['Customer', 'text-bg-primary'],
      supplier: ['Supplier', 'text-bg-secondary'],
      employee: ['Employee', 'text-bg-dark'],
    };
    var v = map[t] || [t || '—', 'text-bg-light'];
    return '<span class="badge rounded-pill acc-type-badge ' + v[1] + '">' + escapeHtml(v[0]) + '</span>';
  }

  function statusBadge(status) {
    var s = (status || 'active') === 'inactive' ? 'inactive' : 'active';
    return (
      '<span class="badge rounded-pill ' +
      (s === 'inactive' ? 'text-bg-secondary' : 'text-bg-success') +
      '">' +
      (s === 'inactive' ? 'Inactive' : 'Active') +
      '</span>'
    );
  }

  var state = {
    all: {
      q: '',
      type: '',
      status: '',
      timer: null,
      accounts: [],
    },
    stmt: {
      accountId: 0,
      from: '',
      to: '',
      q: '',
      timer: null,
      entries: [],
      page: 1,
      perPage: 25,
      totals: null,
      account: null,
    },
  };

  function setAllLoading(on) {
    var el = $('accAllLoading');
    if (el) el.classList.toggle('d-none', !on);
  }

  function setStmtLoading(on) {
    var el = $('accStmtLoading');
    if (el) el.classList.toggle('d-none', !on);
  }

  function renderAllAccounts() {
    var tb = $('accAllTbody');
    var cards = $('accAllCards');
    var empty = $('accAllEmpty');
    var list = state.all.accounts || [];
    if (!list.length) {
      if (tb) tb.innerHTML = '';
      if (cards) cards.innerHTML = '';
      if (empty) empty.classList.remove('d-none');
      return;
    }
    if (empty) empty.classList.add('d-none');

    if (tb) {
      var html = '';
      list.forEach(function (a) {
        html +=
          '<tr>' +
          '<td class="fw-semibold">' +
          escapeHtml(a.name || '') +
          (a.description ? '<div class="small text-muted text-truncate">' + escapeHtml(a.description) + '</div>' : '') +
          '</td>' +
          '<td>' +
          typeBadge(a.type) +
          '</td>' +
          '<td class="text-end font-monospace">' +
          money(a.opening_balance || 0) +
          '</td>' +
          '<td class="text-end font-monospace fw-bold ' +
          (Number(a.balance || 0) < 0 ? 'text-danger' : 'text-success') +
          '">' +
          money(a.balance || 0) +
          '</td>' +
          '<td>' +
          statusBadge(a.status) +
          '</td>' +
          '<td class="text-end text-nowrap">' +
          '<button class="btn btn-sm btn-outline-secondary rounded-pill acc-act-view" data-id="' +
          a.id +
          '"><i class="bi bi-eye"></i></button> ' +
          '<button class="btn btn-sm btn-outline-primary rounded-pill acc-act-edit" data-id="' +
          a.id +
          '"><i class="bi bi-pencil"></i></button> ' +
          '<button class="btn btn-sm btn-outline-danger rounded-pill acc-act-del" data-id="' +
          a.id +
          '"' +
          (parseInt(a.is_system, 10) === 1 || (a.customer_id && String(a.customer_id) !== '0') ? ' disabled' : '') +
          '><i class="bi bi-trash"></i></button>' +
          '</td>' +
          '</tr>';
      });
      tb.innerHTML = html;
    }

    if (cards) {
      var ch = '';
      list.forEach(function (a) {
        var bal = Number(a.balance || 0);
        var canDel = !(parseInt(a.is_system, 10) === 1 || (a.customer_id && String(a.customer_id) !== '0'));
        ch +=
          '<div class="acc-card p-3 mb-2" data-id="' +
          a.id +
          '">' +
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
          '<div class="min-w-0">' +
          '<div class="fw-bold text-truncate">' +
          escapeHtml(a.name || '') +
          '</div>' +
          (a.description ? '<div class="small text-muted text-truncate">' + escapeHtml(a.description) + '</div>' : '') +
          '<div class="mt-2 d-flex flex-wrap gap-2">' +
          typeBadge(a.type) +
          statusBadge(a.status) +
          '</div>' +
          '</div>' +
          '<div class="text-end">' +
          '<div class="small text-muted">Balance</div>' +
          '<div class="fw-bold font-monospace ' +
          (bal < 0 ? 'text-danger' : 'text-success') +
          '">' +
          money(bal) +
          '</div>' +
          '</div>' +
          '</div>' +
          '<div class="d-flex gap-2 mt-3">' +
          '<button class="btn btn-outline-secondary btn-sm rounded-pill flex-fill acc-act-view" data-id="' +
          a.id +
          '"><i class="bi bi-eye me-1"></i>View</button>' +
          '<button class="btn btn-outline-primary btn-sm rounded-pill flex-fill acc-act-edit" data-id="' +
          a.id +
          '"><i class="bi bi-pencil me-1"></i>Edit</button>' +
          '<button class="btn btn-outline-danger btn-sm rounded-pill flex-fill acc-act-del" data-id="' +
          a.id +
          '"' +
          (canDel ? '' : ' disabled') +
          '><i class="bi bi-trash me-1"></i>Delete</button>' +
          '</div>' +
          '</div>';
      });
      cards.innerHTML = ch;
    }
  }

  function loadAllAccounts() {
    setAllLoading(true);
    return get('accounts', {
      cb_page: 1,
      per_page: 10000,
      q: state.all.q,
      type: state.all.type,
      status: state.all.status,
      sort: 'name_asc',
    })
      .then(function (j) {
        if (!j.ok) throw new Error(j.error || 'Load failed');
        state.all.accounts = j.accounts || [];
        renderAllAccounts();
        fillStatementAccountOptions();
      })
      .catch(function () {
        state.all.accounts = [];
        renderAllAccounts();
      })
      .finally(function () {
        setAllLoading(false);
      });
  }

  function showFormError(msg) {
    var el = $('accFormErr');
    if (!el) return;
    if (!msg) {
      el.classList.add('d-none');
      el.textContent = '';
      return;
    }
    el.textContent = msg;
    el.classList.remove('d-none');
  }

  function clearForm() {
    showFormError('');
    if ($('accFormId')) $('accFormId').value = '0';
    if ($('accName')) $('accName').value = '';
    if ($('accType')) $('accType').value = 'cash';
    if ($('accOpening')) $('accOpening').value = '0.00';
    if ($('accStatus')) $('accStatus').value = 'active';
    if ($('accDesc')) $('accDesc').value = '';
  }

  function fillFormFromAccount(acc) {
    showFormError('');
    if ($('accFormId')) $('accFormId').value = String(acc.id || 0);
    if ($('accName')) $('accName').value = acc.name || '';
    if ($('accType')) $('accType').value = acc.type || 'cash';
    if ($('accOpening')) $('accOpening').value = money(acc.opening_balance || 0);
    if ($('accStatus')) $('accStatus').value = (acc.status || 'active') === 'inactive' ? 'inactive' : 'active';
    if ($('accDesc')) $('accDesc').value = acc.description || '';
  }

  function saveAccount() {
    showFormError('');
    var id = parseInt(($('accFormId') && $('accFormId').value) || '0', 10) || 0;
    var name = ($('accName') && $('accName').value ? $('accName').value : '').trim();
    var type = ($('accType') && $('accType').value) || 'cash';
    var opening = parseMoney(($('accOpening') && $('accOpening').value) || '0');
    var status = ($('accStatus') && $('accStatus').value) || 'active';
    var desc = ($('accDesc') && $('accDesc').value ? $('accDesc').value : '').trim();

    if (!name) {
      showFormError('Account name is required.');
      return Promise.resolve();
    }
    if (isNaN(opening)) {
      showFormError('Opening balance must be a valid number.');
      return Promise.resolve();
    }

    return post('account_save', {
      id: String(id),
      name: name,
      type: type,
      status: status,
      opening_balance: String(opening),
      description: desc,
    }).then(function (j) {
      if (!j.ok) throw new Error(j.error || 'Save failed');
      window.location.href = (cfg.accountsUrl || 'index.php?page=accounts') + '&tab=all';
    }).catch(function (e) {
      showFormError(String((e && e.message) || e || 'Save failed'));
    });
  }

  function deleteAccount(id) {
    if (!window.confirm('Delete this account? This cannot be undone.')) return;
    post('account_delete', { id: String(id) })
      .then(function (j) {
        if (!j.ok) throw new Error(j.error || 'Delete failed');
        loadAllAccounts();
      })
      .catch(function (e) {
        window.alert(String((e && e.message) || e || 'Delete failed'));
      });
  }

  function fillStatementAccountOptions() {
    var sel = $('accStmtAccount');
    if (!sel) return;
    var prev = sel.value;
    sel.innerHTML = '';
    (state.all.accounts || []).forEach(function (a) {
      var o = document.createElement('option');
      o.value = String(a.id);
      o.textContent = (a.name || '') + ' — ' + money(a.balance || 0);
      sel.appendChild(o);
    });
    if (prev && sel.querySelector('option[value="' + prev + '"]')) {
      sel.value = prev;
    }
  }

  function setStmtHeader(acc) {
    var t = $('accStmtTitle');
    var s = $('accStmtSub');
    if (t) t.textContent = acc ? acc.name : 'Account Statement';
    if (s) {
      if (!acc) {
        s.textContent = 'Select an account to view transactions.';
      } else {
        s.innerHTML = typeBadge(acc.type) + ' <span class="ms-2">Current balance <strong class="text-primary">' + escapeHtml(money(acc.balance || 0)) + '</strong></span>';
      }
    }

    var addTxn = $('accStmtAddTxn');
    var tr = $('accStmtTransfer');
    if (addTxn) {
      addTxn.href = acc
        ? (cfg.accountingEntryUrl || 'index.php?page=accounting&action=entry&voucher_type=PAYMENT')
        : '#';
    }
    if (tr) {
      tr.href = acc
        ? (cfg.transferVoucherUrl || 'index.php?page=transfer_voucher&action=entry')
        : '#';
    }
  }

  function renderStmtPager() {
    var ul = $('accStmtPager');
    if (!ul) return;
    var total = (state.stmt.entries || []).length;
    var pages = Math.max(1, Math.ceil(total / state.stmt.perPage));
    var page = Math.max(1, Math.min(pages, state.stmt.page));
    state.stmt.page = page;
    if (pages <= 1) {
      ul.innerHTML = '';
      return;
    }
    function li(p, label, dis, act) {
      return (
        '<li class="page-item' +
        (dis ? ' disabled' : '') +
        (act ? ' active' : '') +
        '"><a class="page-link rounded-pill" href="#" data-page="' +
        p +
        '">' +
        escapeHtml(label) +
        '</a></li>'
      );
    }
    var html = '';
    html += li(page - 1, 'Prev', page <= 1, false);
    html += li(page, String(page) + ' / ' + String(pages), true, true);
    html += li(page + 1, 'Next', page >= pages, false);
    ul.innerHTML = html;
    ul.querySelectorAll('a[data-page]').forEach(function (a) {
      a.addEventListener('click', function (ev) {
        ev.preventDefault();
        var p = parseInt(a.getAttribute('data-page'), 10);
        if (!isNaN(p)) {
          state.stmt.page = p;
          renderStatement();
        }
      });
    });
  }

  function renderStatement() {
    var tb = $('accStmtTbody');
    var cards = $('accStmtCards');
    var empty = $('accStmtEmpty');
    var all = state.stmt.entries || [];
    var total = all.length;
    if (!total) {
      if (tb) tb.innerHTML = '';
      if (cards) cards.innerHTML = '';
      if (empty) empty.classList.remove('d-none');
      renderStmtPager();
      return;
    }
    if (empty) empty.classList.add('d-none');
    var pages = Math.max(1, Math.ceil(total / state.stmt.perPage));
    var page = Math.max(1, Math.min(pages, state.stmt.page));
    var start = (page - 1) * state.stmt.perPage;
    var slice = all.slice(start, start + state.stmt.perPage);

    function kindBadge(k) {
      if (k === 'income') return '<span class="badge rounded-pill text-bg-success">Income</span>';
      if (k === 'expense') return '<span class="badge rounded-pill text-bg-danger">Expense</span>';
      if (k === 'transfer_in' || k === 'transfer_out') return '<span class="badge rounded-pill text-bg-secondary">Transfer</span>';
      return '';
    }

    if (tb) {
      var html = '';
      slice.forEach(function (r) {
        var k = String(r.kind || '');
        var amt = Number(r.amount || 0);
        var debit = k === 'expense' || k === 'transfer_out' ? amt : 0;
        var credit = k === 'income' || k === 'transfer_in' ? amt : 0;
        var desc = (r.notes || '').trim() || (r.peer_name || '').trim() || '';
        var idStr = String(r.id);
        var viewKind = r.transfer_id ? 'transfer' : 'transaction';
        var viewId = r.transfer_id ? String(r.transfer_id) : idStr;
        html +=
          '<tr>' +
          '<td class="text-nowrap small text-muted">' +
          escapeHtml(String(r.occurred_at || '').replace('T', ' ').slice(0, 16)) +
          '</td>' +
          '<td class="small text-muted">' +
          escapeHtml(idStr) +
          '</td>' +
          '<td>' +
          kindBadge(k) +
          '</td>' +
          '<td class="small">' +
          escapeHtml(desc) +
          '</td>' +
          '<td class="text-end font-monospace text-danger">' +
          (debit ? money(debit) : '') +
          '</td>' +
          '<td class="text-end font-monospace text-success">' +
          (credit ? money(credit) : '') +
          '</td>' +
          '<td class="text-end font-monospace fw-semibold">' +
          money(r.running_balance || 0) +
          '</td>' +
          '<td class="text-end">' +
          '<a class="btn btn-sm btn-outline-secondary rounded-pill" href="' +
          apiUrl('entry_get', { kind: viewKind, id: viewId }) +
          '" target="_blank" rel="noopener" title="Open JSON details"><i class="bi bi-box-arrow-up-right"></i></a>' +
          '</td>' +
          '</tr>';
      });
      tb.innerHTML = html;
    }

    if (cards) {
      var ch = '';
      slice.forEach(function (r) {
        var k = String(r.kind || '');
        var amt = Number(r.amount || 0);
        var debit = k === 'expense' || k === 'transfer_out' ? amt : 0;
        var credit = k === 'income' || k === 'transfer_in' ? amt : 0;
        var desc = (r.notes || '').trim() || (r.peer_name || '').trim() || '';
        ch +=
          '<div class="acc-card p-3 mb-2">' +
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
          '<div class="min-w-0">' +
          '<div class="small text-muted">' +
          escapeHtml(String(r.occurred_at || '').replace('T', ' ').slice(0, 16)) +
          '</div>' +
          '<div class="fw-semibold mt-1">' +
          kindBadge(k) +
          ' <span class="ms-1 small text-muted">#' +
          escapeHtml(String(r.id)) +
          '</span></div>' +
          (desc ? '<div class="small text-secondary mt-1">' + escapeHtml(desc) + '</div>' : '') +
          '</div>' +
          '<div class="text-end">' +
          (debit ? '<div class="small text-danger fw-bold">-' + money(debit) + '</div>' : '') +
          (credit ? '<div class="small text-success fw-bold">+' + money(credit) + '</div>' : '') +
          '<div class="small text-muted">Bal <span class="fw-semibold text-primary">' +
          money(r.running_balance || 0) +
          '</span></div>' +
          '</div>' +
          '</div>' +
          '</div>';
      });
      cards.innerHTML = ch;
    }

    renderStmtPager();
  }

  function loadStatement() {
    var sel = $('accStmtAccount');
    if (!sel || !sel.value) return;
    var aid = parseInt(sel.value, 10) || 0;
    state.stmt.accountId = aid;
    state.stmt.from = ($('accStmtFrom') && $('accStmtFrom').value) || '';
    state.stmt.to = ($('accStmtTo') && $('accStmtTo').value) || '';
    state.stmt.q = ($('accStmtSearch') && $('accStmtSearch').value.trim()) || '';
    state.stmt.page = 1;

    var acc = (state.all.accounts || []).find(function (a) {
      return String(a.id) === String(aid);
    });
    state.stmt.account = acc || null;
    setStmtHeader(state.stmt.account);

    if (!state.stmt.from || !state.stmt.to) return;
    setStmtLoading(true);
    return Promise.all([
      get('entries_range', { account_id: aid, from: state.stmt.from, to: state.stmt.to, q: state.stmt.q }),
      get('totals_range', { account_id: aid, from: state.stmt.from, to: state.stmt.to }),
    ])
      .then(function (arr) {
        var e = arr[0];
        var t = arr[1];
        if (!e.ok) throw new Error(e.error || 'Load failed');
        state.stmt.entries = e.entries || [];
        state.stmt.totals = t && t.ok ? t.totals : null;

        // KPIs
        var open = $('accKpiOpen');
        var credit = $('accKpiCredit');
        var debit = $('accKpiDebit');
        var bal = $('accKpiBalance');
        var openingBal = state.stmt.account ? Number(state.stmt.account.opening_balance || 0) : 0;
        if (open) open.textContent = money(openingBal);
        if (credit) credit.textContent = money((state.stmt.totals && state.stmt.totals.income) || 0);
        if (debit) debit.textContent = money((state.stmt.totals && state.stmt.totals.expense) || 0);
        if (bal) bal.textContent = money((state.stmt.account && state.stmt.account.balance) || 0);

        renderStatement();
      })
      .catch(function () {
        state.stmt.entries = [];
        renderStatement();
      })
      .finally(function () {
        setStmtLoading(false);
      });
  }

  function bind() {
    if ($('accSearch')) {
      $('accSearch').addEventListener('input', function () {
        clearTimeout(state.all.timer);
        state.all.timer = setTimeout(function () {
          state.all.q = $('accSearch').value.trim();
          loadAllAccounts();
        }, 250);
      });
    }
    if ($('accFilterType')) {
      $('accFilterType').addEventListener('change', function () {
        state.all.type = $('accFilterType').value;
        loadAllAccounts();
      });
    }
    if ($('accFilterStatus')) {
      $('accFilterStatus').addEventListener('change', function () {
        state.all.status = $('accFilterStatus').value;
        loadAllAccounts();
      });
    }
    if ($('accRefresh')) $('accRefresh').addEventListener('click', function () { loadAllAccounts(); });

    if ($('accSave')) $('accSave').addEventListener('click', function () { saveAccount(); });
    if ($('accFormReset')) $('accFormReset').addEventListener('click', function () { clearForm(); });

    document.body.addEventListener('click', function (e) {
      var v = e.target.closest('.acc-act-view');
      if (v) {
        var id = v.getAttribute('data-id');
        window.location.href = (cfg.accountsUrl || 'index.php?page=accounts') + '&tab=statement&account_id=' + encodeURIComponent(String(id));
      }
      var ed = e.target.closest('.acc-act-edit');
      if (ed) {
        var id = ed.getAttribute('data-id');
        get('account_get', { id: id }).then(function (j) {
          if (j && j.ok && j.account) {
            fillFormFromAccount(j.account);
            window.location.href = (cfg.accountsUrl || 'index.php?page=accounts') + '&tab=add&id=' + encodeURIComponent(String(j.account.id));
          }
        });
      }
      var del = e.target.closest('.acc-act-del');
      if (del) {
        if (del.disabled) return;
        deleteAccount(del.getAttribute('data-id'));
      }
    });

    if ($('accStmtLoad')) $('accStmtLoad').addEventListener('click', function () { loadStatement(); });
    if ($('accStmtAccount')) $('accStmtAccount').addEventListener('change', function () { loadStatement(); });
    if ($('accStmtSearch')) {
      $('accStmtSearch').addEventListener('input', function () {
        clearTimeout(state.stmt.timer);
        state.stmt.timer = setTimeout(function () {
          loadStatement();
        }, 320);
      });
    }
    if ($('accStmtExport')) {
      $('accStmtExport').addEventListener('click', function () {
        if (!state.stmt.accountId) return;
        var u = apiUrl('export_csv', { account_id: state.stmt.accountId, period: 'all', anchor: '2000-01-01', q: state.stmt.q });
        window.location.href = u;
      });
    }
    if ($('accStmtPrint')) {
      $('accStmtPrint').addEventListener('click', function () {
        window.print();
      });
    }
  }

  function boot() {
    bind();
    // load accounts
    loadAllAccounts().then(function () {
      // preselect statement account if present
      var qs = new URLSearchParams(window.location.search);
      var stmtAcc = qs.get('account_id') || '';
      if (stmtAcc && $('accStmtAccount')) {
        $('accStmtAccount').value = String(stmtAcc);
      }
      if ($('accStmtFrom') && $('accStmtTo')) {
        // initial load only when statement tab active and account selected
        if ($('accStmtPanel') && !$('accStmtPanel').classList.contains('d-none') && $('accStmtAccount') && $('accStmtAccount').value) {
          loadStatement();
        }
      }
      // edit prefill if tab=add&id=...
      var editId = qs.get('id') || '';
      if (editId && $('accFormPanel') && !$('accFormPanel').classList.contains('d-none')) {
        get('account_get', { id: editId }).then(function (j) {
          if (j && j.ok && j.account) fillFormFromAccount(j.account);
        });
      } else if ($('accFormPanel') && !$('accFormPanel').classList.contains('d-none')) {
        clearForm();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();

