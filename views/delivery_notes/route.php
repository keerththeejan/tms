<?php /** @var array $routes */ /** @var string $date */ $dir = ($direction ?? 'to'); ?>
<style>
  .dn-route-page {
    --dnr-border: rgba(17, 24, 39, 0.08);
    --dnr-shadow: 0 1px 3px rgba(16, 24, 40, 0.06), 0 10px 28px rgba(15, 23, 42, 0.06);
    --dnr-radius: 14px;
    --dnr-accent: #4f46e5;
  }
  .dn-route-page .dnr-head {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.15rem;
  }
  @media (min-width: 768px) {
    .dn-route-page .dnr-head { flex-direction: row; justify-content: space-between; align-items: flex-start; }
  }
  .dn-route-page .dnr-title {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  .dn-route-page .dnr-title i { color: var(--dnr-accent); }
  .dn-route-page .dnr-sub {
    font-size: 0.88rem;
    color: #64748b;
    margin: 0.35rem 0 0;
    max-width: 42rem;
    line-height: 1.45;
  }
  .dn-route-page .dnr-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
  .dn-route-page .dnr-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.65rem;
    margin-bottom: 1rem;
  }
  .dn-route-page .dnr-stat {
    background: #fff;
    border: 1px solid var(--dnr-border);
    border-radius: var(--dnr-radius);
    padding: 0.65rem 0.85rem;
    box-shadow: var(--dnr-shadow);
  }
  .dn-route-page .dnr-stat .lbl { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
  .dn-route-page .dnr-stat .val { font-weight: 800; font-size: 1.15rem; color: #0f172a; line-height: 1.2; }
  .dn-route-page .dnr-card {
    background: #fff;
    border: 1px solid var(--dnr-border);
    border-radius: var(--dnr-radius);
    box-shadow: var(--dnr-shadow);
    overflow: hidden;
    margin-bottom: 1rem;
  }
  .dn-route-page .dnr-card-h {
    padding: 0.65rem 1rem;
    background: linear-gradient(180deg, #f8fafc, #fff);
    border-bottom: 1px solid var(--dnr-border);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
  }
  .dn-route-page .dnr-filters .form-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.3rem; }
  .dn-route-page .dnr-filters .form-control,
  .dn-route-page .dnr-filters .form-select { border-radius: 10px; font-size: 0.9rem; }
  .dn-route-page .dnr-collapse-toggle {
    font-weight: 700;
    color: #334155;
    text-decoration: none !important;
    width: 100%;
    text-align: left;
  }
  .dn-route-page .dnr-collapse-toggle:hover { color: var(--dnr-accent); }
  .dn-route-page .dnr-hero-add {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.07), rgba(14, 165, 233, 0.05));
    border-bottom: 1px solid var(--dnr-border);
  }
  .dn-route-page .dnr-table thead th {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid var(--dnr-border);
    padding: 0.55rem 0.65rem;
    white-space: nowrap;
  }
  .dn-route-page .dnr-table tbody td { padding: 0.5rem 0.65rem; vertical-align: middle; border-color: rgba(15, 23, 42, 0.06); font-size: 0.88rem; }
  .dn-route-page .dnr-table tbody tr:hover { background: rgba(79, 70, 229, 0.04); }
  .dn-route-page .dnr-actions-cell { min-width: 280px; }
  .dn-route-page .dnr-actions-cell .d-flex { justify-content: flex-end; }
  @media (max-width: 1399px) {
    .dn-route-page .dnr-actions-cell { min-width: 0; }
    .dn-route-page .dnr-actions-cell .d-flex { justify-content: flex-start; }
  }
</style>

