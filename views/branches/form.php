<?php /** @var array $branch */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo $branch['id'] ? 'Edit Branch' : 'New Branch'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=branches'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=branches&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$branch['id']; ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($branch['name']); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Code</label>
        <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($branch['code']); ?>">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="is_main" name="is_main" <?php echo !empty($branch['is_main']) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="is_main">Main Branch</label>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
