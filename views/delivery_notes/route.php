<?php /** @var array $routes */ /** @var string $date */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Delivery Route Planning</h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=new'); ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Customer</a>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</div>
<?php if (isset($parcels_total) || isset($customers_total) || isset($branchName)): ?>
<div class="row g-3 mb-3">
  <div class="col-sm-4">
    <div class="border rounded p-2 bg-light">
      <div class="text-muted small">Branch</div>
      <div class="fw-semibold"><?php echo htmlspecialchars($branchName ?? ''); ?></div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="border rounded p-2 bg-light">
      <div class="text-muted small">Pending Parcels (to deliver)</div>
      <div class="fw-semibold fs-5"><?php echo (int)($parcels_total ?? 0); ?></div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="border rounded p-2 bg-light">
      <div class="text-muted small">Customers</div>
      <div class="fw-semibold fs-5"><?php echo (int)($customers_total ?? 0); ?></div>
    </div>
  </div>
  </div>
<?php endif; ?>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="delivery_notes">
  <input type="hidden" name="action" value="route">
  <div class="col-md-3">
    <label class="form-label">Delivery Date</label>
    <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
  </div>
  <div class="col-auto d-flex align-items-end">
    <button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button>
  </div>
</form>
<?php if (empty($routes)): ?>
  <div class="alert alert-info">No pending parcels to deliver from this branch.</div>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Delivery Location</th>
        <th class="text-center">Parcels</th>
        <th class="text-end">Est. Total</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=0; foreach ($routes as $r): $i++; ?>
        <tr>
          <td><?php echo $i; ?></td>
          <td><?php echo htmlspecialchars($r['customer_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($r['customer_phone'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($r['delivery_location'] ?? ''); ?></td>
          <td class="text-center"><?php echo (int)($r['parcels_count'] ?? 0); ?></td>
          <td class="text-end"><?php echo number_format((float)($r['est_total'] ?? 0), 2); ?></td>
          <td class="text-end">
            <form class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=generate'); ?>" onsubmit="return confirm('Generate delivery note for this customer on selected date?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
              <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
              <button class="btn btn-sm btn-primary"><i class="bi bi-clipboard-check"></i> Generate</button>
            </form>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=search&phone=' . urlencode($r['customer_phone'] ?? '')); ?>" target="_blank"><i class="bi bi-person"></i> View</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
