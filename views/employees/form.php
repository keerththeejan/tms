<?php /** @var array $employee */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo $employee['id'] ? 'Edit Employee' : 'New Employee'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=employees&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$employee['id']; ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($employee['name']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Position</label>
        <input type="text" name="position" class="form-control" required value="<?php echo htmlspecialchars($employee['position']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Salary Amount</label>
        <input type="number" step="0.01" name="salary_amount" class="form-control" required value="<?php echo htmlspecialchars((string)$employee['salary_amount']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select" required>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($employee['branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
