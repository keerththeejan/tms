<?php
/** @var array $parcels */ /** @var string $from */ /** @var string $to */ /** @var int $parcelRowStart */
$rowNum = (int)($parcelRowStart ?? 0);
?>
<style>
  @media print {
    .navbar, #sidebar, .sidebar-toggle-floating, .no-print { display: none !important; }
    .content-wrapper { margin-left: 0 !important; }
    .print-title { margin-bottom: 12px; font-size: 18px; font-weight: 700; }
    .print-meta { margin-bottom: 12px; color: #666; font-size: 11px; }
  }
  .print-title { margin-bottom: 12px; font-size: 18px; font-weight: 700; }
  .print-meta { margin-bottom: 12px; color: #666; font-size: 11px; }
  .parcels-print-list table { font-size: 11px; }
  .parcels-print-list th { white-space: nowrap; }
</style>
<div class="parcels-print-list">
  <div class="no-print mb-3 d-flex justify-content-between align-items-center">
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print();">Print</button>
    <a href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>" class="btn btn-outline-secondary btn-sm">Back to Parcels</a>
  </div>
  <h1 class="print-title">Parcels list</h1>
  <p class="print-meta">Date range: <?php echo htmlspecialchars($from ?? '—'); ?> to <?php echo htmlspecialchars($to ?? '—'); ?> &nbsp;|&nbsp; Total: <?php echo (int)($totalCount ?? 0); ?> parcel(s)</p>
  <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Customer</th>
          <th>Supplier</th>
          <th>From Branch</th>
          <th>To Branch</th>
          <th>Vehicle</th>
          <th>Delivery Route</th>
          <th>Items</th>
          <th>Weight</th>
          <th>Price</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($parcels as $p): $rowNum++; ?>
        <tr>
          <td><?php echo $rowNum; ?></td>
          <td><?php $nm = (string)($p['customer_name'] ?? ''); $ph = trim((string)($p['customer_phone'] ?? '')); $isPH = preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1; $label = $nm . (!$isPH && $ph !== '' ? ' (' . $ph . ')' : ''); echo htmlspecialchars($label); ?></td>
          <td><?php echo htmlspecialchars($p['supplier_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['from_branch'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['to_branch'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['vehicle_no'] ?? '—'); ?></td>
          <td>
            <?php
              $rdTo = trim((string)($p['route_date_to'] ?? ''));
              $rdFrom = trim((string)($p['route_date_from'] ?? ''));
              if ($rdTo !== '' || $rdFrom !== '') {
                $parts = [];
                if ($rdTo !== '') $parts[] = 'To: ' . $rdTo;
                if ($rdFrom !== '') $parts[] = 'From: ' . $rdFrom;
                echo htmlspecialchars(implode(' / ', $parts));
              } else {
                echo '—';
              }
            ?>
          </td>
          <td><?php $desc = trim((string)($p['item_descriptions'] ?? '')); echo $desc === '' ? '—' : htmlspecialchars($desc); ?></td>
          <td><?php echo number_format((float)($p['weight'] ?? 0), 2); ?></td>
          <td><?php echo $p['price'] === null ? '-' : number_format((float)$p['price'], 2); ?></td>
          <td><?php echo htmlspecialchars($p['status'] ?? ''); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (empty($parcels)): ?>
  <p class="text-muted">No parcels match the current filters.</p>
  <?php endif; ?>
</div>
