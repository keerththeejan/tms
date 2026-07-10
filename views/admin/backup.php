<?php /** @var array $files */ ?>
<div class="container-fluid px-0">
  <div class="row g-2 mb-2">
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body p-3 d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2">
          <h3 class="h5 mb-0 fw-bold">Database backups</h3>
          <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=backup&action=create'); ?>" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1"><i class="bi bi-hdd" aria-hidden="true"></i><span>Create backup</span></button>
          </form>
        </div>
      </div>
    </div>
  </div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success py-2"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="card shadow-sm rounded-3 border-0 overflow-hidden">
  <div class="card-header bg-white py-2 px-3 fw-semibold">Existing backups</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th>File</th>
            <th class="text-end">Size</th>
            <th>Created</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($files)): ?>
          <?php foreach ($files as $f): ?>
          <tr>
            <td class="text-wrap"><?php echo htmlspecialchars($f['name']); ?></td>
            <td class="text-end"><?php echo number_format(($f['size'] ?? 0) / 1024, 1); ?> KB</td>
            <td><?php echo date('Y-m-d H:i:s', (int)$f['mtime']); ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" href="<?php echo Helpers::baseUrl('index.php?page=backup&action=download&file=' . urlencode($f['name'])); ?>"><i class="bi bi-download" aria-hidden="true"></i><span>Download</span></a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4" class="text-center text-muted py-3">No backups yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card border-danger mt-4 shadow-sm">
  <div class="card-header bg-danger text-white d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
    <span>Reset All Data</span>
  </div>
  <div class="card-body">
    <p class="text-muted mb-3">Permanently delete all business and accounting data from the system. You will be logged out after the reset completes. Create a backup first if you may need to restore your data.</p>
    <button type="button" class="btn btn-danger" id="openDbResetModalBtn" data-bs-toggle="modal" data-bs-target="#dbResetModal">
      <i class="bi bi-trash3 me-1" aria-hidden="true"></i> Delete All Data
    </button>
  </div>
</div>
</div>

<div class="modal fade" id="dbResetModal" tabindex="-1" aria-labelledby="dbResetModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger bg-opacity-10 border-danger">
        <h5 class="modal-title text-danger fw-bold" id="dbResetModalTitle">
          <i class="bi bi-exclamation-octagon-fill me-2" aria-hidden="true"></i>⚠️ Permanent Database Reset
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="dbResetModalClose"></button>
      </div>
      <div class="modal-body">
        <form id="dbResetForm" method="post" action="<?php echo Helpers::baseUrl('index.php?page=backup&action=reset_data'); ?>" onsubmit="return false;">
          <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">

          <p class="mb-2">This operation will permanently delete <strong>ALL business data</strong> from the system.</p>
          <p class="mb-2">This includes:</p>
          <ul class="small mb-3 columns-2" style="column-count: 2;">
            <li>Customers</li>
            <li>Parcels</li>
            <li>Delivery Notes</li>
            <li>Accounting Records</li>
            <li>Journal Entries</li>
            <li>Ledger</li>
            <li>Receipts</li>
            <li>Payments</li>
            <li>Expenses</li>
            <li>Employees</li>
            <li>Users</li>
            <li>Branches</li>
            <li>Reports</li>
            <li>Transaction History</li>
          </ul>
          <div class="alert alert-warning py-2 small mb-3">
            <strong>This action CANNOT be undone.</strong><br>
            Create a backup before continuing if you may need to restore your data.
          </div>

          <label class="form-label fw-semibold" for="dbResetConfirmInput">Type <code>DELETE</code> to continue</label>
          <input type="text" class="form-control" id="dbResetConfirmInput" name="confirm_reset" autocomplete="off" placeholder="DELETE" spellcheck="false">

          <div id="dbResetError" class="alert alert-danger py-2 mt-3 d-none" role="alert"></div>

          <div id="dbResetProgress" class="d-none mt-4 text-center">
            <div class="spinner-border text-danger mb-3" role="status" aria-hidden="true"></div>
            <div class="fw-semibold text-danger" id="dbResetProgressLabel">Preparing...</div>
            <p class="text-muted small mb-0 mt-2">Please wait while the database is being reset. Do not close this window.</p>
          </div>

          <div id="dbResetSuccess" class="d-none mt-4">
            <div class="alert alert-success mb-0">
              <div class="fw-bold mb-1">✅ Database Reset Completed Successfully</div>
              <div>All application records have been permanently removed.</div>
              <div>The system has been reset to a clean installation state.</div>
              <div class="mt-2">You will now be logged out.</div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="dbResetCancelBtn">Cancel</button>
        <button type="button" class="btn btn-danger" id="dbResetDeleteBtn" disabled>
          <i class="bi bi-trash3 me-1" aria-hidden="true"></i> Delete Everything
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  window.TMS_BACKUP_RESET = {
    loginUrl: <?php echo json_encode(Helpers::baseUrl('index.php?page=login&reset=1'), JSON_UNESCAPED_UNICODE); ?>
  };
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/backup-reset.js?v=1'); ?>"></script>
