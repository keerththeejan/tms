<?php /** @var array $files */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Database Backups</h3>
  <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=backup&action=create'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <button type="submit" class="btn btn-primary"><i class="bi bi-hdd"></i> Create Backup</button>
  </form>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo $error; ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success py-2"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">Existing Backups</div>
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
              <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=backup&action=download&file=' . urlencode($f['name'])); ?>"><i class="bi bi-download"></i> Download</a>
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
