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
      <div class="col-md-4"><strong>Customer:</strong> <?php echo htmlspecialchars($dn['customer_name']); ?></div>
      <div class="col-md-4"><strong>Phone:</strong> <?php echo htmlspecialchars($dn['customer_phone']); ?></div>
      <div class="col-md-4"><strong>Date:</strong> <?php echo htmlspecialchars($dn['delivery_date']); ?></div>
    </div>
    <div class="row g-2 mt-1">
      <div class="col-md-6"><strong>Supplier(s):</strong> <?php echo htmlspecialchars($dn['suppliers_agg'] ?? ''); ?></div>
      <div class="col-md-6"><strong>Supplier Phone(s):</strong> <?php echo htmlspecialchars($dn['supplier_phones_agg'] ?? ''); ?></div>
    </div>
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
</div>
