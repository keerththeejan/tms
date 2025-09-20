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
        <label class="form-label d-flex justify-content-between align-items-center">
          <span>Expense Type</span>
          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddExpType" aria-expanded="false"><i class="bi bi-plus-lg"></i> Quick Add</button>
        </label>
        <select name="expense_type" id="expenseType" class="form-select">
          <?php 
            $builtin = ['fuel'=>'Fuel','vehicle_maintenance'=>'Vehicle Maintenance','office'=>'Office','utilities'=>'Utilities','other'=>'Other'];
            $current = (string)($expense['expense_type'] ?? 'other');
            foreach ($builtin as $k=>$v): ?>
              <option value="<?php echo htmlspecialchars($k); ?>" <?php echo strcasecmp($current,$k)===0?'selected':''; ?>><?php echo $v; ?></option>
            <?php endforeach; 
            // Render dynamic types from DB, avoid duplicates with built-ins
            $rendered = array_fill_keys(array_keys($builtin), true);
            if (!empty($typesDynamic) && is_array($typesDynamic)) {
              foreach ($typesDynamic as $row) {
                $t = trim((string)($row['expense_type'] ?? ''));
                if ($t === '' || isset($rendered[$t])) continue;
                $rendered[$t] = true;
                $label = ucwords(str_replace('_',' ', $t));
          ?>
                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo strcasecmp($current,$t)===0?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
          <?php 
              }
            }
            // If current is custom and not in lists yet
            if ($current !== '' && !isset($rendered[$current])) {
              $label = ucwords(str_replace('_',' ', $current));
          ?>
              <option value="<?php echo htmlspecialchars($current); ?>" selected><?php echo htmlspecialchars($label); ?></option>
          <?php } ?>
        </select>
        <div class="collapse mt-2" id="quickAddExpType">
          <div class="border rounded p-2 bg-light">
            <div class="row g-2">
              <div class="col-8"><input type="text" id="et_new" class="form-control form-control-sm" placeholder="e.g., Parking or Toll"></div>
              <div class="col-4 text-end"><button type="button" id="et_add" class="btn btn-sm btn-primary"><i class="bi bi-check2"></i> Add & Select</button></div>
            </div>
            <div class="form-text">Tip: Use a clear label. It will appear in the list next time automatically.</div>
          </div>
        </div>
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
<script>
(function(){
  // Quick Add for Expense Type: client-side add, server will accept any string on save
  const addBtn = document.getElementById('et_add');
  const input = document.getElementById('et_new');
  const select = document.getElementById('expenseType');
  addBtn?.addEventListener('click', function(){
    if (!input || !select) return;
    const raw = (input.value || '').trim();
    if (!raw) { alert('Enter an expense type'); return; }
    // Check if already exists (case-insensitive)
    let exists = false; let existingVal = '';
    Array.from(select.options).forEach(o=>{ if ((o.text||'').toLowerCase() === raw.toLowerCase() || (o.value||'').toLowerCase() === raw.toLowerCase()) { exists = true; existingVal = o.value; } });
    if (!exists) {
      const opt = document.createElement('option');
      opt.value = raw; // store raw label as value
      opt.textContent = raw;
      select.appendChild(opt);
      existingVal = raw;
    }
    select.value = existingVal;
    input.value = '';
    const collapseEl = document.getElementById('quickAddExpType'); if (collapseEl && window.bootstrap) new bootstrap.Collapse(collapseEl, {toggle:true});
  });
})();
</script>
