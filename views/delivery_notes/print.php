<?php /** @var array $dn */ /** @var array $items */ $isEmbed = (($_GET['embed'] ?? '') === '1'); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Delivery Note #<?php echo (int)$dn['id']; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    @media print { .no-print { display: none !important; } }
    body { padding: <?php echo $isEmbed ? '8px' : '20px'; ?>; }
    .doc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .75rem; }
    .doc-title { font-weight: 700; font-size: 1.25rem; }
    .muted { color: #6c757d; }
    .amount { text-align: right; }
    .table-sm th, .table-sm td { padding-top: .35rem; padding-bottom: .35rem; }
    @media (max-width: 576px) {
      .doc-header { flex-direction: column; gap: .5rem; }
      .table { font-size: .9rem; }
    }
    <?php if ($isEmbed): ?>
    /* Tighter tables when embedded */
    .table { margin-bottom: .75rem; }
    h4, .doc-title { margin-bottom: .25rem; }
    <?php endif; ?>
  </style>
</head>
<?php if (!$isEmbed): ?>
  <div class="no-print mb-3">
    <div class="d-flex flex-wrap gap-2 mb-2">
      <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      <button id="toggleAddrEditor" class="btn btn-outline-secondary" type="button"><i class="bi bi-pencil-square"></i> Edit Header Addresses</button>
    </div>
    <div id="addrEditor" style="display:none">
      <div class="card card-body p-2">
        <div class="mb-2 small text-muted">Enter one address per line. Changes affect only this print.</div>
        <textarea id="addrTextarea" class="form-control" rows="4"></textarea>
        <div class="mt-2 d-flex gap-2">
          <button id="applyAddr" class="btn btn-success" type="button"><i class="bi bi-check"></i> Apply</button>
          <button id="applyAndPrint" class="btn btn-primary" type="button"><i class="bi bi-printer"></i> Apply & Print</button>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php 
  // Company branding for print header
  $cfg = (require __DIR__ . '/../../config/config.php'); 
  $brand = $cfg['company'] ?? []; 
  // Optional one-off address override via query (?addr=line1\nline2)
  $addrParam = (string)($_GET['addr'] ?? '');
  if ($addrParam !== '') { $addrParam = str_replace(["\r"], '', $addrParam); }
  $addresses = [];
  if ($addrParam !== '') {
    $tmp = explode("\n", $addrParam);
    foreach ($tmp as $a) { $a = trim($a); if ($a !== '') { $addresses[] = $a; } }
  } else {
    foreach (($brand['addresses'] ?? []) as $a) { $a = trim((string)$a); if ($a !== '') { $addresses[] = $a; } }
  }
?>
<div class="mb-2 p-2 border rounded">
  <div class="d-flex align-items-center gap-2">
    <?php if (!empty($brand['logo_url'])): ?>
      <img src="<?php echo htmlspecialchars($brand['logo_url']); ?>" alt="Logo" style="height:38px">
    <?php endif; ?>
    <div>
      <div class="fw-bold"><?php echo htmlspecialchars($brand['name'] ?? ''); ?></div>
      <div class="small text-muted">Transport and Parcel Services</div>
    </div>
  </div>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-1 mt-1 small text-muted" id="addrContainer">
    <?php foreach ($addresses as $addr): ?>
      <div class="addr-line"><?php echo nl2br(htmlspecialchars($addr)); ?></div>
    <?php endforeach; ?>
  </div>
</div>
<?php 
  // Build distinct vehicle list if present on items
  $vehSet = [];
  foreach (($items ?? []) as $it) {
    $v = trim((string)($it['vehicle_no'] ?? ''));
    if ($v !== '') { $vehSet[$v] = true; }
  }
  $vehList = implode(', ', array_keys($vehSet));
?>
<div class="doc-header">
  <div>
    <div class="doc-title">Delivery Note #<?php echo (int)$dn['id']; ?></div>
    <div class="muted">Date: <?php echo htmlspecialchars($dn['delivery_date'] ?? ''); ?></div>
  </div>
  <div class="text-end">
    <div><strong>Customer:</strong> <?php echo htmlspecialchars($dn['customer_name'] ?? ''); ?></div>
    <div class="muted"><strong>Phone:</strong> &lrm;<?php echo htmlspecialchars($dn['customer_phone'] ?? ''); ?></div>
    <div class="muted"><strong>Branch:</strong> <?php echo htmlspecialchars($dn['branch_name'] ?? ''); ?></div>
    <?php if ($vehList !== ''): ?><div class="muted"><strong>Vehicle(s):</strong> <?php echo htmlspecialchars($vehList); ?></div><?php endif; ?>
  </div>
</div>
<div class="table-responsive">
<table class="table table-sm table-bordered align-middle mb-0">
  <thead>
    <tr>
      <th>#</th>
      <th>Delivery Date</th>
      <th>Customer</th>
      <th>Phone</th>
      <th>Supplier</th>
      <th>Supplier Phone</th>
      <th>Tracking</th>
      <th>Items</th>
      <th>Weight</th>
      <th class="text-end">Amount</th>
    </tr>
  </thead>
  <tbody>
  <?php $total = 0; foreach ($items as $i): $total += (float)$i['amount']; ?>
    <tr>
      <td><?php echo (int)$dn['id']; ?></td>
      <td><?php echo htmlspecialchars($dn['delivery_date']); ?></td>
      <td><?php echo htmlspecialchars($dn['customer_name']); ?></td>
      <td><?php echo htmlspecialchars($dn['customer_phone']); ?></td>
      <td><?php echo htmlspecialchars($i['supplier_name'] ?? ''); ?></td>
      <td><?php echo htmlspecialchars($i['supplier_phone'] ?? ''); ?></td>
      <td><?php echo htmlspecialchars($i['tracking_number'] ?? ''); ?></td>
      <td><?php $desc = trim((string)($i['item_descriptions'] ?? '')); echo $desc !== '' ? htmlspecialchars($desc) : '—'; ?></td>
      <td><?php echo number_format((float)($i['weight'] ?? 0), 2); ?></td>
      <td class="text-end"><?php echo number_format((float)$i['amount'], 2); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <?php 
      $disc = (float)($dn['discount'] ?? 0);
      $subtotal = (float)$total;
      if ($subtotal <= 0 && isset($dn['total_amount'])) { $subtotal = (float)$dn['total_amount']; }
      $net = $subtotal + $disc;
    ?>
    <tr>
      <th colspan="8" class="text-end">Subtotal</th>
      <th class="text-end"><?php echo number_format($subtotal, 2); ?></th>
    </tr>
    <?php if ($disc != 0): ?>
    <tr>
      <th colspan="8" class="text-end">Discount</th>
      <th class="text-end"><?php echo ($disc>0?'+':'').number_format($disc, 2); ?></th>
    </tr>
    <?php endif; ?>
    <tr>
      <th colspan="8" class="text-end">Net Total</th>
      <th class="text-end"><?php echo number_format($net, 2); ?></th>
    </tr>
  </tfoot>
</table>
</div>
<?php if (!$isEmbed): ?>
<script>
  // Auto-trigger print on load for faster workflow (disabled when embedded)
  window.addEventListener('load', function(){
    setTimeout(function(){
      if (window.matchMedia) { window.print(); }
    }, 50);
  });
  // Optional: close the tab after printing if the browser supports it
  window.addEventListener('afterprint', function(){ /* window.close(); */ });

  // Address editor logic
  (function(){
    var toggleBtn = document.getElementById('toggleAddrEditor');
    var ed = document.getElementById('addrEditor');
    var ta = document.getElementById('addrTextarea');
    if (!toggleBtn || !ed || !ta) return;
    function getCurrentLines(){
      var nodes = document.querySelectorAll('#addrContainer .addr-line');
      var arr = [];
      for (var i=0;i<nodes.length;i++){ var t = nodes[i].textContent.trim(); if(t) arr.push(t); }
      return arr;
    }
    // Prefill
    ta.value = getCurrentLines().join('\n');
    toggleBtn.addEventListener('click', function(){ ed.style.display = (ed.style.display==='none' || ed.style.display==='') ? 'block' : 'none'; });
    function applyAddrs(){
      var cont = document.getElementById('addrContainer');
      if (!cont) return;
      var val = ta.value.replace(/\r/g,'');
      var parts = val.split('\n').map(function(s){ return s.trim(); }).filter(function(s){ return s.length>0; });
      cont.innerHTML = '';
      parts.forEach(function(line){
        var d = document.createElement('div');
        d.className = 'addr-line';
        d.textContent = line;
        cont.appendChild(d);
      });
    }
    document.getElementById('applyAddr').addEventListener('click', function(){ applyAddrs(); });
    document.getElementById('applyAndPrint').addEventListener('click', function(){ applyAddrs(); window.print(); });
  })();
</script>
<?php endif; ?>
</body>
</html>
