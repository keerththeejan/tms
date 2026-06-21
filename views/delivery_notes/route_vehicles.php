<?php /** @var array $routes */ ?>
<?php
  $routes = $routes ?? [];
  $hasFilters = !empty($hasFilters);
  $routeBranchId = (int)($routeBranchId ?? 0);
  $isMain = !empty($isMain);
?>
<style>
  .dn-rv-page { --rv-border: rgba(17,24,39,.08); --rv-radius: 14px; --rv-accent: #4f46e5; }
  .dn-rv-page .rv-head { margin-bottom: 1rem; }
  .dn-rv-page .rv-title { font-size: 1.3rem; font-weight: 800; margin: 0; color: #0f172a; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
  .dn-rv-page .rv-title i { color: var(--rv-accent); }
  .dn-rv-page .rv-sub { font-size: .88rem; color: #64748b; margin: .35rem 0 0; max-width: 44rem; }
  .dn-rv-page .rv-filters, .dn-rv-page .rv-table-card {
    background: #fff; border: 1px solid var(--rv-border); border-radius: var(--rv-radius);
    box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 8px 24px rgba(15,23,42,.06);
  }
  .dn-rv-page .rv-filters { padding: .85rem 1rem; margin-bottom: 1rem; }
  .dn-rv-page .rv-filters .form-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
  .dn-rv-page .rv-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
  .dn-rv-page .rv-empty .bi { font-size: 2.25rem; opacity: .35; color: var(--rv-accent); display: block; margin-bottom: .75rem; }
</style>

<div class="dn-rv-page container-fluid px-0">
  <div class="rv-head d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
    <div>
      <h1 class="rv-title"><i class="bi bi-truck-front" aria-hidden="true"></i> Vehicle Routes</h1>
      <p class="rv-sub">Parcels grouped by vehicle for dispatch or arrival. Open a route to build delivery notes per customer.</p>
    </div>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>" class="btn btn-outline-secondary rounded-pill"><i class="bi bi-arrow-left me-1"></i> Route planning</a>
  </div>

  <?php if (($_GET['saved'] ?? '') === '1'): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 px-3" role="alert">
      Vehicle number updated successfully.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php elseif (($_GET['err'] ?? '') === 'vehicle_required'): ?>
    <div class="alert alert-warning alert-dismissible fade show py-2 px-3" role="alert">
      Vehicle number is required.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="rv-filters">
    <form class="row g-2 g-md-3 align-items-end" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
      <input type="hidden" name="page" value="delivery_notes">
      <input type="hidden" name="action" value="route_vehicles">
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label">From</label>
        <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? date('Y-m-d')); ?>">
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? date('Y-m-d')); ?>">
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label">Direction</label>
        <select class="form-select" name="direction" data-enhance="false">
          <option value="from" <?php echo (($direction ?? 'from') === 'from') ? 'selected' : ''; ?>>Dispatch (from branch)</option>
          <option value="to" <?php echo (($direction ?? 'from') === 'to') ? 'selected' : ''; ?>>Arrival (to branch)</option>
        </select>
      </div>
      <?php if ($isMain): ?>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label">Branch</label>
        <select class="form-select" name="branch_id" data-enhance="false">
          <?php $bid = $routeBranchId; ?>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ($bid === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-6 col-md-4 col-lg-<?php echo $isMain ? '2' : '3'; ?>">
        <label class="form-label">Vehicle</label>
        <input type="text" class="form-control" name="vehicle" placeholder="Vehicle no" value="<?php echo htmlspecialchars($vehicle ?? ''); ?>">
      </div>
      <div class="col-12 col-lg-auto d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
        <a class="btn btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>">Clear</a>
      </div>
    </form>
  </div>

  <div class="rv-table-card overflow-hidden">
    <?php if (empty($routes)): ?>
      <div class="rv-empty">
        <i class="bi bi-truck" aria-hidden="true"></i>
        <?php if ($hasFilters): ?>
          <div class="fw-semibold text-dark mb-1">No vehicle routes match your filters</div>
          <div class="small mb-3">Widen the date range, switch direction, or clear filters.</div>
        <?php else: ?>
          <div class="fw-semibold text-dark mb-1">No vehicle routes in this period</div>
          <div class="small mb-3">Assign vehicle numbers on parcels, then return here to group by vehicle.</div>
        <?php endif; ?>
        <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=new'); ?>" class="btn btn-primary btn-sm rounded-pill me-2"><i class="bi bi-plus-lg me-1"></i> New parcel</a>
        <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Route planning</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:56px;">#</th>
              <th>Vehicle</th>
              <th>Last date</th>
              <th class="text-center">Parcels</th>
              <th class="text-center">Delivered</th>
              <th class="text-end" style="min-width:220px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 0; foreach ($routes as $r): $i++; ?>
              <tr>
                <td><?php echo $i; ?></td>
                <td class="fw-semibold"><?php echo htmlspecialchars($r['vehicle_no'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars((string)($r['last_date'] ?? '')); ?></td>
                <td class="text-center"><?php echo (int)($r['parcels_count'] ?? 0); ?></td>
                <td class="text-center"><?php echo (int)($r['delivered_count'] ?? 0); ?></td>
                <td class="text-end text-nowrap">
                  <a class="btn btn-sm btn-outline-primary me-1" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_detail&vehicle_no=' . urlencode($r['vehicle_no'] ?? '') . '&from=' . urlencode($from ?? '') . '&to=' . urlencode($to ?? '') . '&direction=' . urlencode($direction ?? 'from') . ($isMain ? '&branch_id=' . $routeBranchId : '')); ?>">
                    <i class="bi bi-list-ul"></i><span class="d-none d-md-inline ms-1">View route</span>
                  </a>
                  <form id="veh-edit-form-<?php echo $i; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles_update'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                    <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
                    <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
                    <input type="hidden" name="direction" value="<?php echo htmlspecialchars($direction ?? 'from'); ?>">
                    <?php if ($isMain): ?><input type="hidden" name="branch_id" value="<?php echo $routeBranchId; ?>"><?php endif; ?>
                    <input type="hidden" name="old_vehicle" value="<?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?>">
                    <input type="hidden" name="new_vehicle" value="">
                  </form>
                  <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="(function(f){var v=prompt('Enter new vehicle number', f.new_vehicle.value || f.old_vehicle.value); if(v!==null){v=v.trim(); if(v){f.new_vehicle.value=v; f.submit();} else {alert('Enter vehicle number');}}})(document.getElementById('veh-edit-form-<?php echo $i; ?>'));"><i class="bi bi-pencil-square"></i><span class="d-none d-md-inline ms-1">Edit</span></button>
                  <form id="veh-del-form-<?php echo $i; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles_clear'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                    <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
                    <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
                    <input type="hidden" name="direction" value="<?php echo htmlspecialchars($direction ?? 'from'); ?>">
                    <?php if ($isMain): ?><input type="hidden" name="branch_id" value="<?php echo $routeBranchId; ?>"><?php endif; ?>
                    <input type="hidden" name="old_vehicle" value="<?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?>">
                  </form>
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="(function(f){ if(confirm('Clear vehicle number for parcels in this range?')) f.submit(); })(document.getElementById('veh-del-form-<?php echo $i; ?>'));"><i class="bi bi-trash"></i><span class="d-none d-md-inline ms-1">Clear</span></button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
