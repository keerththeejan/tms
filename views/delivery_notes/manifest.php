<?php /** @var string $vehicle_no */ /** @var string $from */ /** @var string $to */ /** @var array $grouped */ /** @var array $customers */ ?>
<?php $cfg = (require __DIR__ . '/../../config/config.php'); $brand = $cfg['company'] ?? []; ?>
<div class="mb-2 p-2 border rounded">
  <div class="d-flex align-items-center gap-2">
    <?php $useLogoImage = (($brand['logo_display'] ?? 'builtin') === 'image') && !empty($brand['logo_url']); ?>
    <?php if ($useLogoImage): ?>
      <?php $logoUrl = $brand['logo_url']; $logoUrl = (strpos($logoUrl, 'http') === 0 || strpos($logoUrl, '//') === 0) ? $logoUrl : Helpers::baseUrl($logoUrl); ?>
      <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" style="height:38px">
    <?php endif; ?>
    <div>
      <div class="fw-bold"><?php echo htmlspecialchars($brand['name'] ?? ''); ?></div>
      <div class="small text-muted">Transport and Parcel Services</div>
    </div>
  </div>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-1 mt-1 small text-muted">
    <?php foreach (($brand['addresses'] ?? []) as $addr): ?>
      <div><?php echo nl2br(htmlspecialchars($addr)); ?></div>
    <?php endforeach; ?>
  </div>
</div>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Route Manifest<?php echo ($vehicle_no!=='') ? ' - ' . htmlspecialchars($vehicle_no) : ''; ?></h3>
  <div class="text-muted">From: <?php echo htmlspecialchars($from); ?> &nbsp; To: <?php echo htmlspecialchars($to); ?></div>
</div>
<?php if (empty($grouped)): ?>
  <div class="alert alert-info">No parcels to print for the selected customers.</div>
<?php else: ?>
  <?php $grandWeight = 0.0; $grandAmount = 0.0; ?>
  <?php foreach ($grouped as $cid => $rows): $c = $customers[$cid] ?? ['name'=>'','phone'=>'']; ?>
    <div class="card shadow-sm mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
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
              <th>Vehicle</th>
              <th>Tracking</th>
              <th class="text-end">Weight</th>
              <th class="text-end">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php $subWeight = 0.0; $subAmount = 0.0; ?>
            <?php foreach ($rows as $p): $subWeight += (float)($p['weight'] ?? 0); $subAmount += (float)($p['price'] ?? 0); ?>
              <tr>
                <td><?php echo (int)$p['id']; ?></td>
                <td><?php echo htmlspecialchars(substr((string)($p['created_at'] ?? ''),0,19)); ?></td>
                <td><?php echo (int)($p['from_branch_id'] ?? 0); ?></td>
                <td><?php echo (int)($p['to_branch_id'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars((string)($p['vehicle_no'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($p['tracking_number'] ?? '')); ?></td>
                <td class="text-end"> <?php echo number_format((float)($p['weight'] ?? 0), 2); ?> </td>
                <td class="text-end"> <?php echo is_null($p['price']) ? '-' : number_format((float)($p['price'] ?? 0), 2); ?> </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="6" class="text-end">Subtotal</th>
              <th class="text-end"><?php echo number_format($subWeight, 2); ?></th>
              <th class="text-end"><?php echo number_format($subAmount, 2); ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <?php $grandWeight += $subWeight; $grandAmount += $subAmount; ?>
  <?php endforeach; ?>
  <div class="card">
    <div class="card-body d-flex justify-content-end gap-4">
      <div><strong>Total Weight:</strong> <?php echo number_format($grandWeight, 2); ?></div>
      <div><strong>Total Amount:</strong> <?php echo number_format($grandAmount, 2); ?></div>
    </div>
  </div>
  <script>window.print && window.print();</script>
<?php endif; ?>
