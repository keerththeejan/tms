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
  <div class="col-md-3">
    <input type="text" class="form-control" name="notes" placeholder="Notes" value="<?php echo htmlspecialchars($notesFilter ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <select class="form-select" name="approved">
      <?php $ap = $approved ?? ''; ?>
      <option value="" <?php echo ($ap==='')?'selected':''; ?>>Approved (any)</option>
      <option value="yes" <?php echo ($ap==='yes')?'selected':''; ?>>Yes</option>
      <option value="no" <?php echo ($ap==='no')?'selected':''; ?>>No</option>
    </select>
  </div>
  <div class="col-md-3">
    <select class="form-select" name="type">
      <?php $tf = $typeFilter ?? ''; ?>
      <option value="" <?php echo ($tf==='')?'selected':''; ?>>Type (any)</option>
      <?php foreach (($typesDynamic ?? []) as $t): $val = trim((string)$t['expense_type'] ?? ''); if ($val==='') continue; ?>
        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($tf===$val)?'selected':''; ?>><?php echo htmlspecialchars(ucwords(str_replace('_',' ', $val))); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <select class="form-select" name="mode">
      <?php $mf = $modeFilter ?? ''; ?>
      <option value="" <?php echo ($mf==='')?'selected':''; ?>>Payment (any)</option>
      <option value="cash" <?php echo ($mf==='cash')?'selected':''; ?>>Cash</option>
      <option value="credit" <?php echo ($mf==='credit')?'selected':''; ?>>Credit</option>
    </select>
  </div>
  <div class="col-md-3">
    <select class="form-select" name="credit_status">
      <?php $cs = $creditStatus ?? ''; ?>
      <option value="" <?php echo ($cs==='')?'selected':''; ?>>Credit Status (any)</option>
      <option value="open" <?php echo ($cs==='open')?'selected':''; ?>>Open</option>
      <option value="settled" <?php echo ($cs==='settled')?'selected':''; ?>>Settled</option>
      <option value="overdue" <?php echo ($cs==='overdue')?'selected':''; ?>>Overdue</option>
    </select>
  </div>
  <div class="col-auto d-flex gap-2">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>">Clear</a>
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
<?php if (isset($cashTotal) && isset($creditTotal) && isset($settlementsTotal)): ?>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <h6 class="mb-2">Totals (Selected Range)</h6>
    <ul class="mb-0">
      <li>Cash Purchases: <strong><?php echo number_format((float)$cashTotal, 2); ?></strong></li>
      <li>Credit Purchases: <strong><?php echo number_format((float)$creditTotal, 2); ?></strong></li>
      <li>Settlements Paid: <strong><?php echo number_format((float)$settlementsTotal, 2); ?></strong></li>
    </ul>
  </div>
  </div>
<?php endif; ?>
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
        <th>Payment</th>
        <th>Paid</th>
        <th>Balance</th>
        <th>Due</th>
        <th>Party</th>
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
          <td>
            <?php 
              $isCreditHint = !empty($e['credit_party'] ?? '') || !empty($e['credit_due_date'] ?? '');
              $pm = $e['mode_effective'] ?? (($e['payment_mode'] ?? '') === 'credit' || $isCreditHint ? 'credit' : 'cash');
              echo $pm==='credit' ? '<span class="badge bg-warning text-dark">Credit</span>' : '<span class="badge bg-info text-dark">Cash</span>';
            ?>
          </td>
          <td><?php echo number_format((float)($e['paid_total'] ?? 0), 2); ?></td>
          <td>
            <?php 
              $bal = (float)($e['balance'] ?? 0);
              $pmEff = $e['mode_effective'] ?? ($pm ?? 'cash');
              $isOver = $pmEff==='credit' && (int)($e['credit_settled'] ?? 0)===0 && !empty($e['credit_due_date']) && (strtotime((string)$e['credit_due_date']) < strtotime(date('Y-m-d')));
              $cls = $bal <= 0.0001 ? 'badge bg-success' : ($isOver ? 'badge bg-danger' : 'badge bg-secondary');
              echo '<span class="'.$cls.'">'.number_format(max(0,$bal),2).'</span>';
            ?>
          </td>
          <td><?php echo htmlspecialchars($e['credit_due_date'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['credit_party'] ?? ''); ?></td>
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
            <?php if (($e['mode_effective'] ?? ($e['payment_mode'] ?? 'cash'))==='credit' && (float)($e['balance'] ?? 0) > 0.0001): ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=expenses&action=settle'); ?>" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
              <?php $balRaw = (float)max(0,$e['balance']); $balVal = number_format($balRaw, 2, '.', ''); ?>
              <input type="number" name="pay_amount" step="0.01" min="0.01" max="<?php echo htmlspecialchars((string)$balVal); ?>" value="<?php echo htmlspecialchars((string)$balVal); ?>" class="form-control form-control-sm d-inline-block" style="width:110px" placeholder="Pay">
              <input type="text" name="pay_notes" class="form-control form-control-sm d-inline-block" style="width:150px" placeholder="Notes">
              <button class="btn btn-sm btn-outline-primary"><i class="bi bi-cash"></i> Settle</button>
            </form>
            <?php endif; ?>
            <?php if (($e['mode_effective'] ?? ($e['payment_mode'] ?? 'cash'))==='cash' && (!empty($e['credit_party'] ?? '') || !empty($e['credit_due_date'] ?? ''))): ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=expenses&action=mark_credit'); ?>" class="d-inline" onsubmit="return confirm('Mark this expense as Credit?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
              <button class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-repeat"></i> Mark Credit</button>
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
