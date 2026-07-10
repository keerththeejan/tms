<?php
/** @var array $parcels */ /** @var string $from */ /** @var string $to */ /** @var int $parcelRowStart */
/** @var array $parcelItemsById */
$rowNum = (int)($parcelRowStart ?? 0);
$parcelItemsById = $parcelItemsById ?? [];
$reportTitle = 'Parcels List';
$reportPeriod = trim((string)($from ?? '') . ' — ' . (string)($to ?? ''));
include __DIR__ . '/../partials/report/embed_block.php';
?>
<style>
  @media print {
    .topbar, .navbar, #sidebar, .sidebar-overlay, .sidebar-toggle-floating, .skip-link, .no-print { display: none !important; }
    .content-wrapper { margin-left: 0 !important; padding: 0 !important; }
    .parcels-print-list {
      padding: 0;
      max-width: 100% !important;
      font-family: "Courier New", Courier, "Liberation Mono", monospace, "Noto Sans Tamil", "Latha", Tahoma, sans-serif;
      color: #000;
      -webkit-font-smoothing: auto;
    }
    .print-title { margin-bottom: 8px; font-size: 18px; font-weight: 800; color: #000; }
    .print-meta { margin-bottom: 10px; color: #000; font-size: 11px; }
    .parcels-print-list .table-responsive {
      overflow: visible !important;
      overflow-x: visible !important;
      max-width: 100% !important;
    }
    .parcels-print-list table { font-size: 12px; width: 100% !important; max-width: 100% !important; table-layout: fixed !important; }
    .parcels-print-list th, .parcels-print-list td {
      padding: 4px 6px !important;
      white-space: normal !important;
      word-break: break-word;
      overflow-wrap: anywhere;
      border-color: #000 !important;
      color: #000 !important;
    }
    .parcels-print-list th { white-space: normal !important; font-weight: 800 !important; background: #fff !important; }
    .parcels-print-list .parcel-print-items { page-break-inside: avoid; }
    .parcels-print-list .parcel-print-items th { border-bottom: 2px solid #000 !important; }
  }
  .print-title { margin-bottom: 12px; font-size: 18px; font-weight: 700; }
  .print-meta { margin-bottom: 12px; color: #666; font-size: 11px; }
  .parcels-print-list table { font-size: 11px; }
  @media screen {
    .parcels-print-list th { white-space: nowrap; }
  }
  .parcels-print-list .parcel-print-items { font-size: 10px; margin-bottom: 0; }
  .parcels-print-list .parcel-print-items th { background: #e9ecef; }
  .parcels-print-list .parcel-print-add { font-size: 9px; margin-right: 3px; }
</style>
<div class="parcels-print-list">
  <div class="no-print mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print();"><i class="bi bi-printer me-1"></i>Print</button>
    <a href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>" class="btn btn-outline-secondary btn-sm">Back to Parcels</a>
  </div>
  <h1 class="print-title">Parcels list</h1>
  <p class="print-meta">
    Date range: <?php echo htmlspecialchars($from ?? '—'); ?> to <?php echo htmlspecialchars($to ?? '—'); ?>
    &nbsp;|&nbsp; Total: <?php echo (int)($totalCount ?? 0); ?> parcel(s)
    <?php if (!empty($delivery_route_filter)): ?>
      &nbsp;|&nbsp; Route filter: <?php echo htmlspecialchars($delivery_route_filter); ?>
    <?php endif; ?>
  </p>
  <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>ID</th>
          <th>Serial</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Supplier</th>
          <th>From</th>
          <th>To</th>
          <th>Vehicle</th>
          <th>Route</th>
          <th>Weight</th>
          <th>Total</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $printColspan = 13;
        foreach ($parcels as $p):
          $rowNum++;
          $pid = (int)($p['id'] ?? 0);
          $track = trim((string)($p['tracking_number'] ?? ''));
          $nm = (string)($p['customer_name'] ?? '');
          $ph = trim((string)($p['customer_phone'] ?? ''));
          $isPH = preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1;
          $label = $nm . (!$isPH && $ph !== '' ? ' (' . $ph . ')' : '');
          $savedRoute = trim((string)($p['delivery_route'] ?? ''));
          $custLoc = trim((string)($p['customer_delivery_location'] ?? ''));
          $rdTo = trim((string)($p['route_date_to'] ?? ''));
          $rdFrom = trim((string)($p['route_date_from'] ?? ''));
          $veh = trim((string)($p['vehicle_no'] ?? ''));
          if ($savedRoute !== '') {
            $routeDisp = $savedRoute;
          } elseif ($custLoc !== '') {
            $routeDisp = $custLoc;
          } elseif ($rdTo !== '' || $rdFrom !== '') {
            $parts = [];
            if ($rdTo !== '') {
              $parts[] = 'To: ' . $rdTo;
            }
            if ($rdFrom !== '') {
              $parts[] = 'From: ' . $rdFrom;
            }
            if ($veh !== '') {
              array_unshift($parts, $veh);
            }
            $routeDisp = implode(' · ', $parts);
          } elseif ($veh !== '') {
            $routeDisp = $veh;
          } else {
            $routeDisp = '—';
          }
          $itemsRows = $parcelItemsById[$pid] ?? [];
        ?>
        <tr>
          <td><?php echo $rowNum; ?></td>
          <td><?php echo $pid; ?></td>
          <td><?php echo $track !== '' ? htmlspecialchars($track) : '—'; ?></td>
          <td><?php echo htmlspecialchars(substr((string)($p['created_at'] ?? ''), 0, 16)); ?></td>
          <td><?php echo htmlspecialchars($label); ?></td>
          <td><?php echo htmlspecialchars($p['supplier_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['from_branch'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['to_branch'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['vehicle_no'] ?? '—'); ?></td>
          <td><?php echo htmlspecialchars($routeDisp); ?></td>
          <td><?php echo number_format((float)($p['weight'] ?? 0), 2); ?></td>
          <td class="fw-bold"><?php echo $p['price'] === null ? '-' : number_format((float)$p['price'], 2); ?></td>
          <td><?php echo htmlspecialchars(Helpers::parcelStatusLabel((string)($p['status'] ?? ''))); ?></td>
        </tr>
        <tr>
          <td colspan="<?php echo $printColspan; ?>" class="bg-light p-2">
            <?php if (empty($itemsRows)): ?>
              <span class="text-muted small">No line items.</span>
            <?php else: ?>
              <table class="table table-sm table-bordered parcel-print-items mb-0">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Description</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
                    <th>Additional</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $ln = 0;
                  $sumAmt = 0.0;
                  $sumAdd = 0.0;
                  foreach ($itemsRows as $ir):
                    $ln++;
                    $qty = (float)($ir['qty'] ?? 0);
                    $rate = array_key_exists('rate', $ir) && $ir['rate'] !== null && $ir['rate'] !== '' ? (float)$ir['rate'] : null;
                    $amt = ($rate !== null && $qty > 0) ? round($qty * $rate, 2) : 0.0;
                    $sumAmt += $amt;
                    $addStored = array_key_exists('additional_amount', $ir) && $ir['additional_amount'] !== null && $ir['additional_amount'] !== ''
                      ? (float)$ir['additional_amount'] : 0.0;
                    $tagVals = [];
                    if (!empty($ir['additional_amounts'])) {
                      $dec = json_decode((string)$ir['additional_amounts'], true);
                      if (is_array($dec)) {
                        foreach ($dec as $tv) {
                          $tagVals[] = round((float)$tv, 2);
                        }
                      }
                    }
                    $add = $addStored > 0 ? $addStored : ($tagVals ? round(array_sum($tagVals), 2) : 0.0);
                    $sumAdd += $add;
                  ?>
                  <tr>
                    <td class="text-muted"><?php echo $ln; ?></td>
                    <td><?php echo htmlspecialchars((string)($ir['description'] ?? '')); ?></td>
                    <td class="text-end"><?php echo number_format($qty, 2); ?></td>
                    <td class="text-end"><?php echo $rate !== null ? number_format($rate, 2) : '—'; ?></td>
                    <td class="text-end"><?php echo $amt > 0 ? number_format($amt, 2) : '—'; ?></td>
                    <td>
                      <?php if ($tagVals): ?>
                        <?php foreach ($tagVals as $tv): ?>
                          <span class="badge bg-secondary bg-opacity-25 text-dark parcel-print-add">+<?php echo number_format($tv, 2); ?></span>
                        <?php endforeach; ?>
                      <?php elseif ($add > 0): ?>
                        <span class="badge bg-secondary bg-opacity-25 text-dark parcel-print-add">+<?php echo number_format($add, 2); ?></span>
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-end text-muted small">Subtotals</td>
                    <td class="text-end fw-semibold"><?php echo number_format($sumAmt, 2); ?></td>
                    <td class="fw-semibold"><?php echo $sumAdd > 0 ? '+' . number_format($sumAdd, 2) : '—'; ?></td>
                  </tr>
                  <tr>
                    <td colspan="6" class="text-end">
                      <span class="text-muted small me-2">Lines + additional</span>
                      <span class="fw-bold"><?php echo number_format($sumAmt + $sumAdd, 2); ?></span>
                    </td>
                  </tr>
                </tfoot>
              </table>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (empty($parcels)): ?>
  <p class="text-muted">No parcels match the current filters.</p>
  <?php endif; ?>
</div>
