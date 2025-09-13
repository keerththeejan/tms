<?php /** @var array $rows */ /** @var int $year */ /** @var int $month_num */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Salaries</h3>
  <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=salaries&action=generate'); ?>">
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
  <div class="col-md-2">
    <input type="number" name="year" class="form-control" value="<?php echo (int)$year; ?>">
  </div>
  <div class="col-md-2">
    <input type="number" name="month_num" class="form-control" value="<?php echo (int)$month_num; ?>">
  </div>
  <div class="col-md-4">
    <select name="branch_id" class="form-select">
      <option value="0">All Branches</option>
      <?php foreach (($branchesAll ?? []) as $b): ?>
        <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($branchFilter ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
  </div>
</form>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div>Total: <strong><?php echo number_format((float)$total, 2); ?></strong></div>
    <div>Paid: <strong class="text-success"><?php echo number_format((float)$paid, 2); ?></strong></div>
    <div>Pending: <strong class="text-danger"><?php echo number_format((float)max(0, $total-$paid), 2); ?></strong></div>
  </div>
</div>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Employee</th>
        <th>Position</th>
        <th>Branch</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Payment Date</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['employee_name']); ?></td>
          <td><?php echo htmlspecialchars($r['position']); ?></td>
          <td><?php echo htmlspecialchars($r['branch_name']); ?></td>
          <td><?php echo number_format((float)$r['amount'], 2); ?></td>
          <td><?php echo $r['status']==='paid' ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning text-dark">Pending</span>'; ?></td>
          <td><?php echo htmlspecialchars($r['payment_date'] ?? ''); ?></td>
          <td class="text-end">
            <?php if ($r['status'] !== 'paid'): ?>
              <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=salaries&action=pay'); ?>" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                <input type="date" name="payment_date" class="form-control form-control-sm d-inline-block" style="width:160px" value="<?php echo date('Y-m-d'); ?>">
                <button class="btn btn-sm btn-primary"><i class="bi bi-cash"></i> Mark Paid</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
