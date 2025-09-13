<?php /** @var array $expense */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo $expense['id'] ? 'Edit Expense' : 'New Expense'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=expenses&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$expense['id']; ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Expense Type</label>
        <select name="expense_type" class="form-select">
          <?php $types=['fuel'=>'Fuel','vehicle_maintenance'=>'Vehicle Maintenance','office'=>'Office','utilities'=>'Utilities','other'=>'Other']; foreach ($types as $k=>$v): ?>
            <option value="<?php echo $k; ?>" <?php echo ($expense['expense_type'] ?? '')===$k?'selected':''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Amount</label>
        <input type="number" step="0.01" name="amount" class="form-control" required value="<?php echo htmlspecialchars((string)$expense['amount']); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Date</label>
        <input type="date" name="expense_date" class="form-control" required value="<?php echo htmlspecialchars($expense['expense_date']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select" required>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($expense['branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Notes</label>
        <input type="text" name="notes" class="form-control" value="<?php echo htmlspecialchars($expense['notes'] ?? ''); ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
