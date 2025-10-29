<?php /** @var array $routes */ /** @var string $date */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Delivery Route Planning</h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=routes&action=new'); ?>" class="btn btn-primary"><i class="bi bi-signpost"></i> Add Route</a>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>" class="btn btn-outline-primary"><i class="bi bi-truck-front"></i> Vehicle Routes</a>
  </div>
</div>
<?php if (($_GET['saved'] ?? '') === '1'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  Vehicle number saved successfully.
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
        // Switch Add -> Edit if needed
        var addForm = document.getElementById('veh-add-' + cid);
        var btn = document.getElementById('veh-btn-' + cid);
        if (addForm) {
          // convert to edit form
          addForm.id = 'veh-edit-' + cid;
          // ensure hidden value holds latest vehicle
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
          // already edit form: just set latest hidden value
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
<div class="row g-3 mb-3">
  <div class="col-sm-6">
    <div class="border rounded p-2 bg-light">
      <div class="text-muted small">Branch</div>
      <div class="fw-semibold"><?php echo htmlspecialchars($branchName ?? ''); ?></div>
    </div>
  </div>
  <div class="col-sm-6">
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
    <label class="form-label">Direction</label>
    <?php $dir = ($direction ?? 'to'); ?>
    <select class="form-select" name="direction">
      <option value="to" <?php echo ($dir==='to'?'selected':''); ?>>Arrivals (to this branch)</option>
      <option value="from" <?php echo ($dir==='from'?'selected':''); ?>>Dispatch (from this branch)</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Delivery Date</label>
    <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
  </div>
  <div class="col-12 col-md-5 col-lg-4">
    <label class="form-label">Customer</label>
    <div class="d-flex gap-2">
      <select class="form-select" name="customer_id" style="min-width: 220px;">
        <?php $cfSel = (int)($customer_id ?? 0); ?>
        <option value="0" <?php echo ($cfSel===0?'selected':''); ?>>All Customers</option>
        <?php foreach (($customersFilter ?? []) as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php echo ($cfSel===(int)$c['id']?'selected':''); ?>><?php echo htmlspecialchars(($c['name'] ?? '').' ('.($c['phone'] ?? '').')'); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" class="form-control" name="customer" placeholder="Name" value="<?php echo htmlspecialchars($customer ?? ''); ?>">
    </div>
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label">Phone</label>
    <input type="text" class="form-control" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
  </div>
  <div class="col-12 col-md-5 col-lg-4">
    <label class="form-label">Delivery Location</label>
    <div class="d-flex gap-2">
      <select class="form-select" name="place_sel" style="min-width: 220px;">
        <?php $plSel = (string)($place_sel ?? ''); ?>
        <option value="" <?php echo ($plSel===''?'selected':''); ?>>All Locations</option>
        <?php foreach (($placesFilter ?? []) as $pl): $plV = (string)$pl; ?>
          <option value="<?php echo htmlspecialchars($plV); ?>" <?php echo ($plSel===$plV?'selected':''); ?>><?php echo htmlspecialchars($plV); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" class="form-control" name="place" placeholder="Location" value="<?php echo htmlspecialchars($place ?? ''); ?>">
    </div>
  </div>
  <div class="col-auto d-flex align-items-end gap-2">
    <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
    <a class="btn btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>">Clear</a>
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
          <td><?php $ph = trim((string)($r['customer_phone'] ?? '')); $showPh = (preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1) ? '' : $ph; echo htmlspecialchars($showPh); ?></td>
          <td id="loc-cell-<?php echo (int)$r['customer_id']; ?>"><?php echo htmlspecialchars($r['delivery_location'] ?? ''); ?></td>
          <td class="text-center"><?php echo (int)($r['parcels_count'] ?? 0); ?></td>
          <td class="text-end"><?php echo number_format((float)($r['est_total'] ?? 0), 2); ?></td>
          <td class="text-end">
            <?php $withVeh = (int)($r['with_vehicle'] ?? 0); $pc = (int)($r['parcels_count'] ?? 0); $vehList = array_filter(array_map('trim', explode(',', (string)($r['veh_list'] ?? '')))); $planned = trim((string)($r['planned_vehicle'] ?? '')); $vehCurrent = '';
              if (!empty($vehList)) { $vehCurrent = (string)($vehList[0] ?? ''); }
              elseif ($planned !== '') { $vehCurrent = $planned; }
            ?>
            <?php if ($vehCurrent !== ''): ?>
              <span id="veh-badge-<?php echo (int)$r['customer_id']; ?>" class="me-2"><span class="badge bg-success"><?php echo htmlspecialchars($vehCurrent); ?></span></span>
              <form id="veh-edit-<?php echo (int)$r['customer_id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=assign_vehicle'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
                <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
                <input type="hidden" name="direction" value="<?php echo htmlspecialchars($dir); ?>">
                <input type="hidden" name="vehicle_no" value="<?php echo htmlspecialchars($vehCurrent); ?>">
              </form>
              <button id="veh-btn-<?php echo (int)$r['customer_id']; ?>" type="button" class="btn btn-sm btn-outline-success me-1" onclick="(function(f){var v=prompt('Edit vehicle number', f.vehicle_no.value); if(v!==null){v=v.trim(); if(v){f.vehicle_no.value=v; assignVehicleAjax(f);} else {alert('Enter vehicle number');}}})(document.getElementById('veh-edit-<?php echo (int)$r['customer_id']; ?>'));"><i class="bi bi-pencil-square"></i> Edit Vehicle</button>
            <?php elseif ($pc > 0): ?>
              <span id="veh-badge-<?php echo (int)$r['customer_id']; ?>" class="me-2"></span>
              <form id="veh-add-<?php echo (int)$r['customer_id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=assign_vehicle'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
                <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
                <input type="hidden" name="direction" value="<?php echo htmlspecialchars($dir); ?>">
                <input type="hidden" name="vehicle_no" value="">
              </form>
              <button id="veh-btn-<?php echo (int)$r['customer_id']; ?>" type="button" class="btn btn-sm btn-success" onclick="(function(f){var v=prompt('Enter vehicle number'); if(v!==null){v=v.trim(); if(v){f.vehicle_no.value=v; assignVehicleAjax(f);} else {alert('Enter vehicle number');}}})(document.getElementById('veh-add-<?php echo (int)$r['customer_id']; ?>'));"><i class="bi bi-truck"></i> Add Vehicle</button>
            <?php else: ?>
              <span id="veh-badge-<?php echo (int)$r['customer_id']; ?>" class="me-2"></span>
              <form id="veh-add-<?php echo (int)$r['customer_id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=assign_vehicle'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
                <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
                <input type="hidden" name="vehicle_no" value="">
              </form>
              <button id="veh-btn-<?php echo (int)$r['customer_id']; ?>" type="button" class="btn btn-sm btn-success" onclick="(function(f){var v=prompt('Enter vehicle number'); if(v!==null){v=v.trim(); if(v){f.vehicle_no.value=v; assignVehicleAjax(f);} else {alert('Enter vehicle number');}}})(document.getElementById('veh-add-<?php echo (int)$r['customer_id']; ?>'));"><i class="bi bi-truck"></i> Add Vehicle</button>
            <?php endif; ?>
            <!-- Inline add/edit Delivery Location -->
            <form id="loc-edit-<?php echo (int)$r['customer_id']; ?>" class="d-none" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=update_location'); ?>">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
              <input type="hidden" name="delivery_location" value="<?php echo htmlspecialchars((string)($r['delivery_location'] ?? '')); ?>">
            </form>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="(function(f){var cur=(f.delivery_location.value||'').trim(); var v=prompt('Enter delivery location', cur); if(v!==null){v=v.trim(); f.delivery_location.value=v; updateLocationAjax(f);}})(document.getElementById('loc-edit-<?php echo (int)$r['customer_id']; ?>'));"><i class="bi bi-geo-alt"></i> <?php echo ($r['delivery_location'] ?? '')!=='' ? 'Edit' : 'Add'; ?> Location</button>

            <!-- Inline add/edit Phone -->
            <form id="phone-edit-<?php echo (int)$r['customer_id']; ?>" class="d-none" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=update_phone'); ?>">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
              <input type="hidden" name="phone" value="<?php echo htmlspecialchars((string)$showPh); ?>">
            </form>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="(function(f){var cur=(f.phone.value||'').trim(); var v=prompt('Enter phone', cur); if(v!==null){v=v.trim(); f.phone.value=v; (function(fd){fetch(f.action,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd}).then(r=>r.json()).then(function(res){ if(res&&res.ok){ location.reload(); } else { alert(res&&res.error?res.error:'Failed to save phone'); } }).catch(function(){ alert('Failed to save phone'); });})(new FormData(f)); }})(document.getElementById('phone-edit-<?php echo (int)$r['customer_id']; ?>'));"><i class="bi bi-telephone"></i> <?php echo ($showPh !== '') ? 'Edit' : 'Add'; ?> Phone</button>

            <!-- Full customer edit page -->
            <a class="btn btn-sm btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=customers&action=edit&id='.(int)$r['customer_id']); ?>" target="_blank"><i class="bi bi-person-lines-fill"></i> Edit</a>
            <form class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=generate'); ?>" onsubmit="return confirm('Generate delivery note for this customer on selected date?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="customer_id" value="<?php echo (int)$r['customer_id']; ?>">
              <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($date ?? date('Y-m-d')); ?>">
              <input type="hidden" name="direction" value="<?php echo htmlspecialchars($dir); ?>">
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
