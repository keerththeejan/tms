                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          <?php /** @var array $dn */ /** @var array $items */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Delivery Note #<?php echo (int)$dn['id']; ?></h3>
  <div>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> Next</a>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=print&id='.(int)$dn['id']); ?>" target="_blank" class="btn btn-primary"><i class="bi bi-printer"></i> Print</a>
  </div>
</div>
<div class="card shadow-sm">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-4"><strong>Customer:</strong> <?php echo htmlspecialchars((string)($dn['customer_name'] ?? '')); ?></div>
      <div class="col-md-4"><strong>Phone:</strong> <?php echo htmlspecialchars((string)($dn['customer_phone'] ?? '')); ?></div>
      <div class="col-md-4"><strong>Date:</strong> <?php echo htmlspecialchars((string)($dn['delivery_date'] ?? '')); ?></div>
    </div>
    <div class="row g-2 mt-1">
      <div class="col-md-6"><strong>Supplier(s):</strong> <?php echo htmlspecialchars($dn['suppliers_agg'] ?? ''); ?></div>
      <div class="col-md-6"><strong>Supplier Phone(s):</strong> <?php echo htmlspecialchars($dn['supplier_phones_agg'] ?? ''); ?></div>
    </div>
    <?php 
      // Prefer controller-provided vehicles_agg; otherwise aggregate from items
      $vehList = trim((string)($dn['vehicles_agg'] ?? ''));
      if ($vehList === '') {
        $vehSet = [];
        foreach (($items ?? []) as $it) {
          $v = trim((string)($it['vehicle_no'] ?? ''));
          if ($v !== '') { $vehSet[$v] = true; }
        }
        $vehList = implode(', ', array_keys($vehSet));
      }
    ?>
    <?php if ($vehList !== ''): ?>
    <div class="row g-2 mt-1">
      <div class="col-md-6"><strong>Vehicle(s):</strong> <?php echo htmlspecialchars($vehList); ?></div>
    </div>
    <?php endif; ?>
  </div>
</div>
<div class="table-responsive mt-3">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Supplier</th>
        <th>Supplier Phone</th>
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
        <th colspan="4" class="text-end">Subtotal</th>
        <th class="text-end"><?php echo number_format($subtotal, 2); ?></th>
      </tr>
      <?php if ($disc != 0): ?>
      <tr>
        <th colspan="4" class="text-end">Discount</th>
        <th class="text-end"><?php echo ($disc>0?'+':'').number_format($disc, 2); ?></th>
      </tr>
      <?php endif; ?>
      <tr>
        <th colspan="4" class="text-end">Net Total</th>
        <th class="text-end"><?php echo number_format($net, 2); ?></th>
      </tr>
    </tfoot>
  </table>
</div>
