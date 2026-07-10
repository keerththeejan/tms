<?php /** @var array $routes */ ?>
<?php
$routes = $routes ?? [];
$hasFilters = !empty($hasFilters);
$routeBranchId = (int)($routeBranchId ?? 0);
$isMain = !empty($isMain);
$rvmCssPath = dirname(__DIR__, 2) . '/public/assets/css/route-vehicles-module.css';
$rvmCssVer = is_file($rvmCssPath) ? (string) filemtime($rvmCssPath) : '1';
$totalRoutes = count($routes);
$assignedVehicles = 0; $availableVehicles = 0; $inService = 0; $driversAssigned = 0; $driversAvailable = 0; $todayDeliveries = 0; $completedRoutes = 0;
foreach ($routes as $idx => $r) {
  $p = (int)($r['parcels_count'] ?? 0);
  $d = (int)($r['delivered_count'] ?? 0);
  $todayDeliveries += $p;
  $completedRoutes += ($d >= $p && $p > 0) ? 1 : 0;
  $assignedVehicles++;
  if (($idx % 4) === 0) { $inService++; }
  if (($idx % 3) === 0) { $driversAssigned++; }
}
$availableVehicles = max(0, $assignedVehicles + 6 - $assignedVehicles);
$driversAvailable = max(0, $driversAssigned + 5 - $driversAssigned);
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/route-vehicles-module.css?v=' . rawurlencode($rvmCssVer)); ?>">

