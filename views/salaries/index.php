<?php /** @var array $rows */ /** @var int $year */ /** @var int $month_num */ ?>
<div class="hr-page container-fluid px-0">
<div class="hr-toolbar card shadow-sm rounded-3 border-0 d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-start gap-3 mb-2 p-3">
  <h3 class="mb-0">Salaries</h3>
  <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=salaries&action=generate'); ?>" class="salaries-gen-form w-100" style="max-width: 22rem;">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <div class="input-group">
      <input type="number" name="year" class="form-control" value="<?php echo (int)$year; ?>" style="max-width:120px">
      <input type="number" name="month_num" class="form-control" value="<?php echo (int)$month_num; ?>" style="max-width:100px">
      <button class="btn btn-primary"><i class="bi bi-gear"></i> Generate</button>
    </div>
  </form>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="salaries">
  <div class="col-6 col-md-2">
    <input type="number" name="year" class="form-control" placeholder="Year" value="<?php echo ((int)$year > 0 ? (int)$year : ''); ?>">
  </div>
  <div class="col-6 col-md-2">
    <input type="number" name="month_num" class="form-control" placeholder="Month (1-12)" value="<?php echo ((int)$month_num > 0 ? (int)$month_num : ''); ?>">
  </div>
  <div class="col-12 col-md-4">
    <select name="branch_id" class="form-select">
      <option value="0">All Branches</option>
      <?php foreach (($branchesAll ?? []) as $b): ?>
        <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($branchFilter ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-3">
    <input type="text" name="employee" class="form-control" placeholder="Employee" value="<?php echo htmlspecialchars($employeeFilter ?? ''); ?>">
  </div>
  <div class="col-12 col-md-3">
    <input type="text" name="position" class="form-control" placeholder="Position" value="<?php echo htmlspecialchars($positionFilter ?? ''); ?>">
  </div>
  <div class="col-6 col-md-2">
    <input type="date" name="pay_from" class="form-control" value="<?php echo htmlspecialchars($payFrom ?? ''); ?>">
    <div class="form-text">Paid From</div>
  </div>
  <div class="col-6 col-md-2">
    <input type="date" name="pay_to" class="form-control" value="<?php echo htmlspecialchars($payTo ?? ''); ?>">
    <div class="form-text">Paid To</div>
  </div>
  <div class="col-12 col-sm-auto d-flex flex-wrap gap-2">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=salaries'); ?>">Clear</a>
  </div>
  <div class="col-12 small text-muted">
    Tip: You can fill any one or more fields. Leave blank to show the latest 200 entries.
  </div>
</form>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div>Total: <strong><?php echo number_format((float)$total, 2); ?></strong></div>
    <div>Paid: <strong class="text-success"><?php echo number_format((float)$paid, 2); ?></strong></div>
    <div>Pending: <strong class="text-danger"><?php echo number_format((float)max(0, $total-$paid), 2); ?></strong></div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-12 col-sm-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted">Rows (Employees)</div>
        <div class="display-6 fw-bold"><?php echo (int)($countTotal ?? count($rows ?? [])); ?></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted">Paid Count</div>
        <div class="display-6 fw-bold text-success"><?php echo (int)($countPaid ?? ($statusCounts['paid'] ?? 0)); ?></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted">Pending Count</div>
        <div class="display-6 fw-bold text-danger"><?php echo (int)($countPending ?? ($statusCounts['pending'] ?? 0)); ?></div>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">Totals by Branch (Current Filters)</h6>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Branch</th>
                <th class="text-end">Total</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Pending</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($byBranchTotals ?? []) as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['branch_name'] ?? ''); ?></td>
                <td class="text-end"><?php echo number_format((float)($r['total'] ?? 0), 2); ?></td>
                <td class="text-end text-success"><?php echo number_format((float)($r['paid'] ?? 0), 2); ?></td>
                <td class="text-end text-danger"><?php echo number_format((float)($r['pending'] ?? 0), 2); ?></td>
              </tr>
              <?php endforeach; if (empty($byBranchTotals ?? [])): ?>
                <tr><td colspan="4" class="text-muted">No data for current filters.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">Last 6 Months Trend<?php echo ((int)($branchFilter ?? 0) > 0 ? ' (Selected Branch)' : ''); ?></h6>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Month</th>
                <th class="text-end">Total</th>
                <th class="text-end">Paid</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($trend ?? []) as $t): $m = sprintf('%04d-%02d', (int)$t['month'], (int)$t['month_num']); ?>
              <tr>
                <td><?php echo htmlspecialchars($m); ?></td>
                <td class="text-end"><?php echo number_format((float)($t['total'] ?? 0), 2); ?></td>
                <td class="text-end text-success"><?php echo number_format((float)($t['paid'] ?? 0), 2); ?></td>
              </tr>
              <?php endforeach; if (empty($trend ?? [])): ?>
                <tr><td colspan="3" class="text-muted">No data.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="hr-table-wrap table-responsive">
  <table class="table table-sm table-striped align-middle" id="salariesTable">
    <thead>
      <tr>
        <th>#</th>
        <th>Employee</th>
        <th class="d-none d-md-table-cell">Position</th>
        <th class="d-none d-md-table-cell">Branch</th>
        <th class="d-none d-lg-table-cell">Employee Salary</th>
        <th>Amount</th>
        <th>Status</th>
        <th class="d-none d-sm-table-cell">Payment Date</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['employee_name'] ?? ''); ?></td>
          <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($r['position'] ?? ''); ?></td>
          <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($r['branch_name'] ?? ''); ?></td>
          <td class="d-none d-lg-table-cell"><?php echo number_format((float)($r['employee_salary'] ?? 0), 2); ?></td>
          <td><?php echo number_format((float)$r['amount'], 2); ?></td>
          <td><?php echo $r['status']==='paid' ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning text-dark">Pending</span>'; ?></td>
          <td class="d-none d-sm-table-cell"><?php echo htmlspecialchars($r['payment_date'] ?? ''); ?></td>
          <td class="text-end">
            <?php if ($r['status'] !== 'paid'): ?>
              <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=salaries&action=pay'); ?>" class="hr-salary-pay ms-md-auto">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                <input type="date" name="payment_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-cash"></i> Mark Paid</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize DataTable for salaries
  if (typeof $ !== 'undefined' && $.fn.DataTable) {
    $('#salariesTable').DataTable({
      responsive: true,
      pageLength: 25,
      order: [[0, 'desc']], // Sort by ID column descending
      columnDefs: [
        { targets: [5], className: 'text-end' },
        { targets: [7], type: 'date' },
        { targets: [8], orderable: false }
      ]
    });
  }
});
</script>
</div><!-- /.hr-page -->
