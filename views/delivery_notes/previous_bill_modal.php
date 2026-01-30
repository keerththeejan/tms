<?php
/** @var array $dn */ /** @var array $items */
$cfg = (require __DIR__ . '/../../config/config.php');
$brand = $cfg['company'] ?? [];
$addresses = $brand['addresses'] ?? [];
if (empty($addresses) && !empty($brand['branches'])) {
    foreach ($brand['branches'] as $b) {
        $addresses[] = ($b['name'] ?? '') . ': ' . ($b['address_en'] ?? '') . ' | ' . ($b['phones'] ?? '');
    }
}
$total = 0.0;
foreach ($items as $i) { $total += (float)$i['amount']; }
$disc = (float)($dn['discount'] ?? 0);
$subtotal = $total;
if ($subtotal <= 0 && isset($dn['total_amount'])) { $subtotal = (float)$dn['total_amount']; }
$net = $subtotal + $disc;
$deliveryDate = (string)($dn['delivery_date'] ?? '');
$customerName = (string)($dn['customer_name'] ?? '');
$customerPhone = (string)($dn['customer_phone'] ?? '');
$branchName = (string)($dn['branch_name'] ?? '');
?>
<div class="previous-bill-content">
  <div class="mb-2">
    <div class="fw-bold fs-5"><?php echo htmlspecialchars($brand['name'] ?? 'TS Transport'); ?></div>
    <div class="text-muted small">Transport and Parcel Services</div>
    <?php if (!empty($addresses)): ?>
      <div class="small text-secondary mt-1">
        <?php echo htmlspecialchars(implode(' &nbsp;|&nbsp; ', array_slice($addresses, 0, 3))); ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="row g-2 mb-2">
    <div class="col-md-6">
      <strong>Delivery Note #<?php echo (int)$dn['id']; ?></strong>
      <span class="text-muted ms-1">Date: <?php echo htmlspecialchars($deliveryDate); ?></span>
    </div>
    <div class="col-md-6 text-md-end">
      <strong>Customer:</strong> <?php echo htmlspecialchars($customerName); ?>
      <span class="text-muted"><?php echo htmlspecialchars($customerPhone); ?></span>
      <?php if ($branchName !== ''): ?>
        <span class="badge bg-secondary"><?php echo htmlspecialchars($branchName); ?></span>
      <?php endif; ?>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0">
      <thead class="table-light">
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
        <?php foreach ($items as $i): ?>
          <tr>
            <td><?php echo (int)$i['parcel_id']; ?></td>
            <td><?php echo htmlspecialchars($deliveryDate); ?></td>
            <td><?php echo htmlspecialchars($customerName); ?></td>
            <td><?php echo htmlspecialchars($customerPhone); ?></td>
            <td><?php echo htmlspecialchars($i['supplier_name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($i['supplier_phone'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($i['tracking_number'] ?? ''); ?></td>
            <td><?php $d = trim((string)($i['item_descriptions'] ?? '')); echo $d !== '' ? htmlspecialchars($d) : '—'; ?></td>
            <td><?php echo number_format((float)($i['weight'] ?? 0), 2); ?></td>
            <td class="text-end"><?php echo number_format((float)$i['amount'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="table-light">
          <td colspan="9" class="text-end fw-bold">Subtotal</td>
          <td class="text-end fw-bold"><?php echo number_format($subtotal, 2); ?></td>
        </tr>
        <tr class="table-light">
          <td colspan="9" class="text-end fw-bold">Net Total</td>
          <td class="text-end fw-bold"><?php echo number_format($net, 2); ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
