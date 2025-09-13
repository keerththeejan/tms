<?php /** @var array $supplier */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo $supplier['id'] ? 'Edit Supplier' : 'New Supplier'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$supplier['id']; ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Supplier Name</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($supplier['name']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($supplier['phone']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select" required>
          <option value="">-- Select Branch --</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($supplier['branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Supplier Code (Optional)</label>
        <input type="text" name="supplier_code" class="form-control" value="<?php echo htmlspecialchars($supplier['supplier_code']); ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
