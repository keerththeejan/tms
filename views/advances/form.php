<?php /** @var array $advance */ ?>
<?php
$advance = is_array($advance ?? null) ? $advance : [];
$advance += ['id'=>0,'employee_id'=>0,'amount'=>'','advance_date'=>date('Y-m-d'),'purpose'=>''];
$advCssPath = dirname(__DIR__, 2) . '/public/assets/css/advances-module.css';
$advCssVer = is_file($advCssPath) ? (string) filemtime($advCssPath) : '1';
$advNo = 'ADV-' . str_pad((string)((int)($advance['id'] ?? 0) ?: 0), 5, '0', STR_PAD_LEFT);
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/advances-module.css?v=' . rawurlencode($advCssVer)); ?>">

<div id="advancesApp" class="avm-app container-fluid px-0">
  <section class="avm-hero mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h1 class="avm-title mb-0"><?php echo ($advance['id'] ?? 0) ? 'Edit Advance' : 'New Advance'; ?></h1>
        <p class="avm-subtitle">Create and manage payroll advance requests with recovery tracking.</p>
      </div>
      <a href="<?php echo Helpers::baseUrl('index.php?page=advances'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
  </section>

  <?php if (!empty($error)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if (empty($employeesAll)): ?><div class="alert alert-warning">No employees found. <a href="<?php echo Helpers::baseUrl('index.php?page=employees&action=new'); ?>">Add an employee</a> before recording an advance.</div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8">
      <form id="avmForm" method="post" action="<?php echo Helpers::baseUrl('index.php?page=advances&action=save'); ?>" class="avm-card">
        <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
        <input type="hidden" name="id" value="<?php echo (int)($advance['id'] ?? 0); ?>">

        <div class="avm-form-sec">
          <div class="avm-form-title">Section 1 - Advance Information</div>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Advance Number</label><input id="avmNo" class="form-control" value="<?php echo htmlspecialchars($advNo); ?>" readonly></div>
            <div class="col-md-8"><label class="form-label">Employee <span class="text-danger">*</span></label><select name="employee_id" class="form-select" required data-enhance="false"><option value="">Select employee</option><?php foreach (($employeesAll ?? []) as $emp): ?><option value="<?php echo (int)$emp['id']; ?>" <?php echo ((int)($advance['employee_id'] ?? 0) === (int)$emp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['name'] ?? ('#'.$emp['id'])); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Department</label><input class="form-control" placeholder="Auto / preview"></div>
            <div class="col-md-4"><label class="form-label">Branch</label><input class="form-control" placeholder="Auto / preview"></div>
            <div class="col-md-4"><label class="form-label">Advance Date <span class="text-danger">*</span></label><input type="date" name="advance_date" class="form-control" required value="<?php echo htmlspecialchars($advance['advance_date'] ?? date('Y-m-d')); ?>"></div>
            <div class="col-md-4"><label class="form-label">Status</label><input class="form-control" value="Pending"></div>
          </div>
        </div>

        <div class="avm-form-sec">
          <div class="avm-form-title">Section 2 - Financial Details</div>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Advance Amount <span class="text-danger">*</span></label><input type="number" step="0.01" name="amount" class="form-control" required value="<?php echo htmlspecialchars((string)($advance['amount'] ?? '')); ?>"></div>
            <div class="col-md-4"><label class="form-label">Recovered Amount</label><input class="form-control" value="0.00" readonly></div>
            <div class="col-md-4"><label class="form-label">Outstanding Balance</label><input class="form-control" value="<?php echo htmlspecialchars((string)($advance['amount'] ?? '0.00')); ?>" readonly></div>
            <div class="col-md-4"><label class="form-label">Payment Method</label><select class="form-select"><option>Cash</option><option>Bank Transfer</option></select></div>
            <div class="col-md-4"><label class="form-label">Reference Number</label><input class="form-control" placeholder="Ref no"></div>
          </div>
        </div>

        <div class="avm-form-sec">
          <div class="avm-form-title">Section 3 - Recovery Information</div>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Recovery Start Date</label><input type="date" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Installment Amount</label><input class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Recovery Frequency</label><select class="form-select"><option>Monthly</option><option>Weekly</option></select></div>
            <div class="col-md-4"><label class="form-label">Expected Completion Date</label><input type="date" class="form-control"></div>
          </div>
        </div>

        <div class="avm-form-sec">
          <div class="avm-form-title">Section 4 - Additional Information</div>
          <div class="row g-3">
            <div class="col-12"><label class="form-label">Purpose</label><input type="text" name="purpose" class="form-control" placeholder="Trip fuel, tolls, food, etc." value="<?php echo htmlspecialchars($advance['purpose'] ?? ''); ?>"></div>
            <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" rows="2"></textarea></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="2"></textarea></div>
          </div>
        </div>

        <div class="avm-form-sec d-flex justify-content-end gap-2">
          <a href="<?php echo Helpers::baseUrl('index.php?page=advances'); ?>" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Advance</button>
        </div>
      </form>
    </div>
    <div class="col-lg-4">
      <aside class="avm-card avm-summary p-3">
        <h2 class="h6 fw-bold mb-2"><i class="bi bi-graph-up me-1 text-success"></i>Advance Summary</h2>
        <div class="avm-summary-row"><span>Employee</span><strong id="avmSumEmployee">—</strong></div>
        <div class="avm-summary-row"><span>Advance No</span><strong id="avmSumNo"><?php echo htmlspecialchars($advNo); ?></strong></div>
        <div class="avm-summary-row"><span>Advance Amount</span><strong id="avmSumAmount">LKR 0.00</strong></div>
        <div class="avm-summary-row"><span>Recovered</span><strong id="avmSumRecovered">LKR 0.00</strong></div>
        <div class="avm-summary-row"><span>Outstanding</span><strong id="avmSumOutstanding">LKR 0.00</strong></div>
        <div class="avm-summary-row"><span>Date</span><strong id="avmSumDate">—</strong></div>
        <div class="avm-summary-row"><span>Purpose</span><strong id="avmSumPurpose">—</strong></div>
        <div class="mt-2 small text-muted">Recovery Progress</div>
        <div class="progress mt-1"><div id="avmProgressBar" class="progress-bar bg-success" style="width:0%">0%</div></div>
      </aside>
    </div>
  </div>
</div>
