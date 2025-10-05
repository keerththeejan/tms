<?php /** @var array $notes */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Delivery Notes</h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=generate'); ?>" class="btn btn-primary"><i class="bi bi-magic"></i> Generate</a>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=customer_summary'); ?>" class="btn btn-outline-secondary"><i class="bi bi-people"></i> Customer Summary</a>
  </div>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="delivery_notes">
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
        <th>Delivery Date</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Supplier</th>
        <th>Supplier Phone</th>
        <th>Total</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($notes as $n): ?>
        <tr>
          <td><?php echo (int)$n['id']; ?></td>
          <td><?php echo htmlspecialchars($n['delivery_date']); ?></td>
          <td><?php echo htmlspecialchars($n['customer_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($n['customer_phone'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($n['suppliers'] ?? '—'); ?></td>
          <td><?php echo htmlspecialchars($n['supplier_phones'] ?? '—'); ?></td>
          <td><?php echo number_format((float)$n['total_amount'], 2); ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='.(int)$n['id']); ?>"><i class="bi bi-eye"></i> View</a>
            <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=print&id='.(int)$n['id']); ?>" target="_blank"><i class="bi bi-printer"></i> Print</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
