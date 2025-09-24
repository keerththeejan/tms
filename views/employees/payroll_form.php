<?php /** @var array $employee */ /** @var array $branchesAll */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">New Payroll Entry</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=employees&action=save_payroll'); ?>" class="row g-3">
  <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
  <input type="hidden" name="id" value="<?php echo (int)($employee['id'] ?? 0); ?>">
  <div class="col-md-6">
    <label class="form-label">Employee</label>
    <select name="employee_id" class="form-select" required>
      <option value="">Select Employee</option>
      <?php 
        $selectedId = isset($selectedEmployeeId) ? (int)$selectedEmployeeId : (int)($employee['employee_id'] ?? 0);
        foreach (($employeesAll ?? []) as $e): $eid=(int)$e['id']; 
      ?>
        <option value="<?php echo $eid; ?>" <?php echo ($eid === $selectedId) ? 'selected' : ''; ?>><?php echo htmlspecialchars(($e['emp_code'] ?? '').' - '.($e['name'] ?? '')); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Month-Year</label>
    <input type="text" name="month_year" id="month_year" maxlength="7" class="form-control" placeholder="YYYY-MM" value="<?php echo htmlspecialchars((string)($employee['month_year'] ?? date('Y-m'))); ?>">
  </div>

  <div class="col-12"><hr class="my-2"><h6 class="mb-0">Payroll</h6></div>
  <div class="col-md-3">
    <label class="form-label">Basic Salary</label>
    <input type="number" step="0.01" name="basic_salary" id="basic_salary" class="form-control" value="<?php echo htmlspecialchars((string)($employee['basic_salary'] ?? '0.00')); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">EPF (Employee)</label>
    <input type="number" step="0.01" name="epf_employee" id="epf_employee" class="form-control" value="<?php echo htmlspecialchars((string)($employee['epf_employee'] ?? '0.00')); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">EPF (Employer)</label>
    <input type="number" step="0.01" name="epf_employer" id="epf_employer" class="form-control" value="<?php echo htmlspecialchars((string)($employee['epf_employer'] ?? '0.00')); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">ETF</label>
    <input type="number" step="0.01" name="etf" id="etf" class="form-control" value="<?php echo htmlspecialchars((string)($employee['etf'] ?? '0.00')); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Allowance</label>
    <input type="number" step="0.01" name="allowance" id="allowance" class="form-control" value="<?php echo htmlspecialchars((string)($employee['allowance'] ?? '0.00')); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Deductions</label>
    <input type="number" step="0.01" name="deductions" id="deductions" class="form-control" value="<?php echo htmlspecialchars((string)($employee['deductions'] ?? '0.00')); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Net Salary (auto)</label>
    <input type="text" id="net_salary_preview" class="form-control" value="" readonly>
  </div>
  <div class="col-12">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save Payroll</button>
  </div>
</form>

<script>
(function(){
  function toNum(v){ const n = parseFloat(v); return isNaN(n)?0:n; }
  function updateNet(){
    const b = toNum(document.getElementById('basic_salary')?.value);
    const al = toNum(document.getElementById('allowance')?.value);
    const de = toNum(document.getElementById('deductions')?.value);
    const epfe = toNum(document.getElementById('epf_employee')?.value);
    const net = (b + al - de - epfe).toFixed(2);
    const out = document.getElementById('net_salary_preview'); if (out) out.value = net;
  }
  ['basic_salary','allowance','deductions','epf_employee'].forEach(id=>{
    const el = document.getElementById(id); if (el) el.addEventListener('input', updateNet);
  });
  updateNet();
})();
</script>
