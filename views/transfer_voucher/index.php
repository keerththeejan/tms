<?php
$csrf = (string) ($csrf ?? Helpers::csrfToken());
$baseUrl = Helpers::baseUrl('');
$voucherRows = $tvList['rows'] ?? [];
$totalRows = (int) ($tvList['total'] ?? 0);
$pageNo = max(1, (int) ($tvList['page'] ?? 1));
$pageLimit = max(1, (int) ($tvList['limit'] ?? 20));
$totalPages = max(1, (int) ceil($totalRows / $pageLimit));
?>
<style>
.tv-shell { background: linear-gradient(180deg, #f8fbff 0%, #f3f6fb 100%); min-height: 100vh; }
.tv-hero { background: linear-gradient(135deg, #183153 0%, #234b7a 45%, #3b82f6 100%); color: #fff; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 18px 40px rgba(24,49,83,.18); }
.tv-hero h1 { font-size: clamp(1.5rem, 2vw, 2.25rem); font-weight: 800; }
.tv-panel, .tv-card { background: #fff; border-radius: 1rem; box-shadow: 0 10px 30px rgba(20, 40, 80, .08); }
.tv-card { padding: 1.25rem; }
.tv-label { font-size: .8rem; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; font-weight: 700; }
.tv-kpi { display: grid; gap: .25rem; }
.tv-kpi strong { font-size: 1.4rem; color: #0f172a; }
.tv-form-grid { display: grid; gap: 1rem; }
.tv-form-grid .form-label { font-weight: 700; color: #344054; }
.tv-table thead th { white-space: nowrap; background: #eff4fb; color: #334155; border-bottom: 0; }
.tv-table td, .tv-table th { vertical-align: middle; }
.tv-badge { border-radius: 999px; padding: .45rem .8rem; font-weight: 800; font-size: .75rem; }
.tv-draft { background: #fff7e6; color: #8a5b00; }
.tv-posted { background: #e8f7ee; color: #116329; }
.tv-cancelled { background: #fbe9e7; color: #a02d24; }
.tv-muted { color: #64748b; }
.tv-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
.tv-side-list { max-height: 260px; overflow: auto; }
@media (max-width: 992px) { .tv-hero { padding: 1.25rem; } }
</style>

<div class="tv-shell p-2 p-lg-3">
  <div class="container-fluid px-0 px-lg-2">
    <div class="tv-hero mb-3">
      <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
        <div>
          <div class="tv-label text-white-50">Treasury movement</div>
          <h1 class="mb-1">Transfer Voucher</h1>
          <div class="text-white-75">Move funds between cashbook accounts without changing the existing transfer engine.</div>
        </div>
        <div class="tv-actions">
          <a class="btn btn-light btn-sm fw-semibold" href="<?php echo htmlspecialchars($baseUrl . 'index.php?page=transfer_voucher&action=entry'); ?>"><i class="bi bi-plus-circle me-1"></i> New Voucher Entry</a>
          <a class="btn btn-light btn-sm fw-semibold" href="<?php echo htmlspecialchars($baseUrl . 'index.php?page=dashboard'); ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
          <button type="button" class="btn btn-outline-light btn-sm fw-semibold" onclick="tvExportCsv()"><i class="bi bi-file-earmark-excel me-1"></i> Export CSV</button>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-12 col-md-6 col-xl-3"><div class="tv-card h-100"><div class="tv-label">Voucher Count</div><div class="tv-kpi"><strong><?php echo (int) ($tvSummary['total_vouchers'] ?? 0); ?></strong><span class="tv-muted">In the current range</span></div></div></div>
      <div class="col-12 col-md-6 col-xl-3"><div class="tv-card h-100"><div class="tv-label">Posted</div><div class="tv-kpi"><strong class="text-success"><?php echo (int) ($tvSummary['posted_count'] ?? 0); ?></strong><span class="tv-muted">Completed transfers</span></div></div></div>
      <div class="col-12 col-md-6 col-xl-3"><div class="tv-card h-100"><div class="tv-label">Draft</div><div class="tv-kpi"><strong class="text-warning"><?php echo (int) ($tvSummary['draft_count'] ?? 0); ?></strong><span class="tv-muted">Waiting to post</span></div></div></div>
      <div class="col-12 col-md-6 col-xl-3"><div class="tv-card h-100"><div class="tv-label">Amount</div><div class="tv-kpi"><strong class="text-primary"><?php echo Helpers::formatMoney((float) ($tvSummary['total_amount'] ?? 0)); ?></strong><span class="tv-muted">Movement total</span></div></div></div>
    </div>

    <div class="row g-3">
      <div class="col-12 col-xl-5">
        <div class="tv-panel p-3 p-lg-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <div class="tv-label">Entry form</div>
              <h2 class="h5 mb-0">Create or post voucher</h2>
            </div>
            <span class="tv-badge tv-draft" id="tvFormStatusBadge">DRAFT</span>
          </div>
          <input type="hidden" id="tvId" value="0">
          <div class="tv-form-grid">
            <div>
              <label class="form-label" for="tvVoucherNo">Voucher No.</label>
              <input class="form-control" id="tvVoucherNo" value="TRF-<?php echo date('Y'); ?>-000001" readonly>
            </div>
            <div class="row g-3">
              <div class="col-12 col-md-6"><label class="form-label" for="tvVoucherDate">Voucher Date</label><input class="form-control" type="date" id="tvVoucherDate" value="<?php echo htmlspecialchars($tvToday); ?>"></div>
              <div class="col-12 col-md-6"><label class="form-label" for="tvReferenceNo">Reference No.</label><input class="form-control" id="tvReferenceNo" placeholder="Optional reference"></div>
            </div>
            <div class="row g-3">
              <div class="col-12 col-md-6"><label class="form-label" for="tvFromAccount">From Account</label><select class="form-select" id="tvFromAccount"><option value="">Select source</option><?php foreach (($transferAccounts ?? []) as $acc): ?><option value="<?php echo (int) ($acc['id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($acc['name'] ?? '')); ?></option><?php endforeach; ?></select></div>
              <div class="col-12 col-md-6"><label class="form-label" for="tvToAccount">To Account</label><select class="form-select" id="tvToAccount"><option value="">Select destination</option><?php foreach (($transferAccounts ?? []) as $acc): ?><option value="<?php echo (int) ($acc['id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($acc['name'] ?? '')); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="row g-3">
              <div class="col-12 col-md-4"><label class="form-label" for="tvAmount">Amount</label><input class="form-control" type="number" min="0" step="0.01" id="tvAmount" placeholder="0.00"></div>
              <div class="col-12 col-md-8"><label class="form-label" for="tvNarration">Narration</label><textarea class="form-control" id="tvNarration" rows="2" placeholder="Purpose of transfer"></textarea></div>
            </div>
            <div class="tv-actions pt-2">
              <button type="button" class="btn btn-primary" onclick="tvSaveDraft()"><i class="bi bi-save me-1"></i> Save Draft</button>
              <button type="button" class="btn btn-success" onclick="tvPostVoucher()"><i class="bi bi-check2-circle me-1"></i> Post Voucher</button>
              <button type="button" class="btn btn-outline-secondary" onclick="tvResetForm()"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</button>
              <button type="button" class="btn btn-outline-danger" onclick="tvCancelDraft()"><i class="bi bi-x-circle me-1"></i> Cancel Draft</button>
            </div>
            <div class="small tv-muted" id="tvStatusMessage">Ready to create a transfer voucher.</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-xl-7">
        <div class="tv-panel p-3 p-lg-4 mb-3">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div><div class="tv-label">History</div><h2 class="h5 mb-0">Voucher register</h2></div>
            <div class="tv-actions">
              <input type="search" class="form-control form-control-sm" id="tvSearch" placeholder="Search voucher no. or account" value="<?php echo htmlspecialchars($tvQuery); ?>">
              <select class="form-select form-select-sm" id="tvStatusFilter"><option value="">All statuses</option><option value="DRAFT" <?php echo $tvStatus === 'DRAFT' ? 'selected' : ''; ?>>Draft</option><option value="POSTED" <?php echo $tvStatus === 'POSTED' ? 'selected' : ''; ?>>Posted</option><option value="CANCELLED" <?php echo $tvStatus === 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option></select>
              <input type="date" class="form-control form-control-sm" id="tvFromDate" value="<?php echo htmlspecialchars($tvFrom); ?>">
              <input type="date" class="form-control form-control-sm" id="tvToDate" value="<?php echo htmlspecialchars($tvTo); ?>">
              <button type="button" class="btn btn-outline-primary btn-sm" onclick="tvLoadList(1)"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle tv-table mb-0">
              <thead><tr><th>Voucher No.</th><th>Date</th><th>Accounts</th><th class="text-end">Amount</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="tvListBody">
                <?php if (empty($voucherRows)): ?><tr><td colspan="6" class="text-center text-muted py-4">No transfer vouchers found.</td></tr><?php else: ?><?php foreach ($voucherRows as $row): ?><?php $status = (string) ($row['status'] ?? 'DRAFT'); ?><tr><td class="fw-semibold"><?php echo htmlspecialchars((string) ($row['voucher_number'] ?? $row['voucher_no'] ?? '')); ?></td><td><?php echo htmlspecialchars((string) ($row['voucher_date'] ?? '')); ?></td><td><small class="text-muted"><?php echo htmlspecialchars((string) ($row['accounts_summary'] ?? ($row['from_account_name'] . ' → ' . $row['to_account_name'] ?? ''))); ?></small></td><td class="text-end fw-semibold"><?php echo Helpers::formatMoney((float) ($row['amount'] ?? 0)); ?></td><td><span class="tv-badge tv-<?php echo strtolower($status); ?>"><?php echo htmlspecialchars($status); ?></span></td><td><div class="tv-actions"><a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($baseUrl . 'index.php?page=transfer_voucher&action=entry&vid=' . (int) ($row['id'] ?? 0)); ?>"><i class="bi bi-pencil-square"></i></a><a class="btn btn-outline-dark btn-sm" href="<?php echo htmlspecialchars($baseUrl . 'index.php?page=transfer_voucher&action=print&id=' . (int) ($row['id'] ?? 0)); ?>" target="_blank" rel="noopener"><i class="bi bi-printer"></i></a></div></td></tr><?php endforeach; ?><?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
            <div class="small tv-muted">Showing <?php echo count($voucherRows); ?> of <?php echo (int) $totalRows; ?> vouchers</div>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-outline-secondary" <?php echo $pageNo <= 1 ? 'disabled' : ''; ?> onclick="tvLoadList(<?php echo max(1, $pageNo - 1); ?>)">Previous</button>
              <button type="button" class="btn btn-outline-secondary" disabled>Page <?php echo $pageNo; ?> / <?php echo $totalPages; ?></button>
              <button type="button" class="btn btn-outline-secondary" <?php echo $pageNo >= $totalPages ? 'disabled' : ''; ?> onclick="tvLoadList(<?php echo min($totalPages, $pageNo + 1); ?>)">Next</button>
            </div>
          </div>
        </div>
        <div class="tv-panel p-3 p-lg-4">
          <div class="d-flex justify-content-between align-items-center mb-3"><div><div class="tv-label">Audit trail</div><h2 class="h5 mb-0">Recent actions</h2></div></div>
          <?php if (empty($tvRecentAuditLogs)): ?><div class="text-muted small">No audit events yet.</div><?php else: ?><div class="tv-side-list"><div class="list-group list-group-flush"><?php foreach ($tvRecentAuditLogs as $log): ?><div class="list-group-item px-0 d-flex justify-content-between gap-3"><div><div class="fw-semibold text-capitalize"><?php echo htmlspecialchars((string) ($log['action'] ?? '')); ?></div><div class="small tv-muted">Voucher: <?php echo htmlspecialchars((string) ($log['entity_id'] ?? '')); ?></div></div><div class="text-end small tv-muted"><div><?php echo htmlspecialchars((string) ($log['user_name'] ?? $log['user_username'] ?? 'System')); ?></div><div><?php echo htmlspecialchars((string) ($log['created_at'] ?? '')); ?></div></div></div><?php endforeach; ?></div></div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="tvCsrf" value="<?php echo htmlspecialchars($csrf); ?>">

<script>
const tvBaseUrl = <?php echo json_encode($baseUrl); ?>;
function tvSetMessage(message, kind = 'muted') { const el = document.getElementById('tvStatusMessage'); if (el) { el.className = 'small tv-' + kind; el.textContent = message; } }
function tvCurrentPayload() { return new URLSearchParams({ tv_action: 'save_voucher', csrf_token: document.getElementById('tvCsrf').value, id: document.getElementById('tvId').value || '0', voucher_date: document.getElementById('tvVoucherDate').value, from_account_id: document.getElementById('tvFromAccount').value, to_account_id: document.getElementById('tvToAccount').value, amount: document.getElementById('tvAmount').value, reference_number: document.getElementById('tvReferenceNo').value, narration: document.getElementById('tvNarration').value }); }
async function tvSaveDraft() { tvSetMessage('Saving draft...'); const body = tvCurrentPayload(); const response = await fetch(tvBaseUrl + 'index.php?page=transfer_voucher', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body: body.toString() }); const data = await response.json(); if (!data.ok) { tvSetMessage(data.error || 'Unable to save voucher.', 'danger'); return; } tvFillVoucher(data.data); tvSetMessage('Draft saved successfully.', 'success'); tvLoadList(1); }
async function tvPostVoucher() { tvSetMessage('Posting voucher...'); const body = tvCurrentPayload(); body.set('tv_action', 'post_voucher'); const response = await fetch(tvBaseUrl + 'index.php?page=transfer_voucher', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body: body.toString() }); const data = await response.json(); if (!data.ok) { tvSetMessage(data.error || 'Unable to post voucher.', 'danger'); return; } tvFillVoucher(data.data); tvSetMessage('Voucher posted and linked to the cashbook transfer engine.', 'success'); tvLoadList(1); }
async function tvCancelDraft() { const id = document.getElementById('tvId').value; if (!id || id === '0') { tvSetMessage('Save a draft first before cancelling.', 'warning'); return; } const reason = prompt('Cancellation reason (optional):', ''); const body = new URLSearchParams({ tv_action: 'cancel_voucher', csrf_token: document.getElementById('tvCsrf').value, id: id, reason: reason || '' }); const response = await fetch(tvBaseUrl + 'index.php?page=transfer_voucher', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body: body.toString() }); const data = await response.json(); if (!data.ok) { tvSetMessage(data.error || 'Unable to cancel voucher.', 'danger'); return; } tvFillVoucher(data.data); tvSetMessage('Draft cancelled.', 'warning'); tvLoadList(1); }
function tvFillVoucher(voucher) { document.getElementById('tvId').value = voucher.id || 0; document.getElementById('tvVoucherNo').value = voucher.voucher_no || ''; document.getElementById('tvVoucherDate').value = voucher.voucher_date || '<?php echo htmlspecialchars($tvToday); ?>'; document.getElementById('tvFromAccount').value = voucher.from_account_id || ''; document.getElementById('tvToAccount').value = voucher.to_account_id || ''; document.getElementById('tvAmount').value = voucher.amount || ''; document.getElementById('tvReferenceNo').value = voucher.reference_number || ''; document.getElementById('tvNarration').value = voucher.narration || ''; const status = (voucher.status || 'DRAFT').toUpperCase(); const badge = document.getElementById('tvFormStatusBadge'); badge.textContent = status; badge.className = 'tv-badge tv-' + status.toLowerCase(); }
function tvResetForm() { document.getElementById('tvId').value = '0'; document.getElementById('tvVoucherNo').value = 'TRF-<?php echo date('Y'); ?>-000001'; document.getElementById('tvVoucherDate').value = '<?php echo htmlspecialchars($tvToday); ?>'; document.getElementById('tvFromAccount').value = ''; document.getElementById('tvToAccount').value = ''; document.getElementById('tvAmount').value = ''; document.getElementById('tvReferenceNo').value = ''; document.getElementById('tvNarration').value = ''; const badge = document.getElementById('tvFormStatusBadge'); badge.textContent = 'DRAFT'; badge.className = 'tv-badge tv-draft'; tvSetMessage('Ready to create a transfer voucher.'); }
async function tvEditVoucher(id) { const response = await fetch(tvBaseUrl + 'index.php?page=transfer_voucher&tv_action=get_voucher&id=' + encodeURIComponent(id)); const data = await response.json(); if (!data.ok) { tvSetMessage(data.error || 'Unable to load voucher.', 'danger'); return; } tvFillVoucher(data.data); tvSetMessage('Voucher loaded. You can update the draft before posting.', 'muted'); }
function tvExportCsv() { const params = new URLSearchParams({ tv_action: 'export_csv', status: document.getElementById('tvStatusFilter').value, from_date: document.getElementById('tvFromDate').value, to_date: document.getElementById('tvToDate').value, q: document.getElementById('tvSearch').value }); window.location.href = tvBaseUrl + 'index.php?page=transfer_voucher&' + params.toString(); }
function tvLoadList(page = 1) { const params = new URLSearchParams({ page_no: String(page), status: document.getElementById('tvStatusFilter').value, from_date: document.getElementById('tvFromDate').value, to_date: document.getElementById('tvToDate').value, q: document.getElementById('tvSearch').value }); window.location.href = tvBaseUrl + 'index.php?page=transfer_voucher&' + params.toString(); }
</script>