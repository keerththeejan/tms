<?php /** @var array $dn */ /** @var array $items */ ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Delivery Note #<?php echo (int)$dn['id']; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    @media print { .no-print { display: none !important; } }
    body { padding: 20px; }
  </style>
</head>
<body>
<div class="no-print mb-3">
  <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>
<h4 class="mb-1">Delivery Note #<?php echo (int)$dn['id']; ?></h4>
<div class="mb-3 text-muted">Branch: <?php echo htmlspecialchars($dn['branch_name'] ?? ''); ?> | Date: <?php echo htmlspecialchars($dn['delivery_date']); ?></div>
<div class="row g-2 mb-3">
  <div class="col-md-4"><strong>Customer:</strong> <?php echo htmlspecialchars($dn['customer_name']); ?></div>
  <div class="col-md-4"><strong>Phone:</strong> <?php echo htmlspecialchars($dn['customer_phone']); ?></div>
</div>
<table class="table table-sm table-bordered align-middle">
  <thead>
    <tr>
      <th>#</th>
      <th>Supplier</th>
      <th>Tracking</th>
      <th>Weight</th>
      <th class="text-end">Amount</th>
    </tr>
  </thead>
  <tbody>
    <?php $total = 0; foreach ($items as $i): $total += (float)$i['amount']; ?>
      <tr>
        <td><?php echo (int)$i['parcel_id']; ?></td>
        <td><?php echo htmlspecialchars($i['supplier_name'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($i['tracking_number'] ?? ''); ?></td>
        <td><?php echo number_format((float)($i['weight'] ?? 0), 2); ?></td>
        <td class="text-end"><?php echo number_format((float)$i['amount'], 2); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr>
      <th colspan="4" class="text-end">Total</th>
      <th class="text-end"><?php 
        $grand = (float)$total; 
        if ($grand <= 0 && isset($dn['total_amount'])) { $grand = (float)$dn['total_amount']; }
        echo number_format($grand, 2); 
      ?></th>
    </tr>
  </tfoot>
</table>
<script>
  // Auto-trigger print on load for faster workflow
  window.addEventListener('load', function(){
    setTimeout(function(){
      if (window.matchMedia) { window.print(); }
    }, 300);
  });
  // Optional: close the tab after printing if the browser supports it
  window.addEventListener('afterprint', function(){ /* window.close(); */ });
</script>
</body>
</html>
