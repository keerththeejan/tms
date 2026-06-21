<div class="acc-vouchers-list">
  <div class="acc-toolbar">
    <div>
      <label class="form-label" for="accVchFrom">From</label>
      <input type="date" class="form-control form-control-sm" id="accVchFrom" value="<?php echo date('Y-m-01'); ?>">
    </div>
    <div>
      <label class="form-label" for="accVchTo">To</label>
      <input type="date" class="form-control form-control-sm" id="accVchTo" value="<?php echo date('Y-m-t'); ?>">
    </div>
    <div>
      <label class="form-label" for="accVchType">Type</label>
      <select class="form-select form-select-sm" id="accVchType">
        <option value="">All</option>
        <option value="PAYMENT">Payment</option>
        <option value="RECEIPT">Receipt</option>
        <option value="JOURNAL">Journal</option>
        <option value="CONTRA">Contra</option>
        <option value="TRANSFER">Transfer</option>
      </select>
    </div>
    <div>
      <label class="form-label" for="accVchStatus">Status</label>
      <select class="form-select form-select-sm" id="accVchStatus">
        <option value="">All</option>
        <option value="DRAFT">Draft</option>
        <option value="POSTED">Posted</option>
        <option value="CANCELLED">Cancelled</option>
      </select>
    </div>
    <div class="flex-grow-1">
      <label class="form-label" for="accVchQ">Search</label>
      <input type="search" class="form-control form-control-sm" id="accVchQ" placeholder="Voucher no. or narration">
    </div>
    <div>
      <label class="form-label">&nbsp;</label>
      <button type="button" class="btn btn-primary btn-sm d-block" id="accVchLoadBtn">Load</button>
    </div>
  </div>

  <div class="acc-table-wrap">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="accVchTable">
        <thead>
          <tr>
            <th>Voucher No.</th>
            <th>Date</th>
            <th>Type</th>
            <th>Status</th>
            <th>Narration</th>
            <th class="text-end">Debit</th>
            <th class="text-end">Credit</th>
            <th></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('accVchLoadBtn')?.addEventListener('click', loadList);
    loadList();
  });

  function loadList() {
    AccModule.fetchJson({
      acc_action: 'list_vouchers',
      from_date: document.getElementById('accVchFrom').value,
      to_date: document.getElementById('accVchTo').value,
      voucher_type: document.getElementById('accVchType').value,
      status: document.getElementById('accVchStatus').value,
      q: document.getElementById('accVchQ').value,
      limit: 100,
    }).then(function (res) {
      if (!res.ok) throw new Error(res.error || 'Load failed');
      var rows = (res.data && res.data.rows) || [];
      var tbody = document.querySelector('#accVchTable tbody');
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">No vouchers found</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(function (r) {
        var editUrl = AccModule.cfg.baseUrl + 'index.php?page=accounting&action=entry&voucher_type=' + encodeURIComponent(r.voucher_type || 'PAYMENT') + '&vid=' + encodeURIComponent(r.id);
        return '<tr>' +
          '<td>' + escapeHtml(r.voucher_number) + '</td>' +
          '<td>' + escapeHtml(r.voucher_date) + '</td>' +
          '<td>' + escapeHtml(r.voucher_type) + '</td>' +
          '<td>' + escapeHtml(r.status) + '</td>' +
          '<td><small>' + escapeHtml(r.narration || '') + '</small></td>' +
          '<td class="text-end">' + AccModule.money(r.total_debit) + '</td>' +
          '<td class="text-end">' + AccModule.money(r.total_credit) + '</td>' +
          '<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="' + editUrl + '">Open</a></td></tr>';
      }).join('');
    }).catch(function (e) { AccModule.toast(String(e.message || e), 'error'); });
  }

  function escapeHtml(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
})();
</script>