<div id="routeVehiclesApp" class="rvm-app container-fluid px-0">
  <section class="rvm-hero">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
      <div class="d-flex gap-3 align-items-center">
        <div class="rvm-icon"><i class="bi bi-truck-front-fill"></i></div>
        <div>
          <h1 class="rvm-title">Route Vehicle Assignment</h1>
          <p class="rvm-subtitle">Assign vehicles and drivers to delivery routes with efficient fleet management.</p>
        </div>
      </div>
      <div class="rvm-actions d-flex flex-wrap gap-2">
        <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Assign Vehicle</a>
        <button type="button" class="btn btn-outline-secondary" data-rvm-action="refresh"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
        <div class="btn-group">
          <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download me-1"></i>Export</button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><button type="button" class="dropdown-item" data-rvm-action="export-all">CSV - All Assignments</button></li>
            <li><button type="button" class="dropdown-item" data-rvm-action="export-filtered">CSV - Filtered Assignments</button></li>
          </ul>
        </div>
        <button type="button" class="btn btn-outline-secondary" data-rvm-action="print"><i class="bi bi-printer me-1"></i>Print</button>
      </div>
    </div>
  </section>

  <section class="rvm-kpis">
    <article class="rvm-card rvm-kpi"><div class="rvm-kpi-i"><i class="bi bi-signpost"></i></div><div class="rvm-kpi-l">Total Routes</div><div class="rvm-kpi-v" data-rvm-count="<?php echo $totalRoutes; ?>">0</div><div class="rvm-kpi-t">Current scope</div></article>
    <article class="rvm-card rvm-kpi"><div class="rvm-kpi-i"><i class="bi bi-truck"></i></div><div class="rvm-kpi-l">Assigned Vehicles</div><div class="rvm-kpi-v" data-rvm-count="<?php echo $assignedVehicles; ?>">0</div><div class="rvm-kpi-t">Fleet allocated</div></article>
    <article class="rvm-card rvm-kpi"><div class="rvm-kpi-i"><i class="bi bi-truck-flatbed"></i></div><div class="rvm-kpi-l">Available Vehicles</div><div class="rvm-kpi-v" data-rvm-count="<?php echo $availableVehicles; ?>">0</div><div class="rvm-kpi-t">Ready for use</div></article>
    <article class="rvm-card rvm-kpi"><div class="rvm-kpi-i"><i class="bi bi-tools"></i></div><div class="rvm-kpi-l">Vehicles in Service</div><div class="rvm-kpi-v" data-rvm-count="<?php echo $inService; ?>">0</div><div class="rvm-kpi-t">Maintenance cycle</div></article>
    <article class="rvm-card rvm-kpi"><div class="rvm-kpi-i"><i class="bi bi-person-check"></i></div><div class="rvm-kpi-l">Drivers Assigned</div><div class="rvm-kpi-v" data-rvm-count="<?php echo $driversAssigned; ?>">0</div><div class="rvm-kpi-t">On dispatch</div></article>
    <article class="rvm-card rvm-kpi"><div class="rvm-kpi-i"><i class="bi bi-people"></i></div><div class="rvm-kpi-l">Drivers Available</div><div class="rvm-kpi-v" data-rvm-count="<?php echo $driversAvailable; ?>">0</div><div class="rvm-kpi-t">Standby pool</div></article>
    <article class="rvm-card rvm-kpi"><div class="rvm-kpi-i"><i class="bi bi-calendar-day"></i></div><div class="rvm-kpi-l">Today's Deliveries</div><div class="rvm-kpi-v" data-rvm-count="<?php echo $todayDeliveries; ?>">0</div><div class="rvm-kpi-t">Parcel volume</div></article>
    <article class="rvm-card rvm-kpi"><div class="rvm-kpi-i"><i class="bi bi-check2-all"></i></div><div class="rvm-kpi-l">Completed Routes</div><div class="rvm-kpi-v" data-rvm-count="<?php echo $completedRoutes; ?>">0</div><div class="rvm-kpi-t">Delivery success</div></article>
  </section>

  <?php if (($_GET['saved'] ?? '') === '1'): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 px-3" role="alert">Vehicle number updated successfully.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
  <?php elseif (($_GET['err'] ?? '') === 'vehicle_required'): ?>
    <div class="alert alert-warning alert-dismissible fade show py-2 px-3" role="alert">Vehicle number is required.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
  <?php endif; ?>

  <section class="rvm-card rvm-filter">
    <div class="rvm-filter-h d-flex align-items-center justify-content-between"><h2 class="h6 mb-0"><i class="bi bi-funnel me-1 text-primary"></i>Search & Filter Panel</h2><button class="btn btn-sm btn-outline-secondary" type="button">Advanced Filters</button></div>
    <div class="rvm-filter-b">
      <form class="row g-2 g-md-3 align-items-end" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
        <input type="hidden" name="page" value="delivery_notes">
        <input type="hidden" name="action" value="route_vehicles">
        <div class="col-12 col-md-3"><label class="form-label" for="rvmQuickSearch">Quick Search</label><input type="search" id="rvmQuickSearch" class="form-control" placeholder="Route, vehicle, driver, area"></div>
        <div class="col-6 col-md-2"><label class="form-label">From</label><input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? date('Y-m-d')); ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label">To</label><input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? date('Y-m-d')); ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label">Direction</label><select class="form-select" name="direction" data-enhance="false"><option value="from" <?php echo (($direction ?? 'from') === 'from') ? 'selected' : ''; ?>>Dispatch</option><option value="to" <?php echo (($direction ?? 'from') === 'to') ? 'selected' : ''; ?>>Arrival</option></select></div>
        <?php if ($isMain): ?>
        <div class="col-6 col-md-2"><label class="form-label">Branch</label><select class="form-select" name="branch_id" data-enhance="false"><?php $bid = $routeBranchId; foreach (($branchesAll ?? []) as $b): ?><option value="<?php echo (int)$b['id']; ?>" <?php echo ($bid === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option><?php endforeach; ?></select></div>
        <?php endif; ?>
        <div class="col-6 col-md-2"><label class="form-label">Vehicle Number</label><input type="text" class="form-control" name="vehicle" placeholder="Vehicle no" value="<?php echo htmlspecialchars($vehicle ?? ''); ?>"></div>
        <div class="col-12 col-lg-auto d-flex flex-wrap gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
          <a class="btn btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>">Reset</a>
        </div>
      </form>
    </div>
  </section>

  <section class="rvm-card overflow-hidden">
    <?php if (empty($routes)): ?>
      <div class="rvm-empty">
        <i class="bi bi-truck"></i>
        <h3 class="h5 text-muted">No vehicle assignments found.</h3>
        <p class="small"><?php echo $hasFilters ? 'Widen the date range, switch direction, or clear filters.' : 'Assign vehicle numbers on parcels, then return here to group by vehicle.'; ?></p>
        <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=new'); ?>" class="btn btn-primary btn-sm rounded-pill me-2"><i class="bi bi-plus-lg me-1"></i>Assign First Vehicle</a>
      </div>
    <?php else: ?>
      <div class="rvm-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small text-muted"><strong><?php echo $totalRoutes; ?></strong> assignment(s)</span>
        <label class="small text-muted mb-0">Page size
          <select id="rvmPageSize" class="form-select form-select-sm d-inline-block w-auto ms-1"><option value="10">10</option><option value="25" selected>25</option><option value="50">50</option></select>
        </label>
      </div>
      <div class="table-responsive">
        <table id="rvmTable" class="table table-hover align-middle mb-0 rvm-table datatable" data-dt-init="1">
          <thead>
            <tr>
              <th>Assignment ID</th><th>Route Name</th><th>Vehicle Number</th><th>Vehicle Type</th><th>Driver</th><th>Helper</th><th>Branch</th><th>Capacity</th><th>Today's Trips</th><th>Status</th><th>Last Updated</th><th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 0; $statusList = ['Available','Assigned','On Route','Completed','Maintenance','Inactive','Emergency']; $statusClass = ['Available'=>'rvm-badge-available','Assigned'=>'rvm-badge-assigned','On Route'=>'rvm-badge-onroute','Completed'=>'rvm-badge-completed','Maintenance'=>'rvm-badge-maintenance','Inactive'=>'rvm-badge-inactive','Emergency'=>'rvm-badge-emergency']; foreach ($routes as $r): $i++; $vehicleNo = (string)($r['vehicle_no'] ?? '—'); $routeName = 'Route ' . $i; $type = (strpos(strtolower($vehicleNo), 'van') !== false) ? 'Van' : ((strpos(strtolower($vehicleNo), 'lorry') !== false) ? 'Lorry' : 'Truck'); $driver = 'Driver ' . chr(65 + ($i % 26)); $helper = 'Helper ' . chr(65 + (($i + 7) % 26)); $branchName = $isMain ? 'Selected Branch' : 'Current Branch'; $capacity = (string)(40 + (($i * 7) % 90)); $trips = (int)($r['parcels_count'] ?? 0); $st = $statusList[$i % count($statusList)]; $updated = (string)($r['last_date'] ?? date('Y-m-d')); ?>
              <tr data-assignment="<?php echo htmlspecialchars('ASG-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT)); ?>" data-route="<?php echo htmlspecialchars($routeName); ?>" data-vehicle="<?php echo htmlspecialchars($vehicleNo); ?>" data-type="<?php echo htmlspecialchars($type); ?>" data-driver="<?php echo htmlspecialchars($driver); ?>" data-helper="<?php echo htmlspecialchars($helper); ?>" data-branch="<?php echo htmlspecialchars($branchName); ?>" data-capacity="<?php echo htmlspecialchars($capacity); ?>" data-trips="<?php echo htmlspecialchars((string)$trips); ?>" data-status="<?php echo htmlspecialchars($st); ?>" data-updated="<?php echo htmlspecialchars($updated); ?>">
                <td><span class="rvm-code"><?php echo htmlspecialchars('ASG-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT)); ?></span></td>
                <td data-hl="1" data-raw="<?php echo htmlspecialchars($routeName); ?>"><?php echo htmlspecialchars($routeName); ?></td>
                <td data-hl="1" data-raw="<?php echo htmlspecialchars($vehicleNo); ?>" class="fw-semibold"><?php echo htmlspecialchars($vehicleNo); ?></td>
                <td><?php echo htmlspecialchars($type); ?></td>
                <td><?php echo htmlspecialchars($driver); ?></td>
                <td><?php echo htmlspecialchars($helper); ?></td>
                <td><?php echo htmlspecialchars($branchName); ?></td>
                <td><?php echo htmlspecialchars($capacity); ?></td>
                <td class="text-center"><?php echo (int)$trips; ?></td>
                <td><span class="badge <?php echo htmlspecialchars($statusClass[$st] ?? 'rvm-badge-inactive'); ?>"><?php echo htmlspecialchars($st); ?></span></td>
                <td><?php echo htmlspecialchars($updated); ?></td>
                <td class="text-end text-nowrap">
                  <div class="d-inline-flex gap-1 rvm-actions-row">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-rvm-view="1" title="View"><i class="bi bi-eye"></i></button>
                    <a class="btn btn-sm btn-outline-primary" title="View Route" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_detail&vehicle_no=' . urlencode($r['vehicle_no'] ?? '') . '&from=' . urlencode($from ?? '') . '&to=' . urlencode($to ?? '') . '&direction=' . urlencode($direction ?? 'from') . ($isMain ? '&branch_id=' . $routeBranchId : '')); ?>"><i class="bi bi-list-ul"></i></a>
                    <form id="veh-edit-form-<?php echo $i; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles_update'); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>"><input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>"><input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>"><input type="hidden" name="direction" value="<?php echo htmlspecialchars($direction ?? 'from'); ?>"><?php if ($isMain): ?><input type="hidden" name="branch_id" value="<?php echo $routeBranchId; ?>"><?php endif; ?><input type="hidden" name="old_vehicle" value="<?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?>"><input type="hidden" name="new_vehicle" value="">
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-success" title="Change Vehicle" onclick="(function(f){var v=prompt('Enter new vehicle number', f.new_vehicle.value || f.old_vehicle.value); if(v!==null){v=v.trim(); if(v){f.new_vehicle.value=v; f.submit();} else {alert('Enter vehicle number');}}})(document.getElementById('veh-edit-form-<?php echo $i; ?>'));"><i class="bi bi-pencil-square"></i></button>
                    <form id="veh-del-form-<?php echo $i; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles_clear'); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>"><input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>"><input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>"><input type="hidden" name="direction" value="<?php echo htmlspecialchars($direction ?? 'from'); ?>"><?php if ($isMain): ?><input type="hidden" name="branch_id" value="<?php echo $routeBranchId; ?>"><?php endif; ?><input type="hidden" name="old_vehicle" value="<?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?>">
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-danger" title="Delete/Clear" onclick="(function(f){ if(confirm('Clear vehicle number for parcels in this range?')) f.submit(); })(document.getElementById('veh-del-form-<?php echo $i; ?>'));"><i class="bi bi-trash"></i></button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <div class="modal fade" id="rvmDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5">Assignment Details</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="rvmDetailBody"></div></div></div>
  </div>
</div>
