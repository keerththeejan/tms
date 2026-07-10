<?php /** @var string $vehicle_no */ /** @var array $grouped */ /** @var array $customers */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Route Detail<?php echo $vehicle_no!==''? ' - ' . htmlspecialchars($vehicle_no):''; ?></h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="delivery_notes">
  <input type="hidden" name="action" value="route_detail">
  <div class="col-md-3">
    <label class="form-label">Vehicle</label>
    <input type="text" class="form-control" name="vehicle_no" value="<?php echo htmlspecialchars($vehicle_no ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">From</label>
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? date('Y-m-d')); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">To</label>
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? date('Y-m-d')); ?>">
  </div>
  <div class="col-auto d-flex align-items-end">
    <button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button>
  </div>
</form>
<div class="mb-3">
  <form id="bulkPrintForm" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=print_manifest'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="vehicle_no" value="<?php echo htmlspecialchars($vehicle_no ?? ''); ?>">
    <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? date('Y-m-d')); ?>">
    <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? date('Y-m-d')); ?>">
    <button type="submit" class="btn btn-primary"><i class="bi bi-printer"></i> Print Selected</button>
  </form>
</div>
<script>
  (function(){
    var f = document.getElementById('bulkPrintForm');
    if (!f) return;
    f.addEventListener('submit', function(e){
      if (!f.querySelector('input[name="customer_ids[]"]')) {
        e.preventDefault();
        alert('Please select at least one customer to print.');
      }
    });
  })();
  </script>
<?php if (empty($grouped)): ?>
  <div class="alert alert-info">No parcels found for the selected filters.</div>
<?php else: ?>
  <?php foreach ($grouped as $cid => $rows): $c = $customers[$cid] ?? ['name'=>'','phone'=>'']; ?>
    <div class="card shadow-sm mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <input type="checkbox" class="form-check-input me-2" onchange="(function(cb){var f=document.getElementById('bulkPrintForm'); if(!f) return; if(cb.checked){ var i=document.createElement('input'); i.type='hidden'; i.name='customer_ids[]'; i.value='<?php echo (int)$cid; ?>'; i.id='cust-<?php echo (int)$cid; ?>'; f.appendChild(i);} else { var x=document.getElementById('cust-<?php echo (int)$cid; ?>'); if(x) x.remove(); }})(this)">
          <strong><?php echo htmlspecialchars($c['name'] ?? ''); ?></strong>
          <span class="text-muted ms-2"><?php echo htmlspecialchars($c['phone'] ?? ''); ?></span>
        </div>
        
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>From</th>
              <th>To</th>
              <th>Weight</th>
              <th>Price</th>
              <th>Vehicle</th>
              <th>Status</th>
              <th>Tracking</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $p): ?>
            <tr>
              <td><?php echo (int)$p['id']; ?></td>
              <td><?php echo htmlspecialchars($p['created_at']); ?></td>
              <td><?php echo (int)$p['from_branch_id']; ?></td>
              <td><?php echo (int)$p['to_branch_id']; ?></td>
              <td><?php echo number_format((float)$p['weight'], 2); ?></td>
              <td><?php echo is_null($p['price']) ? '-' : number_format((float)$p['price'], 2); ?></td>
              <td><?php echo ($p['vehicle_no'] ?? '') !== '' ? htmlspecialchars($p['vehicle_no']) : '—'; ?></td>
              <td><?php echo htmlspecialchars($p['status']); ?></td>
              <td><?php echo htmlspecialchars($p['tracking_number'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
