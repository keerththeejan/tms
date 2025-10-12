<?php /** @var array $dn */ /** @var array $items */ $isEmbed = (($_GET['embed'] ?? '') === '1'); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
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
    <?php if ($isEmbed): ?>
    /* Tighter tables when embedded */
    .table { margin-bottom: .75rem; }
    h4, .doc-title { margin-bottom: .25rem; }
    <?php endif; ?>
  </style>
</head>
<?php if (!$isEmbed): ?>
  <div class="no-print mb-3">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
  </div>
<?php endif; ?>
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
<table class="table table-sm table-bordered align-middle">
  <thead>
    <tr>
      <th style="width:6rem;">#</th>
      <th style="width:11rem;">Delivery Date</th>
      <th>Customer</th>
      <th style="width:12rem;">Phone</th>
      <th>Supplier</th>
      <th style="width:12rem;">Supplier Phone</th>
      <th>Tracking</th>
      <th>Weight</th>
      <th style="width:10rem;" class="text-end">Amount</th>
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
</script>
<?php endif; ?>
</body>
</html>
