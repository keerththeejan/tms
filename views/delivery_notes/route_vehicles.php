<?php /** @var array $routes */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Vehicle Routes</h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</div>
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
  <div class="col-auto d-flex align-items-end">
    <button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button>
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
          <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_detail&vehicle_no=' . urlencode($r['vehicle_no'] ?? '') . '&from=' . urlencode($from ?? '') . '&to=' . urlencode($to ?? '') . '&direction=' . urlencode($direction ?? 'from')); ?>">
            <i class="bi bi-list-ul"></i> View Route
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
