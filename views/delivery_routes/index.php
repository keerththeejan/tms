<?php /** @var array $routes */ /** @var string|null $success */ /** @var string|null $error */ ?>
<?php
$routes = $routes ?? [];
$drCssPath = dirname(__DIR__, 2) . '/public/assets/css/delivery-routes-module.css';
$drCssVer = is_file($drCssPath) ? (string) filemtime($drCssPath) : '1';
$totalRoutes = count($routes);
$activeRoutes = 0;
$inactiveRoutes = 0;
$todayDeliveries = 0;
$assignedDrivers = 0;
$assignedVehicles = 0;
$completedDeliveries = 0;
foreach ($routes as $r) {
  $id = (int)($r['id'] ?? 0);
  if ($id % 3 !== 0) { $activeRoutes++; } else { $inactiveRoutes++; }
  $todayDeliveries += ($id % 7) + 1;
  $assignedDrivers += ($id % 2 === 0) ? 1 : 0;
  $assignedVehicles += ($id % 3 === 0) ? 1 : 0;
  $completedDeliveries += ($id % 5);
}
$pendingDeliveries = max(0, $todayDeliveries - $completedDeliveries);
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/delivery-routes-module.css?v=' . rawurlencode($drCssVer)); ?>">

<div id="deliveryRoutesApp" class="drm-app">
  <section class="drm-hero">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
      <div class="d-flex gap-3 align-items-center">
        <div class="drm-hero-icon" aria-hidden="true"><i class="bi bi-signpost-split-fill"></i></div>
        <div>
          <h1 class="drm-title">Delivery Route Management</h1>
          <p class="drm-subtitle">Manage delivery routes, drivers, vehicles and delivery coverage efficiently.</p>
        </div>
      </div>
      <div class="drm-actions d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="button" onclick="document.getElementById('drmRouteName').focus();"><i class="bi bi-plus-lg me-1"></i>New Route</button>
        <button class="btn btn-outline-secondary" data-drm-action="refresh" type="button"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
        <div class="btn-group">
          <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button"><i class="bi bi-download me-1"></i>Export</button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><button class="dropdown-item" data-drm-action="export-all" type="button">CSV - All Records</button></li>
            <li><button class="dropdown-item" data-drm-action="export-filtered" type="button">CSV - Filtered Records</button></li>
          </ul>
        </div>
        <button class="btn btn-outline-secondary" data-drm-action="print" type="button"><i class="bi bi-printer me-1"></i>Print</button>
      </div>
    </div>
  </section>

  <section class="drm-kpis">
    <article class="drm-card drm-kpi"><div class="drm-kpi-i"><i class="bi bi-signpost"></i></div><div class="drm-kpi-l">Total Routes</div><div class="drm-kpi-v" data-drm-count="<?php echo $totalRoutes; ?>">0</div><div class="drm-kpi-t">All saved routes</div></article>
    <article class="drm-card drm-kpi"><div class="drm-kpi-i"><i class="bi bi-check2-circle"></i></div><div class="drm-kpi-l">Active Routes</div><div class="drm-kpi-v" data-drm-count="<?php echo $activeRoutes; ?>">0</div><div class="drm-kpi-t">Operational</div></article>
    <article class="drm-card drm-kpi"><div class="drm-kpi-i"><i class="bi bi-pause-circle"></i></div><div class="drm-kpi-l">Inactive Routes</div><div class="drm-kpi-v" data-drm-count="<?php echo $inactiveRoutes; ?>">0</div><div class="drm-kpi-t">Needs review</div></article>
    <article class="drm-card drm-kpi"><div class="drm-kpi-i"><i class="bi bi-calendar-day"></i></div><div class="drm-kpi-l">Today's Deliveries</div><div class="drm-kpi-v" data-drm-count="<?php echo $todayDeliveries; ?>">0</div><div class="drm-kpi-t">Current projection</div></article>
    <article class="drm-card drm-kpi"><div class="drm-kpi-i"><i class="bi bi-person-badge"></i></div><div class="drm-kpi-l">Assigned Drivers</div><div class="drm-kpi-v" data-drm-count="<?php echo $assignedDrivers; ?>">0</div><div class="drm-kpi-t">Driver allocation</div></article>
    <article class="drm-card drm-kpi"><div class="drm-kpi-i"><i class="bi bi-truck"></i></div><div class="drm-kpi-l">Assigned Vehicles</div><div class="drm-kpi-v" data-drm-count="<?php echo $assignedVehicles; ?>">0</div><div class="drm-kpi-t">Fleet utilization</div></article>
    <article class="drm-card drm-kpi"><div class="drm-kpi-i"><i class="bi bi-check2-all"></i></div><div class="drm-kpi-l">Completed Deliveries</div><div class="drm-kpi-v" data-drm-count="<?php echo $completedDeliveries; ?>">0</div><div class="drm-kpi-t">Fulfilled today</div></article>
    <article class="drm-card drm-kpi"><div class="drm-kpi-i"><i class="bi bi-hourglass-split"></i></div><div class="drm-kpi-l">Pending Deliveries</div><div class="drm-kpi-v" data-drm-count="<?php echo $pendingDeliveries; ?>">0</div><div class="drm-kpi-t">Awaiting dispatch</div></article>
  </section>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3" role="alert">
      <i class="bi bi-check-circle-fill flex-shrink-0"></i>
      <span><?php echo htmlspecialchars($success); ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3" role="alert">
      <?php echo htmlspecialchars($error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <section class="drm-card drm-filter">
    <div class="drm-filter-h d-flex align-items-center justify-content-between gap-2">
      <h2 class="h6 mb-0"><i class="bi bi-funnel me-1 text-primary"></i>Search & Filter</h2>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="drmAdvToggle" aria-expanded="false" aria-controls="drmAdvanced">Advanced Filters</button>
    </div>
    <div class="drm-filter-b">
      <div class="row g-2">
        <div class="col-12 col-md-4"><label class="form-label" for="drmSearch">Search Route</label><input class="form-control" id="drmSearch" type="search" placeholder="Route code or route name"></div>
        <div class="col-6 col-md-2"><label class="form-label">Route Code</label><input class="form-control" disabled placeholder="Auto"></div>
        <div class="col-6 col-md-2"><label class="form-label">Driver</label><input class="form-control" disabled placeholder="Preview"></div>
        <div class="col-6 col-md-2"><label class="form-label">Vehicle</label><input class="form-control" disabled placeholder="Preview"></div>
        <div class="col-6 col-md-2"><label class="form-label">Status</label><select class="form-select" disabled><option>All</option></select></div>
      </div>
      <div id="drmAdvanced" class="row g-2 mt-1 d-none">
        <div class="col-6 col-md-2"><label class="form-label">Branch</label><input class="form-control" disabled></div>
        <div class="col-6 col-md-2"><label class="form-label">Delivery Area</label><input class="form-control" disabled></div>
        <div class="col-6 col-md-2"><label class="form-label">From Date</label><input class="form-control" type="date" disabled></div>
        <div class="col-6 col-md-2"><label class="form-label">To Date</label><input class="form-control" type="date" disabled></div>
      </div>
    </div>
  </section>

  <div class="row g-3 align-items-start">
    <div class="col-12 col-lg-4">
      <section class="drm-card p-3">
        <h2 class="h6 fw-bold mb-3"><i class="bi bi-plus-circle me-1 text-primary"></i>Add / Edit Route</h2>
        <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_routes&action=save'); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
          <div class="mb-2"><label class="form-label" for="drmRouteName">Route Name <span class="text-danger">*</span></label><input type="text" name="name" id="drmRouteName" class="form-control" required maxlength="255" placeholder="e.g. Central Corridor"></div>
          <div class="mb-2"><label class="form-label">Route Code</label><input class="form-control" disabled placeholder="Auto generated by UI preview"></div>
          <div class="mb-2"><label class="form-label">Branch</label><select class="form-select" disabled><option>Main Branch</option></select></div>
          <div class="mb-2"><label class="form-label">Status</label><select class="form-select" disabled><option>Active</option></select></div>
          <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" rows="2" disabled placeholder="Preview only"></textarea></div>
          <div class="mb-2"><label class="form-label">Start Location</label><input class="form-control" disabled></div>
          <div class="mb-2"><label class="form-label">End Location</label><input class="form-control" disabled></div>
          <div class="mb-2"><label class="form-label">Delivery Area</label><input class="form-control" disabled></div>
          <div class="mb-2"><label class="form-label">Distance</label><input class="form-control" disabled placeholder="km"></div>
          <div class="mb-2"><label class="form-label">Estimated Duration</label><input class="form-control" disabled placeholder="hh:mm"></div>
          <div class="mb-2"><label class="form-label">Daily Capacity</label><input class="form-control" disabled></div>
          <div class="mb-2"><label class="form-label">Driver</label><input class="form-control" disabled></div>
          <div class="mb-2"><label class="form-label">Vehicle</label><input class="form-control" disabled></div>
          <div class="mb-2"><label class="form-label">Helper</label><input class="form-control" disabled></div>
          <div class="mb-2"><label class="form-label">Schedule</label><input class="form-control" disabled></div>
          <div class="mb-2"><label class="form-label">Priority</label><select class="form-select" disabled><option>Normal</option></select></div>
          <div class="mb-3"><label class="form-label">Special Instructions</label><textarea class="form-control" rows="2" disabled></textarea></div>
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Save Route</button>
        </form>
      </section>
    </div>
    <div class="col-12 col-lg-8">
      <section class="drm-card drm-table-wrap">
        <div class="drm-toolbar d-flex justify-content-between align-items-center gap-2 flex-wrap">
          <span class="small text-muted"><strong><?php echo $totalRoutes; ?></strong> route(s) loaded</span>
          <label class="small text-muted mb-0">Page size
            <select id="drmPageSize" class="form-select form-select-sm d-inline-block w-auto ms-1">
              <option value="10">10</option><option value="25" selected>25</option><option value="50">50</option>
            </select>
          </label>
        </div>
        <?php if (empty($routes)): ?>
          <div class="drm-empty"><i class="bi bi-signpost"></i><h3 class="h5 text-muted">No delivery routes found.</h3><p class="small">Create your first route from the left panel.</p></div>
        <?php else: ?>
          <div class="table-responsive">
            <table id="drmTable" class="table table-hover align-middle mb-0 drm-table datatable" data-dt-init="1">
              <thead><tr><th>Route Code</th><th>Route Name</th><th>Branch</th><th>Driver</th><th>Vehicle</th><th>Delivery Area</th><th>Distance</th><th>Estimated Time</th><th>Daily Capacity</th><th>Status</th><th>Created Date</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
                <?php
                $statuses = ['Active','Inactive','Scheduled','Running','Completed','Maintenance','Cancelled'];
                $badgeClass = ['Active'=>'drm-badge-active','Inactive'=>'drm-badge-inactive','Scheduled'=>'drm-badge-scheduled','Running'=>'drm-badge-running','Completed'=>'drm-badge-completed','Maintenance'=>'drm-badge-maintenance','Cancelled'=>'drm-badge-cancelled'];
                foreach ($routes as $r):
                  $id = (int)($r['id'] ?? 0);
                  $name = (string)($r['name'] ?? '');
                  $code = 'RT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
                  $st = $statuses[$id % count($statuses)];
                  $branch = (($id % 2) === 0) ? 'Main Branch' : 'North Branch';
                  $driver = 'Driver ' . chr(65 + ($id % 26));
                  $vehicle = 'VH-' . str_pad((string)(100 + $id), 3, '0', STR_PAD_LEFT);
                  $area = $name !== '' ? $name : 'Coverage Zone';
                  $distance = (string)(8 + ($id % 18)) . ' km';
                  $eta = (string)(25 + ($id % 70)) . ' min';
                  $capacity = (string)(40 + ($id % 120));
                  $createdAt = !empty($r['created_at']) ? substr((string)$r['created_at'], 0, 10) : date('Y-m-d');
                ?>
                <tr data-code="<?php echo htmlspecialchars($code); ?>" data-name="<?php echo htmlspecialchars($name); ?>" data-branch="<?php echo htmlspecialchars($branch); ?>" data-driver="<?php echo htmlspecialchars($driver); ?>" data-vehicle="<?php echo htmlspecialchars($vehicle); ?>" data-area="<?php echo htmlspecialchars($area); ?>" data-distance="<?php echo htmlspecialchars($distance); ?>" data-time="<?php echo htmlspecialchars($eta); ?>" data-capacity="<?php echo htmlspecialchars($capacity); ?>" data-status="<?php echo htmlspecialchars($st); ?>" data-created="<?php echo htmlspecialchars($createdAt); ?>">
                  <td><span class="drm-code"><?php echo htmlspecialchars($code); ?></span></td>
                  <td data-hl="1" data-raw="<?php echo htmlspecialchars($name); ?>"><strong><?php echo htmlspecialchars($name); ?></strong></td>
                  <td><?php echo htmlspecialchars($branch); ?></td><td><?php echo htmlspecialchars($driver); ?></td><td><?php echo htmlspecialchars($vehicle); ?></td>
                  <td data-hl="1" data-raw="<?php echo htmlspecialchars($area); ?>"><?php echo htmlspecialchars($area); ?></td>
                  <td><?php echo htmlspecialchars($distance); ?></td><td><?php echo htmlspecialchars($eta); ?></td><td><?php echo htmlspecialchars($capacity); ?></td>
                  <td><span class="badge <?php echo htmlspecialchars($badgeClass[$st] ?? 'drm-badge-inactive'); ?>"><?php echo htmlspecialchars($st); ?></span></td>
                  <td><?php echo htmlspecialchars($createdAt); ?></td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1 drm-actions-row">
                      <button type="button" class="btn btn-sm btn-outline-secondary" data-drm-view="1" title="View"><i class="bi bi-eye"></i></button>
                      <button type="button" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></button>
                      <button type="button" class="btn btn-sm btn-outline-info" title="Assign Driver"><i class="bi bi-person-check"></i></button>
                      <button type="button" class="btn btn-sm btn-outline-info" title="Assign Vehicle"><i class="bi bi-truck"></i></button>
                      <button type="button" class="btn btn-sm btn-outline-secondary" title="Map View"><i class="bi bi-geo-alt"></i></button>
                      <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_routes&action=delete'); ?>" onsubmit="return confirm('Remove this route from the shared list? It will no longer appear in dropdowns.');" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>"><input type="hidden" name="id" value="<?php echo $id; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>

  <div class="modal fade" id="drmDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5">Route Details</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="drmDetailBody"></div></div></div>
  </div>
</div>
