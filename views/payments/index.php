<?php /** @var array $dues */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Due Collections</h3>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="payments">
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
        <th>Date</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Total</th>
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
          <td><?php echo number_format((float)$n['total_amount'], 2); ?></td>
          <td><?php echo number_format((float)$n['paid'], 2); ?></td>
          <td><strong><?php echo number_format((float)$n['due'], 2); ?></strong></td>
          <td class="text-end">
            <a class="btn btn-sm btn-primary" href="<?php echo Helpers::baseUrl('index.php?page=payments&action=new&id='.(int)$n['id']); ?>"><i class="bi bi-cash-coin"></i> Collect</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
