<?php /** @var array $route */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo ((int)($route['id'] ?? 0) > 0) ? 'Edit Route' : 'New Route'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=routes'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=routes&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)($route['id'] ?? 0); ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Route Name</label>
        <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($route['name'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Notes</label>
        <input type="text" class="form-control" name="notes" value="<?php echo htmlspecialchars($route['notes'] ?? ''); ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