<div class="dn-route-page">
  <div class="dnr-head">
    <div>
      <h1 class="dnr-title">
        <i class="bi bi-geo-alt" aria-hidden="true"></i>
        Route planning
      </h1>
      <p class="dnr-sub">
        Assign vehicles, review stops, and generate delivery notes for <strong><?php echo htmlspecialchars($branchName ?? 'your branch'); ?></strong>.
        Filter by date, direction, customer, or location.
      </p>
    </div>
    <div class="dnr-actions">
      <a href="<?php echo Helpers::baseUrl('index.php?page=routes&action=new'); ?>" class="btn btn-primary rounded-pill"><i class="bi bi-signpost me-1"></i> Add route</a>
      <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>" class="btn btn-outline-primary rounded-pill"><i class="bi bi-truck-front me-1"></i> Vehicle routes</a>
    </div>
  </div>

  <?php if (($_GET['saved'] ?? '') === '1'): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 d-flex align-items-center gap-2" role="alert">
      <i class="bi bi-check-circle-fill"></i>
      <span>Vehicle number saved.</span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if (($_GET['loc_saved'] ?? '') === '1'): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 px-3 d-flex align-items-center gap-2" role="alert">
      <i class="bi bi-check-circle-fill"></i>
      <span>Delivery location updated.</span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php elseif (isset($_GET['loc_saved']) && $_GET['loc_saved'] === '0'): ?>
    <div class="alert alert-warning alert-dismissible fade show py-2 px-3" role="alert">
      Could not update delivery location.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

<script>
function assignVehicleAjax(form) {
  var fd = new FormData(form);
  fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (res && res.ok) {
        var cid = fd.get('customer_id');
        var badge = document.getElementById('veh-badge-' + cid);
        if (badge) { badge.innerHTML = '<span class="badge bg-success">'+ String(res.vehicle_no || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') +'</span>'; }
        var addForm = document.getElementById('veh-add-' + cid);
        var btn = document.getElementById('veh-btn-' + cid);
        if (addForm) {
          addForm.id = 'veh-edit-' + cid;
          var h = addForm.querySelector('input[name="vehicle_no"]');
          if (h) h.value = res.vehicle_no || '';
          if (btn) {
            btn.className = 'btn btn-sm btn-outline-success me-1';
            btn.innerHTML = '<i class="bi bi-pencil-square"></i> Edit';
            btn.onclick = function(){
              var f = document.getElementById('veh-edit-' + cid);
              var hv = (f.querySelector('input[name="vehicle_no"]').value || '').trim();
              var v = prompt('Edit vehicle number', hv);
              if (v!==null) { v = v.trim(); if (v) { f.querySelector('input[name="vehicle_no"]').value = v; assignVehicleAjax(f); } else { alert('Enter vehicle number'); } }
            };
          }
        } else {
          var editForm = document.getElementById('veh-edit-' + cid);
          if (editForm) {
            var hh = editForm.querySelector('input[name="vehicle_no"]');
            if (hh) hh.value = res.vehicle_no || '';
          }
        }
      } else {
        alert((res && res.error) ? res.error : 'Failed to save vehicle number');
      }
    })
    .catch(function(){ alert('Failed to save vehicle number'); });
}

