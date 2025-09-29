<?php /** @var array $rows */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Customer Invoice Summary</h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="delivery_notes">
  <input type="hidden" name="action" value="customer_summary">
  <div class="col-md-3">
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  </div>
  <div class="col-md-4">
    <input type="text" class="form-control" name="q" placeholder="Search customer phone or name" value="<?php echo htmlspecialchars($q ?? ''); ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Customer</th>
        <th>Phone</th>
        <th class="text-center">Invoices</th>
        <th class="text-end">Total Amount</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=0; foreach (($rows ?? []) as $r): $i++; ?>
      <tr>
        <td><?php echo $i; ?></td>
        <td><?php echo htmlspecialchars($r['customer_name'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($r['customer_phone'] ?? ''); ?></td>
        <td class="text-center"><?php echo (int)($r['invoices_count'] ?? 0); ?></td>
        <td class="text-end"><?php echo number_format((float)($r['total_amount'] ?? 0), 2); ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=search&phone=' . urlencode($r['customer_phone'] ?? '')); ?>" target="_blank"><i class="bi bi-person"></i> View</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
