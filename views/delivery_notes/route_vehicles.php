<?php /** @var array $routes */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Vehicle Routes</h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</div>
<?php if (($_GET['saved'] ?? '') === '1'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  Vehicle number updated successfully.
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php elseif (($_GET['err'] ?? '') === 'vehicle_required'): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
  Vehicle number required.
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="delivery_notes">
  <input type="hidden" name="action" value="route_vehicles">
  <div class="col-md-3">
    <label class="form-label">From</label>
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? date('Y-m-d')); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">To</label>
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? date('Y-m-d')); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Direction</label>
    <select class="form-select" name="direction">
      <option value="from" <?php echo (($direction ?? 'from')==='from')?'selected':''; ?>>Dispatch (From my branch)</option>
      <option value="to" <?php echo (($direction ?? 'from')==='to')?'selected':''; ?>>Arrival (To my branch)</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Vehicle</label>
    <input type="text" class="form-control" name="vehicle" placeholder="Vehicle No" value="<?php echo htmlspecialchars($vehicle ?? ''); ?>">
  </div>
  <div class="col-auto d-flex align-items-end gap-2">
    <button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>">Clear</a>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Vehicle</th>
        <th class="text-center">Parcels</th>
        <th class="text-center">Delivered</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($routes)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted">No routes found for the selected filters. Try widening the date range or switching Direction.</td>
        </tr>
      <?php endif; ?>
      <?php $i=0; foreach (($routes ?? []) as $r): $i++; ?>
      <tr>
        <td><?php echo $i; ?></td>
        <td><?php echo htmlspecialchars($r['vehicle_no'] ?? '—'); ?></td>
        <td class="text-center"><?php echo (int)($r['parcels_count'] ?? 0); ?></td>
        <td class="text-center"><?php echo (int)($r['delivered_count'] ?? 0); ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary me-1" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_detail&vehicle_no=' . urlencode($r['vehicle_no'] ?? '') . '&from=' . urlencode($from ?? '') . '&to=' . urlencode($to ?? '') . '&direction=' . urlencode($direction ?? 'from')); ?>">
            <i class="bi bi-list-ul"></i> View Route
          </a>
          <form id="veh-edit-form-<?php echo $i; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles_update'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
            <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
            <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
            <input type="hidden" name="direction" value="<?php echo htmlspecialchars($direction ?? 'from'); ?>">
            <input type="hidden" name="old_vehicle" value="<?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?>">
            <input type="hidden" name="new_vehicle" value="">
          </form>
          <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="(function(f){var v=prompt('Enter new vehicle number', f.new_vehicle.value || f.old_vehicle.value); if(v!==null){v=v.trim(); if(v){f.new_vehicle.value=v; f.submit();} else {alert('Enter vehicle number');}}})(document.getElementById('veh-edit-form-<?php echo $i; ?>'));"><i class="bi bi-pencil-square"></i> Edit</button>
          <form id="veh-del-form-<?php echo $i; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles_clear'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
            <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
            <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
            <input type="hidden" name="direction" value="<?php echo htmlspecialchars($direction ?? 'from'); ?>">
            <input type="hidden" name="old_vehicle" value="<?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?>">
          </form>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="(function(f){ if(confirm('Clear vehicle number for this range?')) f.submit(); })(document.getElementById('veh-del-form-<?php echo $i; ?>'));"><i class="bi bi-trash"></i> Delete</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
