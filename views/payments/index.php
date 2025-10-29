<?php /** @var array $dues */ /** @var array $payments */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Payments</h3>
</div>

<!-- Tabs for switching between Due Collections and Payment History -->
<ul class="nav nav-tabs mb-3" id="paymentTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="dues-tab" data-bs-toggle="tab" data-bs-target="#dues" type="button" role="tab">Due Collections</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">Payment History</button>
  </li>
</ul>

<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="payments">
  <?php if (!empty($isMain)): ?>
    <div class="col-md-3">
      <select name="branch_id" class="form-select">
        <option value="0" <?php echo (int)($branchFilterId ?? 0) === 0 ? 'selected' : ''; ?>>All Branches</option>
        <?php foreach (($branchesAll ?? []) as $b): ?>
          <option value="<?php echo (int)$b['id']; ?>" <?php echo (int)($branchFilterId ?? 0) === (int)$b['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>
  <div class="col-md-3">
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  </div>
  <div class="col-md-4">
    <input type="text" class="form-control" name="q" placeholder="Search customer phone or name" value="<?php echo htmlspecialchars($q ?? ''); ?>">
  </div>
  <div class="col-md-2">
    <select class="form-select" name="group">
      <option value="customer" <?php echo ($groupMode ?? 'customer')==='customer'?'selected':''; ?>>Group by Customer</option>
      <option value="dn" <?php echo ($groupMode ?? 'customer')==='dn'?'selected':''; ?>>Show Each Bill</option>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
  </div>
</form>

<div class="tab-content" id="paymentTabContent">
  <!-- Due Collections Tab -->
  <div class="tab-pane fade show active" id="dues" role="tabpanel">
    <?php if (($groupMode ?? 'customer') === 'customer'): ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Phone</th>
              <th class="text-end">Total</th>
              <th class="text-end">Discount</th>
              <th class="text-end">After Discount</th>
              <th class="text-end">Paid</th>
              <th class="text-end">Due</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($dueGroups ?? []) as $g): ?>
              <tr class="table-dark">
                <td><strong><?php echo htmlspecialchars($g['customer_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($g['customer_phone']); ?></td>
                <td class="text-end"><?php 
                    $disc = (float)($g['discount'] ?? 0); // negative for discounts
                    $amountAfterDiscount = (float)$g['total_amount'] + $disc;
                    echo number_format($amountAfterDiscount, 2); 
                ?></td>
                <td class="text-end text-danger"><?php $disc=(float)($g['discount']??0); echo number_format(abs($disc),2); ?></td>
                <td class="text-end"><?php 
                    $disc = (float)($g['discount'] ?? 0);
                    $amountAfterDiscount = isset($g['amount_after_discount']) ? (float)$g['amount_after_discount'] : (float)$g['total_amount'] + $disc;
                    echo number_format($amountAfterDiscount, 2); 
                ?></td>
                <td class="text-end"><?php echo number_format((float)$g['paid'],2); ?></td>
                <td class="text-end"><strong><?php echo number_format((float)$g['due'],2); ?></strong></td>
                <td class="text-end">
                  <!-- no direct collect here since collection is per DN; expand below -->
                </td>
              </tr>
              <?php foreach ($g['bills'] as $n): ?>
                <tr>
                  <td colspan="2" class="ps-4">DN #<?php echo (int)$n['id']; ?> — <?php echo htmlspecialchars($n['delivery_date']); ?></td>
                  <td class="text-end"><?php 
                      $disc = (float)($n['discount'] ?? 0);
                      $amountAfterDiscount = (float)$n['total_amount'] + $disc;
                      echo number_format($amountAfterDiscount, 2); 
                  ?></td>
                  <td class="text-end text-danger"><?php $disc=(float)($n['discount']??0); echo number_format(abs($disc),2); ?></td>
                  <td class="text-end"><?php 
                      $disc = (float)($n['discount'] ?? 0);
                      $amountAfterDiscount = isset($n['amount_after_discount']) ? (float)$n['amount_after_discount'] : (float)$n['total_amount'] + $disc;
                      echo number_format($amountAfterDiscount, 2); 
                  ?></td>
                  <td class="text-end"><?php echo number_format((float)$n['paid'],2); ?></td>
                  <td class="text-end"><strong><?php echo number_format((float)$n['due'],2); ?></strong></td>
                  <td class="text-end">
                    <?php if (!empty($isMain)): ?>
                      <a class="btn btn-sm btn-primary" href="<?php echo Helpers::baseUrl('index.php?page=payments&action=new&id='.(int)$n['id']); ?>"><i class="bi bi-cash-coin"></i> Collect</a>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='.(int)$n['id']); ?>"><i class="bi bi-receipt"></i> View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Customer</th>
              <th>Phone</th>
              <th>Total</th>
              <th>Discount</th>
              <th>After Discount</th>
              <th>Paid</th>
              <th>Due</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dues as $n): ?>
              <tr>
                <td><?php echo (int)$n['id']; ?></td>
                <td><?php echo htmlspecialchars($n['delivery_date']); ?></td>
                <td><?php echo htmlspecialchars($n['customer_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($n['customer_phone'] ?? ''); ?></td>
                <td class="text-end"><?php echo number_format((float)($n['display_total'] ?? $n['total_amount']), 2); ?></td>
                <td class="text-end text-danger"><?php $disc=(float)($n['discount']??0); echo number_format(abs($disc), 2); ?></td>
                <td class="text-end"><?php 
                    $disc = (float)($n['discount'] ?? 0);
                    $amountAfterDiscount = isset($n['amount_after_discount']) ? (float)$n['amount_after_discount'] : (float)$n['total_amount'] + $disc;
                    echo number_format($amountAfterDiscount, 2); 
                ?></td>
                <td class="text-end"><?php echo number_format((float)$n['paid'], 2); ?></td>
                <td class="text-end"><strong><?php echo number_format((float)$n['due'], 2); ?></strong></td>
                <td class="text-end">
                  <?php if (!empty($isMain)): ?>
                    <a class="btn btn-sm btn-primary" href="<?php echo Helpers::baseUrl('index.php?page=payments&action=new&id='.(int)$n['id']); ?>"><i class="bi bi-cash-coin"></i> Collect</a>
                  <?php endif; ?>
                  <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='.(int)$n['id']); ?>"><i class="bi bi-receipt"></i> View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Payment History Tab -->
  <div class="tab-pane fade" id="history" role="tabpanel">
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle" id="paymentsTable">
        <thead>
          <tr>
            <th>Payment ID</th>
            <th>DN #</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Amount</th>
            <th>Paid At</th>
            <th>Branch</th>
            <th>Received By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($payments ?? []) as $p): ?>
            <tr>
              <td><?php echo (int)$p['id']; ?></td>
              <td><?php echo (int)$p['dn_id']; ?></td>
              <td><?php echo htmlspecialchars($p['customer_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($p['customer_phone'] ?? ''); ?></td>
              <td><?php echo number_format((float)$p['amount'], 2); ?></td>
              <td><?php echo htmlspecialchars($p['paid_at']); ?></td>
              <td><?php echo htmlspecialchars($p['branch_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($p['received_by_name'] ?? ''); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize DataTable for payments history
  if (typeof $ !== 'undefined' && $.fn.DataTable) {
    $('#paymentsTable').DataTable({
      responsive: true,
      pageLength: 25,
      order: [[5, 'desc']], // Sort by Paid At column descending
      columnDefs: [
        { targets: [4], className: 'text-end' }, // Amount column right-aligned
        { targets: [5], type: 'date' } // Paid At column as date type
      ]
    });
  }
});
</script>
