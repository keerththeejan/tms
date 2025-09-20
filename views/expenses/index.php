<?php /** @var array $expenses */ /** @var array $byBranch */ /** @var float $overall */ /** @var bool $isAdmin */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Expenses</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=expenses&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Expense</a>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="expenses">
  <div class="col-md-3">
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <select class="form-select" name="branch_id">
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
    <h6 class="mb-2">Branch-wise Totals</h6>
    <?php if (!$byBranch): ?>
      <div class="text-muted">No expenses for selected period.</div>
    <?php else: ?>
      <ul class="mb-0">
        <?php foreach ($byBranch as $r): ?>
          <li>Branch ID <?php echo (int)$r['branch_id']; ?>: <strong><?php echo number_format((float)$r['total'], 2); ?></strong></li>
        <?php endforeach; ?>
      </ul>
      <div class="mt-2">Overall: <strong><?php echo number_format($overall, 2); ?></strong></div>
    <?php endif; ?>
  </div>
</div>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Branch</th>
        <th>Notes</th>
        <th>Approved</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($expenses as $e): ?>
        <tr>
          <td><?php echo (int)$e['id']; ?></td>
          <td><?php echo htmlspecialchars($e['expense_date']); ?></td>
          <td>
            <?php 
              $t = trim((string)($e['expense_type'] ?? ''));
              if ($t === '') { echo 'Other'; }
              else { echo htmlspecialchars(ucwords(str_replace('_',' ', $t))); }
            ?>
          </td>
          <td><?php echo number_format((float)$e['amount'], 2); ?></td>
          <td><?php echo htmlspecialchars($e['branch_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['notes'] ?? ''); ?></td>
          <td><?php echo $e['approved_by'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=expenses&action=edit&id='.(int)$e['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <?php if ($isAdmin): ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=expenses&action=approve'); ?>" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
              <button class="btn btn-sm btn-outline-success"><i class="bi bi-check2-circle"></i> Approve</button>
            </form>
            <?php endif; ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=expenses&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this expense?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
