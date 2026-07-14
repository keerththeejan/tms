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
            <th class="text-end text-nowrap">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Delete Voucher confirmation (Bootstrap 5) -->
<div class="modal fade" id="accVchDeleteModal" tabindex="-1" aria-labelledby="accVchDeleteModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="accVchDeleteModalTitle">Delete Voucher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2" id="accVchDeleteMessage">Are you sure you want to delete this voucher?</p>
        <p class="text-danger small mb-3">This action cannot be undone.</p>
        <div class="mb-0">
          <label class="form-label small" for="accVchDeleteReason">Reason (optional)</label>
          <input type="text" class="form-control form-control-sm" id="accVchDeleteReason" maxlength="255" placeholder="Optional deletion reason">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="accVchDeleteConfirmBtn">
          <i class="bi bi-trash" aria-hidden="true"></i> Delete
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var pendingDelete = null;
  var deleteModal = null;

  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('accVchLoadBtn')?.addEventListener('click', loadList);
    var modalEl = document.getElementById('accVchDeleteModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
      deleteModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    }
    document.getElementById('accVchDeleteConfirmBtn')?.addEventListener('click', confirmDelete);
    document.querySelector('#accVchTable tbody')?.addEventListener('click', onTableClick);
    loadList();
  });

  function canDelete() {
    return !!(window.AccModule && AccModule.cfg && AccModule.cfg.canDeleteVouchers);
  }

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
      if (!res.ok) throw new Error(res.error || res.message || 'Load failed');
      var rows = (res.data && res.data.rows) || [];
      var tbody = document.querySelector('#accVchTable tbody');
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">No vouchers found</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(function (r) {
        var editUrl = AccModule.cfg.baseUrl + 'index.php?page=accounting&action=entry&voucher_type='
          + encodeURIComponent(r.voucher_type || 'PAYMENT')
          + '&vid=' + encodeURIComponent(r.id);
        var deleteBtn = canDelete()
          ? '<button type="button" class="btn btn-sm btn-danger acc-vch-delete-btn"'
            + ' data-id="' + escapeAttr(r.id) + '"'
            + ' data-number="' + escapeAttr(r.voucher_number) + '"'
            + ' title="Delete voucher">'
            + '<i class="bi bi-trash" aria-hidden="true"></i> Delete</button>'
          : '';
        return '<tr>' +
          '<td>' + escapeHtml(r.voucher_number) + '</td>' +
          '<td>' + escapeHtml(r.voucher_date) + '</td>' +
          '<td>' + escapeHtml(r.voucher_type) + '</td>' +
          '<td>' + escapeHtml(r.status) + '</td>' +
          '<td><small>' + escapeHtml(r.narration || '') + '</small></td>' +
          '<td class="text-end">' + AccModule.money(r.total_debit) + '</td>' +
          '<td class="text-end">' + AccModule.money(r.total_credit) + '</td>' +
          '<td class="text-end text-nowrap">' +
            '<div class="d-inline-flex flex-wrap gap-1 justify-content-end">' +
              '<a class="btn btn-sm btn-outline-primary" href="' + editUrl + '">Open</a>' +
              deleteBtn +
            '</div>' +
          '</td></tr>';
      }).join('');
    }).catch(function (e) { AccModule.toast(String(e.message || e), 'error'); });
  }

  function onTableClick(e) {
    var btn = e.target.closest('.acc-vch-delete-btn');
    if (!btn) return;
    if (!canDelete()) {
      AccModule.toast('You do not have permission to delete vouchers.', 'error');
      return;
    }
    pendingDelete = {
      id: parseInt(btn.getAttribute('data-id') || '0', 10),
      number: btn.getAttribute('data-number') || '',
    };
    var msg = document.getElementById('accVchDeleteMessage');
    if (msg) {
      msg.textContent = 'Are you sure you want to delete Voucher No. ' + (pendingDelete.number || pendingDelete.id) + '?';
    }
    var reasonEl = document.getElementById('accVchDeleteReason');
    if (reasonEl) reasonEl.value = '';
    if (deleteModal) {
      deleteModal.show();
    } else if (window.confirm(msg ? msg.textContent : 'Delete voucher?')) {
      confirmDelete();
    }
  }

  function confirmDelete() {
    if (!pendingDelete || !pendingDelete.id) return;
    if (!canDelete()) {
      AccModule.toast('You do not have permission to delete vouchers.', 'error');
      return;
    }

    var confirmBtn = document.getElementById('accVchDeleteConfirmBtn');
    if (confirmBtn) {
      confirmBtn.disabled = true;
    }

    var reason = (document.getElementById('accVchDeleteReason') || {}).value || '';

    AccModule.postJson({ acc_action: 'delete_voucher' }, {
      id: pendingDelete.id,
      reason: reason,
    }).then(function (res) {
      if (deleteModal) deleteModal.hide();
      pendingDelete = null;
      if (!res.success && !res.ok) {
        throw new Error(res.message || res.error || 'Unable to delete voucher.');
      }
      AccModule.toast(res.message || 'Voucher deleted successfully.');
      loadList();
    }).catch(function (e) {
      AccModule.toast(String(e.message || e), 'error');
    }).finally(function () {
      if (confirmBtn) confirmBtn.disabled = false;
    });
  }

  function escapeHtml(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function escapeAttr(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;')
      .replace(/</g, '&lt;');
  }
})();
</script>
