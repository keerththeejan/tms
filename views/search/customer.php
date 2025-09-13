<?php /** @var string $phone */ /** @var array|null $customer */ ?>
<h3 class="mb-3">Customer Search</h3>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="search">
  <div class="col-md-4">
    <input type="text" class="form-control" name="phone" placeholder="Enter phone number" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Search</button>
  </div>
</form>
<?php if ($customer): ?>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-3"><strong>Name:</strong> <?php echo htmlspecialchars($customer['name']); ?></div>
      <div class="col-md-3"><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></div>
      <div class="col-md-3"><strong>Address:</strong> <?php echo htmlspecialchars($customer['address'] ?? ''); ?></div>
      <div class="col-md-3"><strong>Delivery Location:</strong> <?php echo htmlspecialchars($customer['delivery_location'] ?? ''); ?></div>
    </div>
  </div>
</div>
<?php if ($dueSummary): ?>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <?php $due = max(0, (float)$dueSummary['total'] - (float)$dueSummary['paid']); ?>
    <div>Total Billed: <strong><?php echo number_format((float)$dueSummary['total'], 2); ?></strong></div>
    <div>Total Paid: <strong class="text-success"><?php echo number_format((float)$dueSummary['paid'], 2); ?></strong></div>
    <div>Due Balance: <strong class="text-danger"><?php echo number_format($due, 2); ?></strong></div>
  </div>
</div>
<?php endif; ?>
<h5>Parcel History</h5>
<div class="table-responsive mb-4">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>From</th>
        <th>To</th>
        <th>Supplier</th>
        <th>Weight</th>
        <th>Price</th>
        <th>Status</th>
        <th>Tracking</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($parcels ?? []) as $p): ?>
        <tr>
          <td><?php echo (int)$p['id']; ?></td>
          <td><?php echo htmlspecialchars($p['created_at']); ?></td>
          <td><?php echo htmlspecialchars($p['from_branch'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['to_branch'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['supplier_name'] ?? ''); ?></td>
          <td><?php echo number_format((float)$p['weight'], 2); ?></td>
          <td><?php echo is_null($p['price']) ? '-' : number_format((float)$p['price'], 2); ?></td>
          <td><?php echo htmlspecialchars($p['status']); ?></td>
          <td><?php echo htmlspecialchars($p['tracking_number'] ?? ''); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<h5>Delivery Notes</h5>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Branch</th>
        <th>Total</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($notes ?? []) as $n): ?>
        <tr>
          <td><?php echo (int)$n['id']; ?></td>
          <td><?php echo htmlspecialchars($n['delivery_date']); ?></td>
          <td><?php echo htmlspecialchars($n['branch_name'] ?? ''); ?></td>
          <td><?php echo number_format((float)$n['total_amount'], 2); ?></td>
          <td>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='.(int)$n['id']); ?>">View</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php elseif ($phone !== ''): ?>
<div class="alert alert-warning">No customer found with phone: <?php echo htmlspecialchars($phone); ?></div>
<?php endif; ?>
