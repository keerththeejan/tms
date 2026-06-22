<?php
/** Customer Ledger — AR accounts linked to TMS customers */
$filterCustomerId = (int) ($_GET['customer_id'] ?? 0);
$filterQ = trim((string) ($_GET['q'] ?? ''));
?>
<div class="acc-customer-ledger">
  <div class="acc-toolbar flex-wrap">
    <div class="flex-grow-1">
      <label class="form-label" for="clSearch">Search</label>
      <input type="search" class="form-control form-control-sm" id="clSearch"
             value="<?php echo htmlspecialchars($filterQ); ?>"
             placeholder="Customer name, phone, or ledger code">
    </div>
    <div>
      <label class="form-label" for="clStatus">Status</label>
      <select class="form-select form-select-sm" id="clStatus">
        <option value="">All</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
    <div>
      <label class="form-label">&nbsp;</label>
      <button type="button" class="btn btn-primary btn-sm d-block" id="clLoadBtn">Load</button>
    </div>
  </div>

  <div class="acc-table-wrap">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="clTable">
        <thead>
          <tr>
            <th>Ledger Code</th>
            <th>Customer</th>
            <th>Phone</th>
            <th class="text-end">Invoices</th>
            <th class="text-end">Payments</th>
            <th class="text-end">Outstanding</th>
            <th class="text-end">Ledger Balance</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="9" class="text-center text-muted py-3">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  var preselectCustomerId = <?php echo (int) $filterCustomerId; ?>;

  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('clLoadBtn')?.addEventListener('click', loadList);
    document.getElementById('clSearch')?.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); loadList(); }
    });
    loadList();
  });

  function loadList() {
    AccModule.fetchJson({
      acc_action: 'list_customer_ledgers',
      q: document.getElementById('clSearch').value,
      status: document.getElementById('clStatus').value,
    }).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'Load failed');
      var rows = res.data || [];
      if (preselectCustomerId > 0) {
        rows = rows.filter(function (r) { return Number(r.customer_id) === preselectCustomerId; });
      }
      renderRows(rows);
    }).catch(function (e) {
      AccModule.toast(String(e.message || e), 'error');
      document.querySelector('#clTable tbody').innerHTML =
        '<tr><td colspan="9" class="text-center text-danger py-3">Failed to load</td></tr>';
    });
  }

  function renderRows(rows) {
    var tbody = document.querySelector('#clTable tbody');
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3">No customer ledgers found</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(function (r) {
      var custUrl = AccModule.cfg.baseUrl + 'index.php?page=customers&action=edit&id=' + encodeURIComponent(r.customer_id);
      var ledgerUrl = AccModule.cfg.baseUrl + 'index.php?page=accounting&action=ledger&account_id=' + encodeURIComponent(r.account_id);
      var statusBadge = Number(r.is_active) === 1
        ? '<span class="badge bg-success-subtle text-success">Active</span>'
        : '<span class="badge bg-secondary-subtle text-secondary">Inactive</span>';
      var outstanding = Number(r.outstanding_amount || 0);
      var outBadge = outstanding > 0
        ? '<span class="badge bg-warning-subtle text-warning-emphasis">' + AccModule.money(outstanding) + '</span>'
        : '<span class="text-muted">' + AccModule.money(0) + '</span>';
      return '<tr>' +
        '<td><code>' + escapeHtml(r.ledger_code) + '</code></td>' +
        '<td>' + escapeHtml(r.customer_name) + '</td>' +
        '<td>' + escapeHtml(r.customer_phone || '') + '</td>' +
        '<td class="text-end">' + AccModule.money(r.total_invoices) + '<div class="small text-muted">' + Number(r.invoice_count || 0) + ' inv.</div></td>' +
        '<td class="text-end">' + AccModule.money(r.total_payments) + '</td>' +
        '<td class="text-end">' + outBadge + '</td>' +
        '<td class="text-end">' + AccModule.money(r.current_balance) + '</td>' +
        '<td>' + statusBadge + '</td>' +
        '<td class="text-end text-nowrap">' +
          '<a class="btn btn-sm btn-outline-secondary me-1" href="' + custUrl + '" title="Customer details"><i class="bi bi-person"></i></a>' +
          '<a class="btn btn-sm btn-outline-primary" href="' + ledgerUrl + '" title="View GL entries"><i class="bi bi-journal-text"></i></a>' +
        '</td></tr>';
    }).join('');
  }

  function escapeHtml(s) {
    return AccModule.escapeHtml(String(s == null ? '' : s));
  }
})();
</script>
