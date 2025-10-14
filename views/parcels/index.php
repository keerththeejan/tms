<?php /** @var array $parcels */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Parcels</h3>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="parcels">
  <div class="col-sm-6 col-md-4">
    <input type="text" class="form-control" name="q" placeholder="Search by customer phone or name" value="<?php echo htmlspecialchars($q ?? ''); ?>">
  </div>
  <div class="col-sm-4 col-md-3">
    <input type="text" class="form-control" name="vehicle_no" placeholder="Vehicle No" value="<?php echo htmlspecialchars($vehicle_no ?? ''); ?>">
  </div>
  <div class="col-sm-6 col-md-4">
    <select class="form-select" name="customer_id">
      <option value="0">All Customers</option>
      <?php foreach (($customersList ?? []) as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($customer_filter_id ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name'].' ('.$c['phone'].')'); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-sm-4 col-md-3">
    <select class="form-select" name="to_branch_id">
      <option value="0">All To Branches</option>
      <?php foreach (($branchesFilterList ?? []) as $b): ?>
        <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($to_branch_filter_id ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-sm-4 col-md-3">
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  </div>
  <div class="col-sm-4 col-md-3">
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  </div>
  <div class="col-sm-4 col-md-3">
    <select class="form-select" name="status">
      <option value="">All Status</option>
      <option value="pending" <?php echo ($status ?? '')==='pending'?'selected':''; ?>>Pending</option>
      <option value="in_transit" <?php echo ($status ?? '')==='in_transit'?'selected':''; ?>>In Transit</option>
      <option value="delivered" <?php echo ($status ?? '')==='delivered'?'selected':''; ?>>Delivered</option>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle datatable">
    <thead>
      <tr>
        <th>#</th>
        <th>Customer</th>
        <th>Supplier</th>
        <th>From Branch</th>
        <th>To Branch</th>
        <th>Vehicle</th>
        <th>Weight</th>
        <th>Price</th>
        <th>Status</th>
        <th>Email</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($parcels as $p): ?>
        <tr>
          <td><?php echo (int)$p['id']; ?></td>
          <td>
            <?php $cid = (int)$p['customer_id']; ?>
            <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&customer_id=' . $cid); ?>" class="text-decoration-none">
              <?php echo htmlspecialchars(($p['customer_name'] ?? '').' ('.($p['customer_phone'] ?? '').')'); ?>
            </a>
          </td>
          <td><?php echo htmlspecialchars($p['supplier_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['from_branch'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['to_branch'] ?? ''); ?></td>
          <td>
            <?php if (!empty($p['vehicle_no'])): ?>
              <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&vehicle_no=' . urlencode($p['vehicle_no'])); ?>" class="text-decoration-none">
                <?php echo htmlspecialchars($p['vehicle_no']); ?>
              </a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td><?php echo number_format((float)$p['weight'], 2); ?></td>
          <td><?php echo is_null($p['price']) ? '-' : number_format((float)$p['price'], 2); ?></td>
          <td><span class="badge bg-<?php echo $p['status']==='delivered'?'success':($p['status']==='in_transit'?'info':'secondary'); ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
          <td>
            <?php if (!empty($p['email_status'])): ?>
              <?php if ($p['email_status'] === 'sent'): ?>
                <span class="badge bg-success">Sent</span>
              <?php else: ?>
                <span class="badge bg-danger">Failed</span>
              <?php endif; ?>
              <small class="text-muted d-block"><?php echo htmlspecialchars($p['emailed_at'] ?? ''); ?></small>
            <?php else: ?>
              <span class="badge bg-secondary">Not sent</span>
            <?php endif; ?>
            <div>
              <a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=email_log&id='.(int)$p['id']); ?>">View log</a>
            </div>
          </td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?php echo Helpers::baseUrl('index.php?page=parcel_print&id='.(int)$p['id']); ?>"><i class="bi bi-printer"></i> Print</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id='.(int)$p['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <a class="btn btn-sm btn-outline-success" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route&customer_id='.(int)$p['customer_id']); ?>"><i class="bi bi-signpost"></i> Delivery Route</a>
            <?php if (!empty($isKilinochchi)): ?>
              <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=generate'); ?>" class="d-inline" onsubmit="return confirm('Generate invoice for this customer?');">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="customer_id" value="<?php echo (int)$p['customer_id']; ?>">
                <input type="hidden" name="delivery_date" value="<?php echo date('Y-m-d'); ?>">
                <button class="btn btn-sm btn-primary"><i class="bi bi-receipt"></i> Generate Invoice</button>
              </form>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-info" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=email_form&id='.(int)$p['id']); ?>"><i class="bi bi-envelope"></i> Email</a>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=parcels&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this parcel?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
