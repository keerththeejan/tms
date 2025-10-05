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
<div class="no-print mb-3">
  <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>
<h4 class="mb-1">Delivery Note</h4>
<div class="mb-2 text-muted">Branch: <?php echo htmlspecialchars($dn['branch_name'] ?? ''); ?></div>
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
    <tr>
      <th colspan="8" class="text-end">Total</th>
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
{{ ... }}
  });
  // Optional: close the tab after printing if the browser supports it
  window.addEventListener('afterprint', function(){ /* window.close(); */ });
</script>
</body>
</html>