function updateLocationAjax(form) {
  var fd = new FormData(form);
  fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (res && res.ok) {
        var cid = fd.get('customer_id');
        var cell = document.getElementById('loc-cell-' + cid);
        if (cell) {
          cell.textContent = String(res.delivery_location || '');
        }
      } else {
        alert((res && res.error) ? res.error : 'Failed to save delivery location');
      }
    })
    .catch(function(){ alert('Failed to save delivery location'); });
}
</script>

  <?php if (isset($customers_total) || isset($branchName)): ?>
  <div class="dnr-stat-grid">
    <div class="dnr-stat">
      <div class="lbl">Branch</div>
      <div class="val" style="font-size:0.95rem;font-weight:700;"><?php echo htmlspecialchars($branchName ?? '—'); ?></div>
    </div>
    <div class="dnr-stat">
      <div class="lbl">Customers (list)</div>
      <div class="val"><?php echo (int)($customers_total ?? 0); ?></div>
    </div>
    <div class="dnr-stat">
      <div class="lbl">Open parcels (branch)</div>
      <div class="val"><?php echo (int)($parcels_total ?? 0); ?></div>
    </div>
    <div class="dnr-stat">
      <div class="lbl">Date</div>
      <div class="val" style="font-size:0.95rem;"><?php echo htmlspecialchars($date ?? date('Y-m-d')); ?></div>
    </div>
  </div>
  <?php endif; ?>

  <div class="dnr-card">
    <div class="dnr-card-h dnr-hero-add py-2">
      <button class="btn btn-link dnr-collapse-toggle py-2" type="button" data-bs-toggle="collapse" data-bs-target="#addRouteOptions" aria-expanded="false">
        <i class="bi bi-plus-circle me-1 text-primary"></i> Add delivery route assignment
      </button>
    </div>
    <div class="collapse" id="addRouteOptions">
      <div class="p-3 border-bottom" style="border-color: var(--dnr-border) !important;">
        <p class="small text-muted mb-3 mb-md-2">Register a customer first, then pick them here to use their address and assign a vehicle for this date.</p>
        <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=assign_vehicle'); ?>" class="row g-3 align-items-end">
          <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
          <input type="hidden" name="direction" value="<?php echo htmlspecialchars($direction ?? 'to'); ?>">
          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Customer</label>
            <select class="form-select" name="customer_id" id="addRouteCustomer" required>
              <option value="">— Select customer —</option>
              <?php foreach (($customersFilter ?? []) as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>" data-address="<?php echo htmlspecialchars((string)($c['delivery_location'] ?? '')); ?>"><?php echo htmlspecialchars(($c['name'] ?? '') . ' (' . ($c['phone'] ?? '') . ')'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold">Address preview</label>
            <div id="addRouteAddress" class="form-control-plaintext small text-secondary border rounded px-2 py-2 bg-light" style="min-height: 2.5rem;">— Pick a customer</div>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold">Date</label>
            <input type="date" name="delivery_date" class="form-control" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold">Vehicle</label>
            <input type="text" name="vehicle_no" class="form-control" placeholder="AB-1234" required>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3"><i class="bi bi-signpost me-1"></i> Assign vehicle</button>
            <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=new'); ?>" class="btn btn-outline-secondary btn-sm ms-0 ms-md-2 mt-2 mt-md-0"><i class="bi bi-person-plus me-1"></i> New customer</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script>
  (function(){
    var sel = document.getElementById('addRouteCustomer');
    var addr = document.getElementById('addRouteAddress');
    if (!sel || !addr) return;
    function update(){
      var opt = sel.options[sel.selectedIndex];
      var a = opt ? (opt.getAttribute('data-address') || '') : '';
      addr.textContent = a ? a : (sel.value ? '— No address on file' : '— Pick a customer');
    }
    sel.addEventListener('change', update);
    update();
  })();
  </script>

  <div class="dnr-card p-0">
    <div class="dnr-card-h"><i class="bi bi-funnel me-1 text-primary"></i> Filters</div>
    <form class="row g-2 g-md-3 p-3 dnr-filters" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
      <input type="hidden" name="page" value="delivery_notes">
      <input type="hidden" name="action" value="route">
      <div class="col-12 col-md-6 col-lg-3">
        <label class="form-label">Direction</label>
        <select class="form-select" name="direction">
          <option value="to" <?php echo ($dir === 'to' ? 'selected' : ''); ?>>Arrivals (to this branch)</option>
          <option value="from" <?php echo ($dir === 'from' ? 'selected' : ''); ?>>Dispatch (from this branch)</option>
        </select>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <label class="form-label">Date</label>
        <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
      </div>
      <div class="col-12 col-lg-4">
        <label class="form-label">Customer</label>
        <div class="d-flex flex-column flex-sm-row gap-2">
          <select class="form-select" name="customer_id" style="min-width: 0;">
            <?php $cfSel = (int)($customer_id ?? 0); ?>
            <option value="0" <?php echo ($cfSel === 0 ? 'selected' : ''); ?>>All customers</option>
            <?php foreach (($customersFilter ?? []) as $c): ?>
              <option value="<?php echo (int)$c['id']; ?>" <?php echo ($cfSel === (int)$c['id'] ? 'selected' : ''); ?>><?php echo htmlspecialchars(($c['name'] ?? '') . ' (' . ($c['phone'] ?? '') . ')'); ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" class="form-control" name="customer" placeholder="Name contains…" value="<?php echo htmlspecialchars($customer ?? ''); ?>">
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-2">
        <label class="form-label">Phone</label>
        <input type="text" class="form-control" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
      </div>
      <div class="col-12 col-md-6 col-lg-4">
        <label class="form-label">Delivery location</label>
        <div class="d-flex flex-column flex-sm-row gap-2">
          <select class="form-select" name="place_sel" style="min-width: 0;">
            <?php $plSel = (string)($place_sel ?? ''); ?>
            <option value="" <?php echo ($plSel === '' ? 'selected' : ''); ?>>All locations</option>
            <?php foreach (($placesFilter ?? []) as $pl): $plV = (string)$pl; ?>
              <option value="<?php echo htmlspecialchars($plV); ?>" <?php echo ($plSel === $plV ? 'selected' : ''); ?>><?php echo htmlspecialchars($plV); ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" class="form-control" name="place" placeholder="Search location…" value="<?php echo htmlspecialchars($place ?? ''); ?>">
        </div>
      </div>
      <div class="col-12 d-flex flex-wrap gap-2 align-items-end pt-1">
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Apply</button>
        <a class="btn btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>">Reset</a>
      </div>
    </form>
  </div>

  <?php if (empty($routes)): ?>
    <div class="dnr-card p-4 text-center">
      <i class="bi bi-inbox display-4 text-primary opacity-25 d-block mb-2"></i>
      <div class="fw-semibold text-dark">No rows to show</div>
      <p class="text-muted small mb-0">Try another date, direction, or filter — or add a route assignment above.</p>
    </div>
  <?php else: ?>
  <div class="dnr-card p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 dnr-table">
        <thead>
          <tr>
            <th style="width:48px;">#</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Delivery location</th>
            <th class="text-center">Parcels</th>
            <th class="text-end">Est. total</th>
            <th class="text-end dnr-actions-cell">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 0; foreach ($routes as $r): $i++; ?>
            <tr>
              <td class="text-muted small"><?php echo $i; ?></td>
              <td class="fw-semibold"><?php echo htmlspecialchars($r['customer_name'] ?? ''); ?></td>
              <td><?php $ph = trim((string)($r['customer_phone'] ?? '')); $showPh = (preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1) ? '' : $ph; echo htmlspecialchars($showPh); ?></td>
              <td id="loc-cell-<?php echo (int)$r['customer_id']; ?>" class="small"><?php echo htmlspecialchars($r['delivery_location'] ?? ''); ?></td>
              <td class="text-center"><span class="badge rounded-pill text-bg-light border"><?php echo (int)($r['parcels_count'] ?? 0); ?></span></td>
              <td class="text-end font-monospace"><?php echo number_format((float)($r['est_total'] ?? 0), 2); ?></td>
              <td class="dnr-actions-cell">
                <div class="d-flex flex-wrap gap-1 justify-content-lg-end">
            <?php $withVeh = (int)($r['with_vehicle'] ?? 0); $pc = (int)($r['parcels_count'] ?? 0); $vehList = array_filter(array_map('trim', explode(',', (string)($r['veh_list'] ?? '')))); $planned = trim((string)($r['planned_vehicle'] ?? '')); $vehCurrent = '';
              if (!empty($vehList)) { $vehCurrent = (string)($vehList[0] ?? ''); }
              elseif ($planned !== '') { $vehCurrent = $planned; }
            ?>
            <?php if ($vehCurrent !== ''): ?>
              <span id="veh-badge-<?php echo (int)$r['customer_id']; ?>" class="me-1"><span class="badge bg-success rounded-pill"><?php echo htmlspecialchars($vehCurrent); ?></span></span>
              <form id="veh-edit-<?php echo (int)$r['customer_id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=assign_vehicle'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
                <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
                <input type="hidden" name="direction" value="<?php echo htmlspecialchars($dir); ?>">
                <input type="hidden" name="vehicle_no" value="<?php echo htmlspecialchars($vehCurrent); ?>">
              </form>
              <button id="veh-btn-<?php echo (int)$r['customer_id']; ?>" type="button" class="btn btn-sm btn-outline-success" onclick="(function(f){var v=prompt('Edit vehicle number', f.vehicle_no.value); if(v!==null){v=v.trim(); if(v){f.vehicle_no.value=v; assignVehicleAjax(f);} else {alert('Enter vehicle number');}}})(document.getElementById('veh-edit-<?php echo (int)$r['customer_id']; ?>'));"><i class="bi bi-pencil-square"></i><span class="d-none d-xxl-inline"> Vehicle</span></button>
            <?php elseif ($pc > 0): ?>
              <span id="veh-badge-<?php echo (int)$r['customer_id']; ?>" class="me-1"></span>
              <form id="veh-add-<?php echo (int)$r['customer_id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=assign_vehicle'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
                <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
                <input type="hidden" name="direction" value="<?php echo htmlspecialchars($dir); ?>">
                <input type="hidden" name="vehicle_no" value="">
              </form>
              <button id="veh-btn-<?php echo (int)$r['customer_id']; ?>" type="button" class="btn btn-sm btn-success" onclick="(function(f){var v=prompt('Enter vehicle number'); if(v!==null){v=v.trim(); if(v){f.vehicle_no.value=v; assignVehicleAjax(f);} else {alert('Enter vehicle number');}}})(document.getElementById('veh-add-<?php echo (int)$r['customer_id']; ?>'));"><i class="bi bi-truck"></i><span class="d-none d-xxl-inline"> Add</span></button>
            <?php else: ?>
              <span id="veh-badge-<?php echo (int)$r['customer_id']; ?>" class="me-1"></span>
              <form id="veh-add-<?php echo (int)$r['customer_id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=assign_vehicle'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
                <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
                <input type="hidden" name="direction" value="<?php echo htmlspecialchars($dir); ?>">
                <input type="hidden" name="vehicle_no" value="">
              </form>
              <button id="veh-btn-<?php echo (int)$r['customer_id']; ?>" type="button" class="btn btn-sm btn-success" onclick="(function(f){var v=prompt('Enter vehicle number'); if(v!==null){v=v.trim(); if(v){f.vehicle_no.value=v; assignVehicleAjax(f);} else {alert('Enter vehicle number');}}})(document.getElementById('veh-add-<?php echo (int)$r['customer_id']; ?>'));"><i class="bi bi-truck"></i><span class="d-none d-xxl-inline"> Add</span></button>
            <?php endif; ?>

            <button type="button" class="btn btn-sm btn-outline-info" onclick="openPreviousBill(<?php echo (int)$r['customer_id']; ?>, '<?php echo htmlspecialchars($date ?? date('Y-m-d'), ENT_QUOTES); ?>')"><i class="bi bi-receipt"></i><span class="d-none d-xxl-inline"> Bill</span></button>
            <a class="btn btn-sm btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=customers&action=edit&id=' . (int)$r['customer_id']); ?>" target="_blank" title="Edit customer"><i class="bi bi-person-lines-fill"></i></a>
            <form class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=generate'); ?>" onsubmit="return confirm('Generate delivery note for this customer on selected date?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
              <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
              <input type="hidden" name="direction" value="<?php echo htmlspecialchars($dir); ?>">
              <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-clipboard-check"></i><span class="d-none d-xxl-inline"> DN</span></button>
            </form>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=search&phone=' . urlencode($r['customer_phone'] ?? '')); ?>" target="_blank" title="Search"><i class="bi bi-search"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="previousBillModal" tabindex="-1" aria-labelledby="previousBillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-bottom" style="background: linear-gradient(180deg,#f8fafc,#fff);">
          <h5 class="modal-title fw-bold" id="previousBillModalLabel"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Previous bill</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="previousBillModalBody">
          <div class="text-center text-muted py-4">Loading…</div>
        </div>
      </div>
    </div>
  </div>
  <script>
  function openPreviousBill(customerId, deliveryDate) {
    var modal = document.getElementById('previousBillModal');
    var body = document.getElementById('previousBillModalBody');
    if (!modal || !body) return;
    body.innerHTML = '<div class="text-center text-muted py-4">Loading…</div>';
    var bsModal = window.bootstrap && bootstrap.Modal ? new bootstrap.Modal(modal) : null;
    if (bsModal) bsModal.show();
    var url = '<?php echo Helpers::baseUrl('index.php'); ?>?page=delivery_notes&action=previous_bill&customer_id=' + encodeURIComponent(customerId);
    if (deliveryDate) url += '&delivery_date=' + encodeURIComponent(deliveryDate);
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r) { return r.text(); })
      .then(function(html) {
        body.innerHTML = html || '<div class="alert alert-warning mb-0">No previous bill found.</div>';
      })
      .catch(function() {
        body.innerHTML = '<div class="alert alert-danger mb-0">Failed to load previous bill.</div>';
      });
  }
  </script>
  <?php endif; ?>
</div>
