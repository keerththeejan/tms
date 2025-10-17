<?php /** @var array $advance */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo ($advance['id'] ?? 0) ? 'Edit Advance' : 'New Advance'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=advances'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=advances&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)($advance['id'] ?? 0); ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Employee</label>
        <select name="employee_id" class="form-select" required>
          <option value="">Select employee</option>
          <?php foreach (($employeesAll ?? []) as $emp): ?>
            <option value="<?php echo (int)$emp['id']; ?>" <?php echo ((int)($advance['employee_id'] ?? 0) === (int)$emp['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($emp['name'] ?? ('#'.$emp['id'])); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Amount</label>
        <input type="number" step="0.01" name="amount" class="form-control" required value="<?php echo htmlspecialchars((string)($advance['amount'] ?? '')); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Date</label>
        <input type="date" name="advance_date" class="form-control" required value="<?php echo htmlspecialchars($advance['advance_date'] ?? date('Y-m-d')); ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Purpose</label>
        <input type="text" name="purpose" class="form-control" placeholder="Trip fuel, tolls, food, etc." value="<?php echo htmlspecialchars($advance['purpose'] ?? ''); ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
