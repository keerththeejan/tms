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

<div class="card border-danger mt-4">
  <div class="card-header bg-danger text-white">Reset All Data</div>
  <div class="card-body">
    <p class="text-muted mb-2">Permanently delete all records from every table (customers, parcels, delivery notes, payments, expenses, employees, users, branches, etc.). You will be logged out. Create a backup first if you may need to restore.</p>
    <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=backup&action=reset_data'); ?>" onsubmit="return confirm('This will delete ALL data. Are you sure?');">
      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
      <label class="form-label">Type <strong>DELETE</strong> to confirm:</label>
      <div class="input-group mb-2" style="max-width: 280px;">
        <input type="text" name="confirm_reset" class="form-control" placeholder="DELETE" autocomplete="off" required>
        <button type="submit" class="btn btn-danger">Delete All Data</button>
      </div>
    </form>
  </div>
</div>
</div>
