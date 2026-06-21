<?php /** @var array $advances */ /** @var array $employeesAll */ ?>
<div class="hr-page container-fluid px-0">
<div class="hr-toolbar card shadow-sm rounded-3 border-0 d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-2 mb-2 p-3">
  <h3 class="mb-0">Employee Advances</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=advances&action=new'); ?>" class="btn btn-primary w-100 w-md-auto"><i class="bi bi-plus-lg"></i> New Advance</a>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="advances">
  <div class="col-6 col-md-3">
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3">
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  </div>
  <div class="col-12 col-md-3">
    <select class="form-select" name="employee_id" data-enhance="false">
      <option value="0">All Employees</option>
      <?php foreach (($employeesAll ?? []) as $emp): ?>
        <option value="<?php echo (int)$emp['id']; ?>" <?php echo ((int)($empFilter ?? 0) === (int)$emp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['name'] ?? ('#'.$emp['id'])); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=advances'); ?>">Clear</a>
  </div>
</form>
<?php if (empty($advances)): ?>
<div class="alert alert-light border text-center py-4 mb-3">
  <i class="bi bi-cash-stack display-6 d-block mb-2 opacity-50" aria-hidden="true"></i>
  <div class="fw-semibold mb-1">No advances in this period</div>
  <p class="text-muted small mb-3">Record staff cash advances here, then settle them when repaid.</p>
  <?php if (empty($employeesAll)): ?>
    <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=new'); ?>"><i class="bi bi-person-plus"></i> Add employee first</a>
  <?php else: ?>
    <a class="btn btn-sm btn-primary" href="<?php echo Helpers::baseUrl('index.php?page=advances&action=new'); ?>"><i class="bi bi-plus-lg"></i> New Advance</a>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="hr-table-wrap card shadow-sm rounded-3 border-0 overflow-hidden">
  <div class="card-body p-0">
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle mb-0">
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Employee</th>
        <th>Amount</th>
        <th>Paid</th>
        <th>Balance</th>
        <th>Purpose</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($advances as $a): ?>
        <tr>
          <td><?php echo (int)$a['id']; ?></td>
          <td><?php echo htmlspecialchars($a['advance_date']); ?></td>
          <td><?php echo htmlspecialchars($a['employee_name'] ?? ''); ?></td>
          <td><?php echo number_format((float)$a['amount'], 2); ?></td>
          <td><?php echo number_format((float)($a['paid_total'] ?? 0), 2); ?></td>
          <td>
            <?php $bal = (float)max(0, ($a['balance'] ?? 0)); $cls = $bal<=0.0001 ? 'badge bg-success' : 'badge bg-secondary'; ?>
            <span class="<?php echo $cls; ?>"><?php echo number_format($bal, 2); ?></span>
          </td>
          <td><?php echo htmlspecialchars($a['purpose'] ?? ''); ?></td>
          <td class="text-end hr-adv-actions">
            <div class="hr-adv-actions-inner">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=advances&action=edit&id='.(int)$a['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <?php if ((float)$bal > 0.0001): ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=advances&action=settle'); ?>" class="d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch justify-content-end">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
              <?php $balVal = number_format($bal, 2, '.', ''); ?>
              <input type="number" name="pay_amount" step="0.01" min="0.01" max="<?php echo htmlspecialchars((string)$balVal); ?>" value="<?php echo htmlspecialchars((string)$balVal); ?>" class="form-control form-control-sm adv-pay-amt" placeholder="Pay">
              <input type="text" name="pay_notes" class="form-control form-control-sm adv-pay-notes" placeholder="Notes">
              <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-cash"></i> Settle</button>
            </form>
            <?php endif; ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=advances&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this advance?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
  </div>
</div>
<?php endif; ?>
</div><!-- /.hr-page -->
